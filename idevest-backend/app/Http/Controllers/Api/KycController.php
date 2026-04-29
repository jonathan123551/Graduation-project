<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\KycVerification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class KycController extends Controller
{
    public function upload(Request $request)
    {
        $request->validate([
            'image' => 'required|file|image|max:10240', // 10 MB
            'side' => 'required|in:front,back',
        ]);

        $path = $request->file('image')->store(
            'kyc/' . $request->user()->id,
            'public'
        );
        $url = Storage::url($path);

        return response()->json([
            'path' => $url,
            'url' => $url,
        ]);
    }

    public function show(Request $request)
    {
        $kyc = KycVerification::firstOrCreate(
            ['user_id' => $request->user()->id],
            ['status' => 'not_started']
        );

        return response()->json($kyc);
    }

    public function submit(Request $request)
    {
        $kyc = KycVerification::firstOrCreate([
            'user_id' => $request->user()->id
        ]);

        $kyc->update([
            'status' => 'pending',
            'id_document_url' => $request->id_document_url,
            'selfie_url' => $request->selfie_url,
        ]);

        return response()->json([
            'message' => 'KYC submitted successfully'
        ]);
    }
}
