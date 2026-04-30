<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\KycVerification;
use App\Models\PhoneOtp;
use App\Models\Profile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class ProfileController extends Controller
{
    public function uploadAvatar(Request $request)
    {
        $request->validate([
            'avatar' => 'required|file|image|max:5120', // 5 MB
        ]);

        $path = $request->file('avatar')->store('avatars', 'public');
        $url = Storage::url($path);

        $profile = Profile::firstOrCreate(
            ['user_id' => $request->user()->id]
        );

        $profile->update(['avatar_url' => $url]);

        return response()->json([
            'path' => $path,
            'url' => $url,
            'profile' => $profile,
        ]);
    }

    public function show(Request $request)
    {
        return response()->json(
            $request->user()->load('profile')
        );
    }

    public function showById($userId)
    {
        $profile = Profile::with('user')
            ->where('user_id', $userId)
            ->first();

        if (!$profile) {
            return response()->json([
                'message' => 'Profile not found'
            ], 404);
        }

        return response()->json($profile);
    }

    public function update(Request $request)
    {
        $profile = Profile::firstOrCreate([
            'user_id' => $request->user()->id
        ]);

        $profile->update($request->only([
            'full_name',
            'phone_number',
            'bio',
            'location',
            'linkedin_url',
            'avatar_url',
            'skills'
        ]));

        return response()->json($profile);
    }

    
    public function gate(Request $request)
    {
        $user = $request->user();
        $profile = $user->profile;

        $kyc = KycVerification::where('user_id', $user->id)->first();

        return response()->json([
            'id'         => $user->id,
            'role'       => $user->role,
            'is_active'  => $user->is_active,
            'is_blocked' => $user->is_blocked,
            'email'      => $user->email,
            'hasPhone'   => (bool) ($profile && $profile->phone_verified_at),
            'kycStatus'  => $kyc->status ?? 'not_started',
        ]);
    }

    /**
     * Free OTP fallback: generate a 6-digit code, store it (hashed) in the
     * phone_otps table with a 10-minute TTL, and log it visibly. Real SMS
     * delivery is handled client-side via Firebase (PR #10) — these legacy
     * endpoints are kept for testing without Firebase being configured.
     */
    public function sendOtp(Request $request)
    {
        $data = $request->validate([
            'phone_number' => 'required|string|max:32',
        ]);

        $code = (string) random_int(100000, 999999);

        PhoneOtp::updateOrCreate(
            ['user_id' => $request->user()->id],
            [
                'phone_number' => $data['phone_number'],
                'code_hash'    => hash('sha256', $code),
                'expires_at'   => now()->addMinutes(10),
                'verified_at'  => null,
            ]
        );

        // Visible in Railway deploy/runtime logs so the user can read it
        // back during free-tier testing. NEVER do this in real production.
        Log::info("[phone-otp] user={$request->user()->id} phone={$data['phone_number']} code={$code}");

        $payload = [
            'message' => 'OTP sent',
            'expires_in_seconds' => 600,
        ];

        // In dev mode, also return the code in the response so end-to-end
        // tests can complete without scraping logs.
        if (config('app.debug')) {
            $payload['debug_code'] = $code;
        }

        return response()->json($payload);
    }

    public function verifyOtp(Request $request)
    {
        $data = $request->validate([
            'code' => 'required|string|size:6',
        ]);

        $otp = PhoneOtp::where('user_id', $request->user()->id)->first();

        if (!$otp || !$otp->expires_at || $otp->expires_at->isPast()) {
            throw ValidationException::withMessages([
                'code' => ['Code expired or never sent. Please request a new one.'],
            ]);
        }

        if (!hash_equals($otp->code_hash, hash('sha256', $data['code']))) {
            throw ValidationException::withMessages([
                'code' => ['Invalid verification code'],
            ]);
        }

        // Mark OTP as used and stamp profile.phone_verified_at.
        $otp->update(['verified_at' => now()]);

        $profile = Profile::firstOrCreate(['user_id' => $request->user()->id]);
        $profile->update([
            'phone_number'      => $otp->phone_number,
            'phone_verified_at' => now(),
        ]);

        return response()->json([
            'message' => 'Phone verified',
            'profile' => $profile->fresh(),
        ]);
    }
}