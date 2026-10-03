<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReviewLog extends Model
{
    use HasFactory;

    protected $table = 'review_logs';

    protected $guarded = [];

    protected $casts = [
        'reviewed_at' => 'datetime',
        'previous_interval_days' => 'integer',
        'new_interval_days' => 'integer',
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

    public function progress(): BelongsTo
    {
        return $this->belongsTo(
            UserWordProgress::class,
            'progress_id'
        );
    }
}