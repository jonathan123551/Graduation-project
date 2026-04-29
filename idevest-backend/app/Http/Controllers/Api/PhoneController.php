<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Profile;
use Firebase\JWT\JWK;
use Firebase\JWT\JWT;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * Phone number verification via Firebase Phone Auth.
 *
 * Frontend uses the Firebase JS SDK to send the SMS OTP (reCAPTCHA-gated,
 * real SMS delivery handled by Firebase/GCP billing). Once the user enters
 * the OTP, the frontend confirms with Firebase and obtains a Firebase ID
 * token (JWT) which it POSTs here. We verify that token using Google's
 * public JWKS + standard claim checks, extract the verified phone number,
 * and mark the user's profile as phone-verified.
 *
 * No shared secret required — the JWT is validated purely on signature +
 * aud/iss/exp claims, which is the documented Firebase server-side flow.
 */
class PhoneController extends Controller
{
    protected const GOOGLE_JWKS_URL =
        'https://www.googleapis.com/service_accounts/v1/jwk/securetoken@system.gserviceaccount.com';

    protected const JWKS_CACHE_KEY  = 'firebase.jwks';
    protected const JWKS_CACHE_TTL  = 60 * 60; // 1h — keys rotate ~daily

    public function verifyFirebaseToken(Request $request): JsonResponse
    {
        $data = $request->validate([
            'firebase_id_token' => ['required', 'string'],
            'phone'             => ['required', 'string'],
        ]);

        $projectId = config('services.firebase.project_id', env('FIREBASE_PROJECT_ID', 'ideavest-otp'));

        try {
            $keys = $this->loadJwks();
            $decoded = JWT::decode($data['firebase_id_token'], $keys);
        } catch (Throwable $e) {
            Log::warning('Firebase token decode failed: ' . $e->getMessage());
            return response()->json([
                'message' => 'Invalid or expired verification token',
            ], 422);
        }

        $claims = (array) $decoded;
        $expectedIss = "https://securetoken.google.com/{$projectId}";

        if (($claims['aud'] ?? null) !== $projectId) {
            return response()->json(['message' => 'Token audience mismatch'], 422);
        }
        if (($claims['iss'] ?? null) !== $expectedIss) {
            return response()->json(['message' => 'Token issuer mismatch'], 422);
        }
        if (empty($claims['sub'])) {
            return response()->json(['message' => 'Token subject missing'], 422);
        }

        $verifiedPhone = $claims['phone_number'] ?? null;
        if (!$verifiedPhone) {
            return response()->json([
                'message' => 'Firebase token does not contain a verified phone number',
            ], 422);
        }

        $user = $request->user();
        $profile = Profile::firstOrCreate(
            ['user_id' => $user->id],
            ['full_name' => $user->full_name ?? '']
        );

        $profile->phone_number = $verifiedPhone;
        if (Schema::hasColumn('profiles', 'phone_verified_at')) {
            $profile->phone_verified_at = now();
        }
        $profile->save();

        return response()->json([
            'message' => 'Phone verified',
            'phone'   => $verifiedPhone,
        ]);
    }

    /**
     * Fetch + cache Google's JWKS for Firebase Secure Token service.
     *
     * Returns the key set in the format expected by firebase/php-jwt's
     * JWT::decode($jwt, $keys) — i.e. array keyed by kid.
     */
    protected function loadJwks(): array
    {
        $jwks = Cache::remember(self::JWKS_CACHE_KEY, self::JWKS_CACHE_TTL, function () {
            $resp = Http::timeout(10)->get(self::GOOGLE_JWKS_URL);
            if (!$resp->ok()) {
                throw new \RuntimeException('Failed to fetch Firebase JWKS: HTTP ' . $resp->status());
            }
            return $resp->json();
        });

        return JWK::parseKeySet($jwks, 'RS256');
    }
}
