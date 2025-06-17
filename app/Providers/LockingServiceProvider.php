<?php

namespace App\Providers;

use App\Services\Locking\TimeSlotLockManager;
use Illuminate\Support\ServiceProvider;

class LockingServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Register TimeSlotLockManager as singleton
        $this->app->singleton(TimeSlotLockManager::class, function ($app) {
            return new TimeSlotLockManager();
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}