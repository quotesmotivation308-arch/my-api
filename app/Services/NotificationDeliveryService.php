<?php

namespace App\Services;

use App\Models\NotificationDelivery;
use App\Models\UserWordProgress;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class NotificationDeliveryService
{
    public function createDueDeliveries(int $userId): Collection
    {
        $progresses = UserWordProgress::where('user_id', $userId)
            ->where('status', 'learning')
            ->whereNotNull('next_review_at')
            ->where('next_review_at', '<=', now())
            ->get();

        return $progresses->map(function (UserWordProgress $progress) {
            $scheduledFor = $progress->next_review_at;

            $delivery = NotificationDelivery::firstOrCreate(
                [
                    'user_id' => $progress->user_id,
                    'progress_id' => $progress->id,
                    'scheduled_for' => $scheduledFor,
                ],
                [
                    'word_id' => $progress->word_id,
                    'status' => 'pending',
                    'action_token' => (string) Str::uuid(),
                ]
            );

            return $delivery;
        });
    }
}