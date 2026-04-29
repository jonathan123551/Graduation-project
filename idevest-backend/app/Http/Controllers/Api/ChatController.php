<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AiChatMessage;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ChatController extends Controller
{
    /**
     * Return the authenticated user's saved chat history ordered oldest-first.
     */
    public function history(Request $request)
    {
        $messages = AiChatMessage::where('user_id', $request->user()->id)
            ->oldest()
            ->get(['role', 'message as content', 'created_at']);

        return response()->json(['data' => $messages]);
    }

    /**
     * Persist a single chat message (user or assistant) for the authenticated
     * user. Used as a best-effort save hook from the client.
     */
    public function storeMessage(Request $request)
    {
        $data = $request->validate([
            'role' => 'required|in:user,assistant',
            'content' => 'required|string',
        ]);

        $msg = AiChatMessage::create([
            'user_id' => $request->user()->id,
            'role' => $data['role'],
            'message' => $data['content'],
        ]);

        return response()->json($msg, 201);
    }

    /**
     * Delete the authenticated user's chat history.
     */
    public function clearHistory(Request $request)
    {
        AiChatMessage::where('user_id', $request->user()->id)->delete();

        return response()->json(['message' => 'History cleared']);
    }

    /**
     * Stream a completion from the configured OpenAI-compatible provider
     * (default: Groq) as an SSE stream. Forwards the upstream SSE body
     * unchanged so the frontend can parse `data: {"choices":[{"delta":{"content":"..."}}]}`
     * events the same way it would from OpenAI.
     */
    public function stream(Request $request)
    {
        $data = $request->validate([
            'messages' => 'required|array|min:1',
            'messages.*.role' => 'required|in:user,assistant,system',
            'messages.*.content' => 'required|string',
        ]);

        $apiKey = config('services.ai.key');
        $baseUrl = rtrim(config('services.ai.base_url'), '/');
        $model = config('services.ai.model');

        if (!$apiKey) {
            return response()->json([
                'error' => 'AI provider is not configured. Set AI_API_KEY (or GEMINI_API_KEY / GROQ_API_KEY) in the server environment.',
            ], 503);
        }

        $payload = [
            'model' => $model,
            'messages' => $data['messages'],
            'stream' => true,
        ];

        return new StreamedResponse(function () use ($payload, $apiKey, $baseUrl) {
            $ch = curl_init($baseUrl . '/chat/completions');
            curl_setopt_array($ch, [
                CURLOPT_POST => true,
                CURLOPT_HTTPHEADER => [
                    'Authorization: Bearer ' . $apiKey,
                    'Content-Type: application/json',
                    'Accept: text/event-stream',
                ],
                CURLOPT_POSTFIELDS => json_encode($payload),
                CURLOPT_RETURNTRANSFER => false,
                CURLOPT_WRITEFUNCTION => function ($ch, $chunk) {
                    echo $chunk;
                    @ob_flush();
                    @flush();
                    return strlen($chunk);
                },
                CURLOPT_TIMEOUT => 0,
            ]);
            curl_exec($ch);
            if ($err = curl_error($ch)) {
                echo "data: " . json_encode(['error' => $err]) . "\n\n";
            }
            curl_close($ch);
            echo "data: [DONE]\n\n";
            @ob_flush();
            @flush();
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache, no-transform',
            'X-Accel-Buffering' => 'no',
            'Connection' => 'keep-alive',
        ]);
    }
}
