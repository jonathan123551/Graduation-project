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

    public function blockUser(User $user)
    {
        $user->update([
            'is_blocked' => true
        ]);

        return response()->json([
            'message' => 'User blocked'
        ]);
    }

    public function unblockUser(User $user)
    {
        $user->update([
            'is_blocked' => false
        ]);

        return response()->json([
            'message' => 'User unblocked'
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
            'status' => 'published'
        ]);

        return response()->json([
            'message' => 'Idea approved'
        ]);
    }

    public function rejectIdea(Idea $idea)
    {
        $idea->update([
            'status' => 'rejected'
        ]);

        return response()->json([
            'message' => 'Idea rejected'
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
