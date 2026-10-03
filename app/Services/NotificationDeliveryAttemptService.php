<?php

namespace App\Services;

use App\Models\NotificationDelivery;
use App\Models\NotificationDeliveryAttempt;
use App\Models\UserDevice;
use Illuminate\Support\Collection;

class NotificationDeliveryAttemptService
{
    public function createForDelivery(
        NotificationDelivery $delivery
    ): Collection {
        if ($delivery->status !== 'pending') {
            return collect();
        }

        $devices = UserDevice::where('user_id', $delivery->user_id)
            ->where('is_active', true)
            ->get();

        return $devices->map(function (UserDevice $device) use ($delivery) {
            return NotificationDeliveryAttempt::firstOrCreate(
                [
                    'notification_delivery_id' => $delivery->id,
                    'user_device_id' => $device->id,
                ],
                [
                    'status' => 'pending',
                ]
            );
        });
    }
}