<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\UserWordProgress;
use App\Models\Word;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WordLearningController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'word_id' => ['required', 'integer', 'exists:words,id'],
        ]);

        $user = $request->user();

        $progress = UserWordProgress::where('user_id', $user->id)
            ->where('word_id', $validated['word_id'])
            ->first();

        if ($progress) {
            if ($progress->status === 'learning') {
                return response()->json([
                    'message' => 'Word is already in the learning list.',
                    'word' => $progress->word,
                    'progress' => $progress,
                    'already_learning' => true,
                    'reactivated' => false,
                ]);
            }

            $progress->status = 'learning';

            if (! $progress->next_review_at) {
                $progress->next_review_at = now()->addDay();
            }

            $progress->save();

            return response()->json([
                'message' => 'Word added back to learning.',
                'word' => $progress->word,
                'progress' => $progress,
                'already_learning' => false,
                'reactivated' => true,
            ]);
        }

        $progress = UserWordProgress::create([
            'user_id' => $user->id,
            'word_id' => $validated['word_id'],
            'status' => 'learning',
            'times_reviewed' => 0,
            'current_streak' => 0,
            'interval_days' => 1,
            'last_reviewed_at' => null,
            'next_review_at' => now()->addDay(),
        ]);

        return response()->json([
            'message' => 'Word added to learning.',
            'word' => $progress->word,
            'progress' => $progress,
            'already_learning' => false,
            'reactivated' => false,
        ]);
    }

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $progresses = UserWordProgress::with('word')
            ->where('user_id', $user->id)
            ->where('status', 'learning')
            ->orderBy('id')
            ->get();

        return response()->json([
            'count' => $progresses->count(),
            'words' => $progresses->map(function ($progress) {
                return [
                    'progress_id' => $progress->id,
                    'word_id' => $progress->word_id,
                    'base_text' => $progress->word->base_text,
                    'definition_text' => $progress->word->definition_text,
                    'learnerexamples_text' => $progress->word->learnerexamples_text,
                    'ukpron_text' => $progress->word->ukpron_text,
                    'audiofilename_text' => $progress->word->audiofilename_text,
                    'times_reviewed' => $progress->times_reviewed,
                    'current_streak' => $progress->current_streak,
                    'interval_days' => $progress->interval_days,
                    'last_reviewed_at' => $progress->last_reviewed_at,
                    'next_review_at' => $progress->next_review_at,
                ];
            }),
        ]);
    }

    public function destroy(Request $request, int $wordId): JsonResponse
    {
        $user = $request->user();

        $progress = UserWordProgress::where('user_id', $user->id)
            ->where('word_id', $wordId)
            ->first();

        if (! $progress) {
            return response()->json([
                'message' => 'Word is not in the learning list.',
            ], 404);
        }

        $progress->status = 'known';
        $progress->save();

        return response()->json([
            'message' => 'Word removed from learning list.',
            'word_id' => $wordId,
            'status' => $progress->status,
        ]);
    }


}