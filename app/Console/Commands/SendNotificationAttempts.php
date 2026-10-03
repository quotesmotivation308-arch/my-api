<?php

namespace App\Console\Commands;

use App\Models\NotificationDeliveryAttempt;
use App\Services\NotificationSenderService;
use Illuminate\Console\Command;

class SendNotificationAttempts extends Command
{
    protected $signature = 'notifications:send-attempts';

    protected $description = 'Send pending notification delivery attempts';

    public function handle(
        NotificationSenderService $sender
    ): int {
        $sent = 0;
        $failed = 0;

        NotificationDeliveryAttempt::query()
            ->where('status', 'pending')
            ->chunkById(100, function ($attempts) use (
                $sender,
                &$sent,
                &$failed
            ) {
                foreach ($attempts as $attempt) {
                    $result = $sender->sendAttempt($attempt);

                    if ($result->status === 'sent') {
                        $sent++;
                    } elseif ($result->status === 'failed') {
                        $failed++;
                    }
                }
            });

        $this->info("Sent: {$sent}");
        $this->info("Failed: {$failed}");

        return self::SUCCESS;
    }
}