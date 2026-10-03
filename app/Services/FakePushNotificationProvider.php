<?php

namespace App\Services;

use App\Models\NotificationDeliveryAttempt;
use Illuminate\Support\Str;

class FakePushNotificationProvider implements PushNotificationProvider
{
    public function send(
        NotificationDeliveryAttempt $attempt,
        array $payload
    ): array {
        return [
            'success' => true,
            'provider' => 'fake',
            'provider_message_id' => 'fake-' . Str::uuid(),
        ];
    }
}