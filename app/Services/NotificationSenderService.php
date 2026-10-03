<?php

namespace App\Services;

use App\Models\NotificationDelivery;
use App\Models\NotificationDeliveryAttempt;
use Illuminate\Support\Facades\DB;

class NotificationSenderService
{
    public function __construct(
        private PushNotificationProvider $provider,
        private NotificationPayloadService $payloadService
    ) {
    }

    public function sendAttempt(
        NotificationDeliveryAttempt $attempt
    ): NotificationDeliveryAttempt {
        return DB::transaction(function () use ($attempt) {

            $attempt = NotificationDeliveryAttempt::where('id', $attempt->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($attempt->status !== 'pending') {
                return $attempt;
            }

            $delivery = NotificationDelivery::where(
                'id',
                $attempt->notification_delivery_id
            )
                ->lockForUpdate()
                ->firstOrFail();

            if ($delivery->status === 'actioned') {
                return $attempt;
            }

            $payload = $this->payloadService->build($delivery);

            $result = $this->provider->send(
                $attempt,
                $payload
            );

            $now = now();

            if (! $result['success']) {
                $attempt->status = 'failed';
                $attempt->provider = $result['provider'] ?? null;
                $attempt->error_message = $result['error_message'] ?? null;
                $attempt->failed_at = $now;
                $attempt->save();

                return $attempt;
            }

            $attempt->status = 'sent';
            $attempt->provider = $result['provider'];
            $attempt->provider_message_id = $result['provider_message_id'];
            $attempt->sent_at = $now;
            $attempt->save();

            $delivery->status = 'sent';
            $delivery->provider = $result['provider'];
            $delivery->provider_message_id = $result['provider_message_id'];
            $delivery->sent_at = $now;
            $delivery->save();

            return $attempt;
        });
    }
}