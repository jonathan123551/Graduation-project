<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Idea;
use App\Models\SavedIdea;
use Illuminate\Http\Request;

class IdeaController extends Controller
{
    public function index(Request $request)
    {
        $query = Idea::with('founder.profile')
            ->where('status', 'published');

        if ($request->sector) {
            $query->where('sector', $request->sector);
        }

        if ($request->founder_id) {
            $query->where('founder_id', $request->founder_id);
        }

        return response()->json(
            $query->latest()->get()
        );
    }

    public function show(Idea $idea)
    {
        return response()->json(
            $idea->load('founder.profile')
        );
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'sector' => 'required|string|max:100',
            'location' => 'nullable|string|max:255',
            'capital_required' => 'nullable|string|max:100',
            'ai_score' => 'nullable|integer',
            'risk_score' => 'nullable|integer',
            'market_score' => 'nullable|integer',
            'decision' => 'nullable|string',
            'ai_evaluation' => 'nullable|string',
        ]);

        $data['founder_id'] = $request->user()->id;
        $data['status'] = 'published';

        $idea = Idea::create($data);

        return response()->json($idea, 201);
    }

    public function update(Request $request, Idea $idea)
    {
        if ($idea->founder_id !== $request->user()->id) {
            return response()->json([
                'message' => 'Unauthorized'
            ], 403);
        }

        $idea->update($request->all());

        return response()->json($idea);
    }

    public function destroy(Request $request, Idea $idea)
    {
        if ($idea->founder_id !== $request->user()->id) {
            return response()->json([
                'message' => 'Unauthorized'
            ], 403);
        }

        $idea->delete();

        return response()->json([
            'message' => 'Deleted'
        ]);
    }

    public function save(Request $request, Idea $idea)
    {
        SavedIdea::firstOrCreate([
            'user_id' => $request->user()->id,
            'idea_id' => $idea->id,
        ]);

        return response()->json([
            'message' => 'Saved'
        ]);
    }

    public function unsave(Request $request, Idea $idea)
    {
        SavedIdea::where('user_id', $request->user()->id)
            ->where('idea_id', $idea->id)
            ->delete();

        return response()->json([
            'message' => 'Unsaved'
        ]);
    }

    public function savedIdeas(Request $request)
    {
        return response()->json(
            SavedIdea::with('ideas')
                ->where('user_id', $request->user()->id)
                ->latest()
                ->get()
        );
    }
}