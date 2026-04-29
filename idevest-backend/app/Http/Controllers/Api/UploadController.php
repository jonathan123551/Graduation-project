<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * Generic authenticated file upload endpoint.
 *
 * Used by features like SubmitIdea that need to upload a single document and
 * then reference its public URL in a subsequent resource-create call.
 */
class UploadController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'file' => 'required|file|max:20480', // 20 MB
            'folder' => 'nullable|string|max:64|regex:/^[a-z0-9_\-\/]+$/i',
        ]);

        $folder = 'uploads/' . ($request->input('folder') ?: (string) $request->user()->id);

        $path = $request->file('file')->store($folder, 'public');
        $url = Storage::url($path);

        return response()->json([
            'path' => $path,
            'url' => $url,
        ]);
    }
}
