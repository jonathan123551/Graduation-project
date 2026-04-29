<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Idea;
use App\Models\Report;
use App\Models\KycVerification;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    public function stats()
    {
        return response()->json([
            'users' => User::count(),
            'ideas' => Idea::count(),
            'reports' => Report::count(),
            'kyc_pending' => KycVerification::where('status', 'pending')->count(),
        ]);
    }

    public function users()
    {
        return response()->json(
            User::latest()->get()
        );
    }

    public function blockUser(Request $request, User $user)
    {
        $user->update([
            'is_blocked' => true,
        ]);

        return response()->json([
            'message' => 'User blocked',
            'reason' => $request->input('reason'),
        ]);
    }

    public function unblockUser(User $user)
    {
        $user->update([
            'is_blocked' => false,
        ]);

        return response()->json([
            'message' => 'User unblocked',
        ]);
    }

    public function grantRole(Request $request)
    {
        $data = $request->validate([
            'email' => 'nullable|email',
            'name'  => 'nullable|string',
            'role'  => 'required|in:entrepreneur,investor,explorer,admin',
        ]);

        $query = User::query();
        if (!empty($data['email'])) {
            $query->where('email', $data['email']);
        } elseif (!empty($data['name'])) {
            $query->where('full_name', $data['name']);
        } else {
            return response()->json([
                'message' => 'Provide `email` or `name` to identify the user.',
            ], 422);
        }

        $user = $query->first();
        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        $user->update(['role' => $data['role']]);

        return response()->json([
            'message' => 'Role granted',
            'user' => $user,
        ]);
    }

    public function ideas()
    {
        return response()->json(
            Idea::with('founder')->latest()->get()
        );
    }

    public function approveIdea(Idea $idea)
    {
        $idea->update([
            'status' => 'published',
        ]);

        return response()->json([
            'message' => 'Idea approved',
        ]);
    }

    public function rejectIdea(Idea $idea)
    {
        $idea->update([
            'status' => 'rejected',
        ]);

        return response()->json([
            'message' => 'Idea rejected',
        ]);
    }

    public function toggleIdea(Request $request, Idea $idea)
    {
        $data = $request->validate([
            'status' => 'required|string|max:50',
        ]);

        $idea->update(['status' => $data['status']]);

        return response()->json([
            'message' => 'Idea status updated',
            'idea' => $idea,
        ]);
    }

    public function reports()
    {
        return response()->json(
            Report::latest()->get()
        );
    }

    public function resolveReport(Report $report)
    {
        $report->update([
            'status' => 'resolved'
        ]);

        return response()->json([
            'message' => 'Resolved'
        ]);
    }

    public function kycList()
    {
        return response()->json(
            KycVerification::latest()->get()
        );
    }

    public function approveKyc(KycVerification $kyc)
    {
        $kyc->update([
            'status' => 'approved'
        ]);

        return response()->json([
            'message' => 'KYC approved'
        ]);
    }

    public function rejectKyc(KycVerification $kyc)
    {
        $kyc->update([
            'status' => 'rejected'
        ]);

        return response()->json([
            'message' => 'KYC rejected'
        ]);
    }

    public function analytics()
    {
        return response()->json([
            'message' => 'Analytics soon'
        ]);
    }
}
