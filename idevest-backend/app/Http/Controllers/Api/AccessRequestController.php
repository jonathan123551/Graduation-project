<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AccessRequest;
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
            'idea_id' => 'required',
            'founder_id' => 'required',
            'message' => 'nullable|string'
        ]);

        $row = AccessRequest::firstOrCreate(
            [
                'investor_id' => $request->user()->id,
                'idea_id' => $data['idea_id']
            ],
            [
                'founder_id' => $data['founder_id'],
                'message' => $data['message'] ?? null,
                'status' => 'pending'
            ]
        );

        return response()->json($row, 201);
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
