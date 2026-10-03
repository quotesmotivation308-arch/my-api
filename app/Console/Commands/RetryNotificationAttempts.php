<?php

namespace App\Console\Commands;

use App\Models\NotificationDeliveryAttempt;
use App\Services\NotificationRetryService;
use Illuminate\Console\Command;

class RetryNotificationAttempts extends Command
{
    protected $signature = 'notifications:retry-attempts';

    protected $description = 'Retry failed notification delivery attempts';

    public function handle(
        NotificationRetryService $retryService
    ): int {
        $retried = 0;

        NotificationDeliveryAttempt::query()
            ->where('status', 'failed')
            ->whereHas('notificationDelivery', function ($query) {
                $query->where('status', 'pending');
            })
            ->chunkById(100, function ($attempts) use (
                $retryService,
                &$retried
            ) {
                foreach ($attempts as $attempt) {
                    $result = $retryService->retry($attempt);

                    if ($result->status === 'pending') {
                        $retried++;
                    }
                }
            });

        $this->info("Retried: {$retried}");

        return self::SUCCESS;
    }
}