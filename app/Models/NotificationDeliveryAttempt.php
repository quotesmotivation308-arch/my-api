<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationDeliveryAttempt extends Model
{
    use HasFactory;

    protected $table = 'notification_delivery_attempts';

    protected $guarded = [];

    protected $casts = [
        'sent_at' => 'datetime',
        'failed_at' => 'datetime',
    ];

    public function notificationDelivery(): BelongsTo
    {
        return $this->belongsTo(
            NotificationDelivery::class,
            'notification_delivery_id'
        );
    }

    public function userDevice(): BelongsTo
    {
        return $this->belongsTo(
            UserDevice::class,
            'user_device_id'
        );
    }

    
}