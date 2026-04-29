<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Profile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

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

        return response()->json([
            'id' => $user->id,
            'role' => $user->role,
            'is_active' => $user->is_active,
            'is_blocked' => $user->is_blocked,
            'email' => $user->email,
        ]);
    }

    public function sendOtp()
    {
        return response()->json([
            'message' => 'OTP sent (mock)'
        ]);
    }

    public function verifyOtp()
    {
        return response()->json([
            'message' => 'Phone verified (mock)'
        ]);
    }
}