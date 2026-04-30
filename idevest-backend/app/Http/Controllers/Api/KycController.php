<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\KycVerification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Mindee\ClientV2;
use Mindee\Input\InferenceParameters;
use Mindee\Input\PathInput;
use Mindee\Parsing\V2\Field\SimpleField;
use Throwable;

class KycController extends Controller
{
    /**
     * Plain image upload endpoint used by the frontend to pre-upload ID card
     * images before the full verify-id-card call. Kept for backward compat.
     */
    public function upload(Request $request): JsonResponse
    {
        $request->validate([
            'image' => 'required|file|image|max:10240',
            'side'  => 'required|in:front,back',
        ]);

        $path = $request->file('image')->store('kyc/' . $request->user()->id, 'public');
        $url  = Storage::url($path);

        return response()->json([
            'path' => $url,
            'url'  => $url,
        ]);
    }

    public function show(Request $request): JsonResponse
    {
        // Don't auto-create — kyc_verifications has NOT NULL columns that
        // legitimately stay null until the user submits. Frontend handles
        // the null/empty case via `kyc?.id_card_front_url` etc.
        $kyc = KycVerification::where('user_id', $request->user()->id)->first();

        return response()->json($kyc);
    }

    /**
     * Manual KYC submission (no automated verification).
     *
     * Kept so existing frontend flows still work; the recommended path is
     * verifyIdCard() below which runs Mindee OCR and auto-decides status.
     */
    public function submit(Request $request): JsonResponse
    {
        $data = $request->validate([
            'full_legal_name' => ['required', 'string', 'max:255'],
            'national_id'     => ['required', 'string', 'max:50'],
            'id_card_front'   => ['nullable', 'string'],
            'id_card_back'    => ['nullable', 'string'],
            'date_of_birth'   => ['nullable', 'date'],
            'nationality'     => ['nullable', 'string', 'max:100'],
            'address'         => ['nullable', 'string'],
        ]);

        $kyc = KycVerification::firstOrNew(['user_id' => $request->user()->id]);

        foreach ($data as $key => $value) {
            $kyc->{$key} = $value;
        }
        $kyc->status = 'pending';
        $kyc->save();

        return response()->json([
            'message' => 'KYC submitted successfully',
            'kyc'     => $kyc->fresh(),
        ]);
    }

    /**
     * Verify an uploaded ID card image against a user-entered national ID
     * using Mindee's async V2 Extraction API. On success, the extracted
     * national ID must match the user input AND the confidence score must
     * exceed the configured threshold.
     *
     * Accepts multipart/form-data:
     *   id_card          file         required — image of the national ID
     *   national_id      string       required — the ID the user typed in
     *   full_legal_name  string       optional — stored alongside the record
     *   date_of_birth    date         optional
     *   nationality      string       optional
     *   address          string       optional
     */
    public function verifyIdCard(Request $request): JsonResponse
    {
        $data = $request->validate([
            'id_card'         => ['required', 'file', 'image', 'max:10240'],
            'national_id'     => ['required', 'string', 'max:50'],
            'full_legal_name' => ['nullable', 'string', 'max:255'],
            'date_of_birth'   => ['nullable', 'date'],
            'nationality'     => ['nullable', 'string', 'max:100'],
            'address'         => ['nullable', 'string'],
            'selfie_url'      => ['nullable', 'string'],
        ]);

        $apiKey  = config('services.mindee.api_key');
        $modelId = config('services.mindee.model_id');

        if (!$apiKey || !$modelId) {
            return response()->json([
                'message' => 'KYC verification is not configured on the server',
            ], 503);
        }

        // Persist upload to local public disk so we get a stable filesystem
        // path for Mindee and can also keep a URL on the KYC record.
        $file         = $request->file('id_card');
        $storedPath   = $file->store('kyc/' . $request->user()->id, 'public');
        $absolutePath = Storage::disk('public')->path($storedPath);
        $storedUrl    = Storage::url($storedPath);

        try {
            $client = new ClientV2($apiKey);
            $params = new InferenceParameters($modelId, null, null, null, true);
            $response = $client->enqueueAndGetInference(
                new PathInput($absolutePath),
                $params
            );
        } catch (Throwable $e) {
            Log::error('Mindee verify-id-card failed: ' . $e->getMessage());
            return response()->json([
                'message' => 'ID card verification service is temporarily unavailable',
                'detail'  => $e->getMessage(),
            ], 502);
        }

        $extracted     = $this->extractIdFields($response);
        $extractedId   = $extracted['id_number'] ?? null;
        $confidence    = $extracted['confidence'] ?? 0.0;
        $minConfidence = (float) config('services.mindee.min_confidence', 0.8);

        $userEnteredId = $this->normalizeId($data['national_id']);
        $normalizedExt = $this->normalizeId($extractedId ?? '');

        $idMatches   = $normalizedExt !== '' && $normalizedExt === $userEnteredId;
        $confidenceOk = $confidence >= $minConfidence;
        $approved    = $idMatches && $confidenceOk;

        $rejectionReason = null;
        if (!$idMatches) {
            $rejectionReason = 'Typed national ID does not match the one extracted from the card';
        } elseif (!$confidenceOk) {
            $rejectionReason = 'Image quality too low — re-upload a sharper photo of your ID';
        }

        $kyc = KycVerification::firstOrNew(['user_id' => $request->user()->id]);
        $kyc->fill([
            'full_legal_name' => $data['full_legal_name'] ?? ($kyc->full_legal_name ?? ''),
            'national_id'     => $data['national_id'],
            'date_of_birth'   => $data['date_of_birth']   ?? $kyc->date_of_birth,
            'nationality'     => $data['nationality']     ?? ($kyc->nationality ?? 'Egypt'),
            'address'         => $data['address']         ?? $kyc->address,
            'id_card_front'   => $storedUrl,
            'ai_verification_result' => json_encode([
                'mindee' => $extracted,
                'match'  => $idMatches,
                'confidence_threshold' => $minConfidence,
            ]),
            'status'          => $approved ? 'approved' : 'rejected',
            'rejection_reason' => $rejectionReason,
        ]);
        $kyc->save();

        return response()->json([
            'success'        => $approved,
            'message'        => $approved
                ? 'KYC verified successfully'
                : ($rejectionReason ?? 'Verification failed'),
            'confidence'     => $confidence,
            'extracted'      => $extracted,
            'kyc'            => $kyc->fresh(),
        ], $approved ? 200 : 422);
    }

    /**
     * Pull id_number + confidence from a Mindee V2 InferenceResponse, trying
     * a handful of common field-name conventions so this works with any
     * custom extraction model that exposes a "national id number" field
     * under any reasonable key.
     */
    protected function extractIdFields($response): array
    {
        $fields = $response->inference->result->fields ?? null;
        if (!$fields) {
            return ['id_number' => null, 'confidence' => 0.0, 'raw' => null];
        }

        $candidates = [
            'id_number',
            'national_id',
            'idNumber',
            'nationalId',
            'document_number',
            'card_number',
            'number',
        ];

        $idValue      = null;
        $confidence   = 0.0;
        $pickedKey    = null;

        foreach ($candidates as $key) {
            try {
                $field = $fields->get($key);
            } catch (Throwable) {
                continue;
            }
            if ($field instanceof SimpleField && !empty($field->value)) {
                $idValue   = $field->value;
                $pickedKey = $key;
                $confidence = $this->fieldConfidence($field);
                break;
            }
        }

        // If none of the expected keys hit, fall back to the highest-
        // confidence SimpleField whose value looks like a national ID.
        if ($idValue === null) {
            foreach ($fields as $key => $field) {
                if (!($field instanceof SimpleField) || empty($field->value)) {
                    continue;
                }
                $normalized = $this->normalizeId((string) $field->value);
                if (strlen($normalized) >= 10) {
                    $fieldConf = $this->fieldConfidence($field);
                    if ($fieldConf > $confidence) {
                        $idValue    = $field->value;
                        $pickedKey  = $key;
                        $confidence = $fieldConf;
                    }
                }
            }
        }

        return [
            'id_number'  => $idValue !== null ? (string) $idValue : null,
            'confidence' => $confidence,
            'field_key'  => $pickedKey,
        ];
    }

    protected function fieldConfidence(SimpleField $field): float
    {
        $conf = $field->confidence ?? null;
        if ($conf === null) {
            return 0.0;
        }
        if (is_object($conf)) {
            // FieldConfidence maps Certain/High/Medium/Low to numeric bands.
            $map = [
                'Certain' => 1.0,
                'High'    => 0.9,
                'Medium'  => 0.7,
                'Low'     => 0.4,
            ];
            $name = $conf->name ?? (string) $conf;
            return $map[$name] ?? 0.0;
        }
        return (float) $conf;
    }

    protected function normalizeId(string $value): string
    {
        return preg_replace('/\D+/', '', $value) ?? '';
    }
}
