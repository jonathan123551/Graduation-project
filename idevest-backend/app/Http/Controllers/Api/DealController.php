<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Deal;
use App\Models\Idea;
use Illuminate\Http\Request;

class DealController extends Controller
{
    public function index(Request $request)
    {
        $userId = $request->user()->id;

        $deals = Deal::with(['idea', 'investor.profile', 'founder.profile'])
            ->where(function ($q) use ($userId) {
                $q->where('investor_id', $userId)
                    ->orWhere('founder_id', $userId);
            })
            ->latest()
            ->get();

        return response()->json($deals);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'idea_id'           => 'required|exists:ideas,id',
            'investment_amount' => 'nullable|numeric|min:0',
            'amount'            => 'nullable|numeric|min:0',
            'equity_percentage' => 'nullable|numeric|min:0|max:100',
            'equity_percent'    => 'nullable|numeric|min:0|max:100',
            'valuation'         => 'nullable|numeric|min:0',
            'terms'             => 'nullable|string',
        ]);

        $idea = Idea::findOrFail($data['idea_id']);

        // Accept either `amount` (frontend convention) or `investment_amount`.
        $amount = $data['investment_amount'] ?? $data['amount'] ?? 0;
        $equity = $data['equity_percentage'] ?? $data['equity_percent'] ?? 0;
        // If valuation isn't provided, derive it from amount + equity.
        $valuation = $data['valuation']
            ?? ($equity > 0 ? round(($amount * 100) / $equity, 2) : $amount);

        $deal = Deal::create([
            'idea_id'           => $idea->id,
            'investor_id'       => $request->user()->id,
            'founder_id'        => $idea->founder_id,
            'investment_amount' => $amount,
            'equity_percentage' => $equity,
            'valuation'         => $valuation,
            'terms'             => $data['terms'] ?? null,
            'status'            => 'pending',
        ]);

        return response()->json($deal->load(['idea', 'investor.profile', 'founder.profile']), 201);
    }

    public function accept(Request $request, Deal $deal)
    {
        if ($deal->founder_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $deal->update([
            'status'      => 'active',
            'accepted_at' => now(),
        ]);

        return response()->json([
            'message' => 'Deal accepted',
            'deal'    => $deal->fresh(),
        ]);
    }

    public function reject(Request $request, Deal $deal)
    {
        if ($deal->founder_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $deal->update([
            'status' => 'cancelled',
        ]);

        return response()->json([
            'message' => 'Deal rejected',
            'deal'    => $deal->fresh(),
        ]);
    }

    public function signNda(Request $request, Deal $deal)
    {
        $userId = $request->user()->id;
        if ($deal->investor_id !== $userId && $deal->founder_id !== $userId) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $deal->update(['nda_signed_at' => now()]);

        return response()->json([
            'message' => 'NDA signed successfully',
            'deal'    => $deal->fresh(),
        ]);
    }
}
