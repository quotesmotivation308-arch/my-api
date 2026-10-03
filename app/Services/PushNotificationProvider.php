<?php

namespace App\Services;

use App\Models\NotificationDeliveryAttempt;

interface PushNotificationProvider
{
    public function send(
        NotificationDeliveryAttempt $attempt,
        array $payload
    ): array;
}