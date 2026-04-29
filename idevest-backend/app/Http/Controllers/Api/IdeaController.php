<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Idea;
use App\Models\SavedIdea;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class IdeaController extends Controller
{
    public function publish(Request $request, Idea $idea)
    {
        if ($idea->founder_id !== $request->user()->id) {
            return response()->json([
                'message' => 'Unauthorized',
            ], 403);
        }

        $idea->update(['status' => 'published']);

        return response()->json($idea);
    }

    public function uploadPitchDeck(Request $request, Idea $idea)
    {
        if ($idea->founder_id !== $request->user()->id) {
            return response()->json([
                'message' => 'Unauthorized',
            ], 403);
        }

        $request->validate([
            'pitch_deck' => 'required|file|max:20480|mimes:pdf,ppt,pptx,key,doc,docx',
        ]);

        $path = $request->file('pitch_deck')->store('pitch-decks', 'public');
        $url = Storage::url($path);

        $idea->update(['pitch_deck_url' => $url]);

        return response()->json([
            'path' => $path,
            'url' => $url,
            'idea' => $idea,
        ]);
    }

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
            'expected_revenue' => 'nullable|string|max:100',
            'team_size' => 'nullable|integer',
            'team_experience' => 'nullable|string',
            'competitors' => 'nullable|string',
            'competitive_advantage' => 'nullable|string',
            'target_audience' => 'nullable|string',
            'timeline' => 'nullable|string|max:255',
            'additional_info' => 'nullable|string',
            'document_url' => 'nullable|string|max:1024',
            'ai_score' => 'nullable|integer',
            'risk_score' => 'nullable|integer',
            'market_score' => 'nullable|integer',
            'innovation_score' => 'nullable|integer',
            'execution_score' => 'nullable|integer',
            'investment_score' => 'nullable|integer',
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
