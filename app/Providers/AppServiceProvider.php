<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

use App\Services\ExpoPushNotificationProvider;
use App\Services\PushNotificationProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(
            PushNotificationProvider::class,
            ExpoPushNotificationProvider::class
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}