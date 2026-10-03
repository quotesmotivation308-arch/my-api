<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\NotificationDelivery;
use App\Models\ReviewLog;
use App\Models\UserWordProgress;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WordReviewController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'word_id' => ['nullable', 'integer', 'exists:words,id'],
            'source' => ['required', 'string', 'in:notification,app'],
            'action_token' => ['nullable', 'uuid'],
        ]);

        $user = $request->user();

        if ($validated['source'] === 'notification') {
            if (empty($validated['action_token'])) {
                return response()->json([
                    'message' => 'action_token is required for notification reviews.',
                ], 422);
            }

            return $this->reviewFromNotification(
                $user->id,
                $validated['action_token']
            );
        }

        if (empty($validated['word_id'])) {
            return response()->json([
                'message' => 'word_id is required for app reviews.',
            ], 422);
        }

        return $this->reviewWord(
            $user->id,
            $validated['word_id'],
            'app'
        );
    }

    private function reviewFromNotification(
        int $userId,
        string $actionToken
    ): JsonResponse {
        return DB::transaction(function () use ($userId, $actionToken) {

            $delivery = NotificationDelivery::where('action_token', $actionToken)
                ->where('user_id', $userId)
                ->lockForUpdate()
                ->first();

            if (! $delivery) {
                return response()->json([
                    'message' => 'Notification delivery not found.',
                ], 404);
            }

            /*
             * Idempotency:
             * If this notification was already actioned,
             * do not register another review.
             */
            if ($delivery->actioned_at) {
                $progress = $delivery->progress;

                return response()->json([
                    'message' => 'Review was already registered for this notification.',
                    'already_actioned' => true,
                    'delivery_id' => $delivery->id,
                    'word_id' => $delivery->word_id,
                    'actioned_at' => $delivery->actioned_at,
                    'progress' => $progress,
                ]);
            }

            if ($delivery->status !== 'sent') {
                return response()->json([
                    'message' => 'Notification is not available for action.',
                    'status' => $delivery->status,
                ], 409);
            }



            $progress = UserWordProgress::where('id', $delivery->progress_id)
                ->where('user_id', $userId)
                ->lockForUpdate()
                ->first();

            if (! $progress) {
                return response()->json([
                    'message' => 'Learning progress not found.',
                ], 404);
            }

            $now = now();

            $previousInterval = $progress->interval_days;

            $intervals = [1, 3, 7, 14, 30, 60, 90];

            $nextInterval = $intervals[
                min(
                    $progress->times_reviewed + 1,
                    count($intervals) - 1
                )
            ];

            $progress->times_reviewed += 1;
            $progress->current_streak += 1;
            $progress->interval_days = $nextInterval;
            $progress->last_reviewed_at = $now;
            $progress->next_review_at = $now->copy()->addDays($nextInterval);

            $progress->save();

            $log = ReviewLog::create([
                'user_id' => $userId,
                'word_id' => $progress->word_id,
                'progress_id' => $progress->id,
                'source' => 'notification',
                'reviewed_at' => $now,
                'previous_interval_days' => $previousInterval,
                'new_interval_days' => $nextInterval,
                'next_review_at' => $progress->next_review_at,
            ]);

            $delivery->actioned_at = $now;
            $delivery->status = 'actioned';
            $delivery->save();

            return response()->json([
                'message' => 'Review registered successfully.',
                'already_actioned' => false,
                'delivery_id' => $delivery->id,
                'progress' => $progress,
                'review_log' => $log,
            ]);
        });
    }

    private function reviewWord(
        int $userId,
        int $wordId,
        string $source
    ): JsonResponse {
        return DB::transaction(function () use ($userId, $wordId, $source) {

            $progress = UserWordProgress::where('user_id', $userId)
                ->where('word_id', $wordId)
                ->lockForUpdate()
                ->first();

            if (! $progress) {
                return response()->json([
                    'message' => 'Word is not in the learning list.',
                ], 404);
            }

            $now = now();

            $previousInterval = $progress->interval_days;

            $intervals = [1, 3, 7, 14, 30, 60, 90];

            $nextInterval = $intervals[
                min(
                    $progress->times_reviewed + 1,
                    count($intervals) - 1
                )
            ];

            $progress->times_reviewed += 1;
            $progress->current_streak += 1;
            $progress->interval_days = $nextInterval;
            $progress->last_reviewed_at = $now;
            $progress->next_review_at = $now->copy()->addDays($nextInterval);

            $progress->save();

            $log = ReviewLog::create([
                'user_id' => $userId,
                'word_id' => $progress->word_id,
                'progress_id' => $progress->id,
                'source' => $source,
                'reviewed_at' => $now,
                'previous_interval_days' => $previousInterval,
                'new_interval_days' => $nextInterval,
                'next_review_at' => $progress->next_review_at,
            ]);

            return response()->json([
                'message' => 'Review registered successfully.',
                'already_actioned' => false,
                'progress' => $progress,
                'review_log' => $log,
            ]);
        });
    }

    public function due(Request $request): JsonResponse
    {
        $user = $request->user();

        $progresses = UserWordProgress::with('word')
            ->where('user_id', $user->id)
            ->where('status', 'learning')
            ->whereNotNull('next_review_at')
            ->where('next_review_at', '<=', now())
            ->orderBy('next_review_at')
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
                    'next_review_at' => $progress->next_review_at,
                ];
            }),
        ]);
    }
}