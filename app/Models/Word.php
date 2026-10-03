<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Word extends Model
{
    use HasFactory;

    protected $table = 'words';

    protected $guarded = [];

    protected $casts = [
        'found' => 'boolean',
        '_version' => 'integer',
        'Created Date' => 'integer',
        'Modified Date' => 'integer',
        'culture_number' => 'integer',
        'l_grammars_list_custom_evp_l_grammar' => 'array',
        'l_topics_list_custom_evp_l_topic' => 'array',
        'source_json' => 'array',
    ];

    public function userProgress(): HasMany
    {
        return $this->hasMany(UserWordProgress::class);
    }

    public function reviewLogs(): HasMany
    {
        return $this->hasMany(ReviewLog::class);
    }

    public function notificationDeliveries(): HasMany
    {
        return $this->hasMany(NotificationDelivery::class);
    }
}