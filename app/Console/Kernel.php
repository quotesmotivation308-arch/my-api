<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        $schedule->command('notifications:create-due')
            ->everyMinute();

        $schedule->command('notifications:create-attempts')
            ->everyMinute();

        $schedule->command('notifications:retry-attempts')
            ->everyMinute();

        $schedule->command('notifications:send-attempts')
            ->everyMinute();
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}