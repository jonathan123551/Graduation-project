<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AccessRequest;
use App\Models\Idea;
use Illuminate\Http\Request;

class AccessRequestController extends Controller
{
    public function index(Request $request)
    {
        $userId = $request->user()->id;

        $rows = AccessRequest::with([
            'idea',
            'investor.profile',
            'founder.profile'
        ])
        ->where('investor_id', $userId)
        ->orWhere('founder_id', $userId)
        ->latest()
        ->get();

        return response()->json($rows);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'idea_id'    => 'required|exists:ideas,id',
            'founder_id' => 'nullable',
            'message'    => 'nullable|string',
        ]);

        // Derive founder_id from the idea so the frontend doesn't have to
        // know it (and can't spoof it).
        $idea = Idea::findOrFail($data['idea_id']);

        $row = AccessRequest::firstOrCreate(
            [
                'investor_id' => $request->user()->id,
                'idea_id'     => $idea->id,
            ],
            [
                'founder_id' => $idea->founder_id,
                'message'    => $data['message'] ?? null,
                'status'     => 'pending',
            ]
        );

        return response()->json($row, 201);
    }

    /**
     * GET /access-requests/check/{ideaId}
     * Returns the current viewer's request status for the given idea, or
     * { status: null } if no request exists. Used by IdeaDetail.tsx to
     * decide whether to show "Request Access" vs "Pending" vs "Approved".
     */
    public function check(Request $request, $ideaId)
    {
        $row = AccessRequest::where('investor_id', $request->user()->id)
            ->where('idea_id', $ideaId)
            ->first();

        return response()->json([
            'status' => $row?->status,
            'request' => $row,
        ]);
    }

    public function approve(Request $request, AccessRequest $req)
    {
        if ($req->founder_id !== $request->user()->id) {
            return response()->json([
                'message' => 'Unauthorized'
            ], 403);
        }

        $req->update([
            'status' => 'approved'
        ]);

        return response()->json([
            'message' => 'Approved'
        ]);
    }

    public function reject(Request $request, AccessRequest $req)
    {
        if ($req->founder_id !== $request->user()->id) {
            return response()->json([
                'message' => 'Unauthorized'
            ], 403);
        }

        $req->update([
            'status' => 'rejected'
        ]);

        return response()->json([
            'message' => 'Rejected'
        ]);
  
        }
}
