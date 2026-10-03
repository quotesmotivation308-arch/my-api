<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\NotificationDeliveryService;
use Illuminate\Console\Command;

class CreateDueNotificationDeliveries extends Command
{
    protected $signature = 'notifications:create-due';

    protected $description = 'Create notification deliveries for due learning words';

    public function handle(NotificationDeliveryService $service): int
    {
        $total = 0;

        User::query()
            ->whereHas('wordProgress', function ($query) {
                $query->where('status', 'learning')
                    ->whereNotNull('next_review_at')
                    ->where('next_review_at', '<=', now());
            })
            ->chunkById(100, function ($users) use ($service, &$total) {
                foreach ($users as $user) {
                    $deliveries = $service->createDueDeliveries($user->id);

                    $total += $deliveries->count();
                }
            });

        $this->info("Created/Found {$total} due notification deliveries.");

        return self::SUCCESS;
    }
}