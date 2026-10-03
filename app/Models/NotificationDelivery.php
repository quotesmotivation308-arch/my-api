<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NotificationDelivery extends Model
{
    use HasFactory;

    protected $table = 'notification_deliveries';

    protected $guarded = [];

    protected $casts = [
        'scheduled_for' => 'datetime',
        'sent_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function word(): BelongsTo
    {
        return $this->belongsTo(Word::class);
    }

    public function progress(): BelongsTo
    {
        return $this->belongsTo(
            UserWordProgress::class,
            'progress_id'
        );
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(
            NotificationDeliveryAttempt::class,
            'notification_delivery_id'
        );
    }
}