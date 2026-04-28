<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Report;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'reported_user_id' => 'nullable',
            'idea_id' => 'nullable',
            'type' => 'required|in:user,idea,message',
            'reason' => 'required|string'
        ]);

        $report = Report::create([
            'reporter_id' => $request->user()->id,
            'reported_user_id' => $data['reported_user_id'] ?? null,
            'idea_id' => $data['idea_id'] ?? null,
            'type' => $data['type'],
            'reason' => $data['reason'],
            'status' => 'open'
        ]);

        return response()->json($report, 201);
    }
}
