<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $rows = Notification::where('user_id', $request->user()->id)
            ->latest()
            ->get();

        return response()->json($rows);
    }

    public function markRead(Request $request, Notification $n)
    {
        if ($n->user_id !== $request->user()->id) {
            return response()->json([
                'message' => 'Unauthorized'
            ], 403);
        }

        $n->update([
            'read' => true
        ]);

        return response()->json([
            'message' => 'Marked as read'
        ]);
    }

    public function readAll(Request $request)
    {
        Notification::where('user_id', $request->user()->id)
            ->update([
                'read' => true
            ]);

        return response()->json([
            'message' => 'All notifications marked read'
        ]);
    }
}