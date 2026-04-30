<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\NdaSignature;
use Illuminate\Http\Request;

/**
 * Pairwise NDA signing between an investor and a founder over a specific
 * idea. The frontend's NdaGate component blocks the chat thread until
 * BOTH parties have signed — so each call here records ONE signature
 * for the calling user.
 */
class NdaController extends Controller
{
    public function sign(Request $request)
    {
        $data = $request->validate([
            'idea_id'        => 'required|exists:ideas,id',
            'other_user_id'  => 'required|exists:users,id',
        ]);

        $signature = NdaSignature::firstOrCreate(
            [
                'idea_id'       => $data['idea_id'],
                'user_id'       => $request->user()->id,
                'other_user_id' => $data['other_user_id'],
            ],
            [
                'signed_at' => now(),
            ]
        );

        return response()->json([
            'message'   => 'NDA signed',
            'signature' => $signature,
            'both_signed' => $this->isPairwiseComplete($data['idea_id'], $request->user()->id, $data['other_user_id']),
        ]);
    }

    /**
     * GET /nda/check?idea_id=…&other_user_id=…
     * Returns whether the current user has signed and whether both sides have.
     */
    public function check(Request $request)
    {
        $data = $request->validate([
            'idea_id'        => 'required',
            'other_user_id'  => 'required',
        ]);

        $mySigned = NdaSignature::where('idea_id', $data['idea_id'])
            ->where('user_id', $request->user()->id)
            ->where('other_user_id', $data['other_user_id'])
            ->exists();

        return response()->json([
            'signed'      => $mySigned,
            'both_signed' => $this->isPairwiseComplete($data['idea_id'], $request->user()->id, $data['other_user_id']),
        ]);
    }

    protected function isPairwiseComplete($ideaId, $a, $b): bool
    {
        $aSigned = NdaSignature::where('idea_id', $ideaId)
            ->where('user_id', $a)
            ->where('other_user_id', $b)
            ->exists();
        $bSigned = NdaSignature::where('idea_id', $ideaId)
            ->where('user_id', $b)
            ->where('other_user_id', $a)
            ->exists();
        return $aSigned && $bSigned;
    }
}
