<?php

namespace App\Services;

use App\Models\NotificationDelivery;

class NotificationPayloadService
{
    public function build(
        NotificationDelivery $delivery
    ): array {
        $delivery->loadMissing('word');

        $word = $delivery->word;

        $example = collect(
            preg_split(
                '/\s*;\s*/',
                trim((string) $word->learnerexamples_text),
                -1,
                PREG_SPLIT_NO_EMPTY
            )
        )->first();

        return [
            'title' => 'وقت مرور زبان انگلیسی 📚',

            'body' => $word->base_text . ' — ' . $word->definition_text,

            'data' => [
                'type' => 'word_review',
                'word_id' => (string) $word->id,
                'action_token' => $delivery->action_token,
                'deep_link' => 'glish://review/' . $word->id,
                'example' => $example,
            ],
        ];
    }
}