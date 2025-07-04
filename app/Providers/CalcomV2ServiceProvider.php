<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\CalcomV2Service;
use App\Services\Calcom\CalcomV2MigrationAdapter;
use App\Services\Calcom\CalcomV2Client;

class CalcomV2ServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Bind the V2 client as singleton
        $this->app->singleton(CalcomV2Client::class, function ($app) {
            return new CalcomV2Client();
        });
        
        // Override CalcomV2Service with migration adapter if V2 is enabled
        if (config('calcom-v2.enabled', false)) {
            $this->app->bind(CalcomV2Service::class, CalcomV2MigrationAdapter::class);
        }
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Log Cal.com V2 migration status
        if (config('calcom-v2.enabled', false)) {
            \Log::info('Cal.com V2 Migration Adapter active', [
                'enabled_methods' => array_keys(array_filter(config('calcom-v2.enabled_methods', [])))
            ]);
        }
    }
}