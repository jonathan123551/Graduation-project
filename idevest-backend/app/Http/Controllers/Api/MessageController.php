<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Message;
use Illuminate\Http\Request;

class MessageController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'receiver_id' => 'required',
            'content' => 'required|string'
        ]);

        $msg = Message::create([
            'sender_id' => $request->user()->id,
            'receiver_id' => $data['receiver_id'],
            'content' => $data['content'],
            'read' => false
        ]);

        return response()->json($msg, 201);
    }

    public function thread(Request $request, $userId)
    {
        $myId = $request->user()->id;

        $messages = Message::where(function ($q) use ($myId, $userId) {
            $q->where('sender_id', $myId)
              ->where('receiver_id', $userId);
        })->orWhere(function ($q) use ($myId, $userId) {
            $q->where('sender_id', $userId)
              ->where('receiver_id', $myId);
        })
        ->orderBy('created_at')
        ->get();

        return response()->json($messages);
    }

    public function markRead(Request $request, $userId)
    {
        Message::where('sender_id', $userId)
            ->where('receiver_id', $request->user()->id)
            ->where('read', false)
            ->update([
                'read' => true,
                'read_at' => now()
            ]);

        return response()->json([
            'message' => 'Marked as read'
        ]);
    }

    /**
     * POST /messages/read with body { other_user_id }
     * Alias of `markRead` for the frontend's MessageThread component.
     */
    public function markReadAlias(Request $request)
    {
        $data = $request->validate([
            'other_user_id' => 'required',
        ]);

        return $this->markRead($request, $data['other_user_id']);
    }
        //test
    public function conversations(Request $request)
    {
        $myId = $request->user()->id;

        $messages = Message::where('sender_id', $myId)
            ->orWhere('receiver_id', $myId)
            ->latest()
            ->get();

        return response()->json($messages);
    }
}