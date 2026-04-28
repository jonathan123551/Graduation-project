<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Deal;
use Illuminate\Http\Request;

class DealController extends Controller
{
    public function index(Request $request)
    {
        $userId = $request->user()->id;

        $deals = Deal::with(['idea', 'investor', 'founder'])
            ->where('investor_id', $userId)
            ->orWhere('founder_id', $userId)
            ->latest()
            ->get();

        return response()->json($deals);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'idea_id' => 'required',
            'founder_id' => 'required',
            'amount' => 'required|numeric',
            'equity_percentage' => 'nullable|numeric',
            'terms' => 'nullable|string',
        ]);

        $deal = Deal::create([
            'idea_id' => $data['idea_id'],
            'investor_id' => $request->user()->id,
            'founder_id' => $data['founder_id'],
            'amount' => $data['amount'],
            'equity_percentage' => $data['equity_percentage'] ?? null,
            'terms' => $data['terms'] ?? null,
            'status' => 'pending',
        ]);

        return response()->json($deal, 201);
    }

    public function accept(Request $request, Deal $deal)
    {
        if ($deal->founder_id !== $request->user()->id) {
            return response()->json([
                'message' => 'Unauthorized'
            ], 403);
        }

        $deal->update([
            'status' => 'accepted',
            'accepted_at' => now(),
        ]);

        return response()->json([
            'message' => 'Deal accepted'
        ]);
    }

    public function reject(Request $request, Deal $deal)
    {
        if ($deal->founder_id !== $request->user()->id) {
            return response()->json([
                'message' => 'Unauthorized'
            ], 403);
        }

        $deal->update([
            'status' => 'rejected',
        ]);

        return response()->json([
            'message' => 'Deal rejected'
        ]);
    }

    public function signNda(Deal $deal)
    {
        return response()->json([
            'message' => 'NDA signed successfully'
        ]);
    }
}
