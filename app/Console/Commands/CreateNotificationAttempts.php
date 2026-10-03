<?php

namespace App\Console\Commands;

use App\Models\NotificationDelivery;
use App\Services\NotificationDeliveryAttemptService;
use Illuminate\Console\Command;

class CreateNotificationAttempts extends Command
{
    protected $signature = 'notifications:create-attempts';

    protected $description = 'Create notification delivery attempts for pending notifications';

    public function handle(
        NotificationDeliveryAttemptService $service
    ): int {
        $total = 0;

        NotificationDelivery::query()
            ->where('status', 'pending')
            ->where('scheduled_for', '<=', now())
            ->chunkById(100, function ($deliveries) use ($service, &$total) {
                foreach ($deliveries as $delivery) {
                    $attempts = $service->createForDelivery($delivery);

                    $total += $attempts->count();
                }
            });

        $this->info("Created/Found {$total} notification attempts.");

        return self::SUCCESS;
    }
}