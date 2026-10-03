<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UserWordProgress extends Model
{
    use HasFactory;

    protected $table = 'user_word_progress';

    protected $guarded = [];

    protected $casts = [
        'times_reviewed' => 'integer',
        'current_streak' => 'integer',
        'interval_days' => 'integer',
        'last_reviewed_at' => 'datetime',
        'next_review_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function word(): BelongsTo
    {
        return $this->belongsTo(Word::class);
    }

    public function reviewLogs(): HasMany
    {
        return $this->hasMany(ReviewLog::class, 'progress_id');
    }

    public function notificationDeliveries(): HasMany
    {
        return $this->hasMany(NotificationDelivery::class, 'progress_id');
    }
}