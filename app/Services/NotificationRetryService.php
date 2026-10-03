<?php

namespace App\Services;

use App\Models\NotificationDeliveryAttempt;
use Illuminate\Support\Facades\DB;

class NotificationRetryService
{
    public function retry(
        NotificationDeliveryAttempt $attempt
    ): NotificationDeliveryAttempt {
        return DB::transaction(function () use ($attempt) {

            $attempt = NotificationDeliveryAttempt::where('id', $attempt->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($attempt->status !== 'failed') {
                return $attempt;
            }

            $delivery = $attempt->notificationDelivery()
                ->lockForUpdate()
                ->first();

            if (! $delivery) {
                return $attempt;
            }

            if ($delivery->status === 'actioned') {
                return $attempt;
            }

            $attempt->status = 'pending';
            $attempt->failed_at = null;
            $attempt->error_message = null;
            $attempt->save();

            return $attempt;
        });
    }
}