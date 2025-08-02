<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\CalcomService;
use App\Services\CalcomV2Service;
use App\Services\Calcom\CalcomBackwardsCompatibility;
use Illuminate\Support\Facades\Schema;

class CalcomMigrationServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Bind CalcomService to backwards compatibility layer
        $this->app->bind(CalcomService::class, function ($app) {
            // Log that we're using backwards compatibility
            \Log::info('CalcomService requested - using backwards compatibility layer');
            
            // Return backwards compatibility instance with container injection
            return new CalcomBackwardsCompatibility($app);
        });
        
        // Register V2 service as singleton
        $this->app->singleton(CalcomV2Service::class, function ($app) {
            return new CalcomV2Service();
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Add config for migration
        $this->mergeConfigFrom(
            __DIR__ . '/../../config/calcom-migration.php',
            'calcom-migration'
        );
        
        // Log migration status on boot
        if (config('calcom-migration.log_status', false)) {
            $this->logMigrationStatus();
        }
    }
    
    /**
     * Log current migration status
     */
    private function logMigrationStatus(): void
    {
        // Skip if we're in testing environment
        if (app()->runningUnitTests()) {
            return;
        }
        
        // Skip if logs table doesn't exist - use try/catch for safety
        try {
            if (!Schema::hasTable('logs')) {
                return;
            }
            
            $v1Usage = \DB::table('logs')
                ->where('message', 'like', '%Cal.com V1 API usage detected%')
                ->where('created_at', '>=', now()->subDay())
                ->count();
        } catch (\Exception $e) {
            // If any database error occurs, just skip logging
            return;
        }
            
        \Log::info('Cal.com Migration Status', [
            'v1_calls_last_24h' => $v1Usage,
            'v2_enabled' => !CalcomBackwardsCompatibility::shouldUseV1(),
            'force_v2' => config('services.calcom.force_v2', false),
            'v2_rollout_percentage' => config('services.calcom.v2_rollout_percentage', 100)
        ]);
    }
}