<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\DB;
use App\Services\Database\ConnectionPoolManager;
use Illuminate\Support\Facades\Log;

class DatabasePoolServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Only enable connection pooling in production or if explicitly enabled
        // Connection pooling is now always enabled for stability
        if (!config('database.pool.enabled', false)) {
            return;
        }

        try {
            // Initialize connection pool manager
            $poolManager = ConnectionPoolManager::getInstance();
            
            // Configure connection pool settings
            $poolConfig = config('database.pool', [
                'min_connections' => 5,
                'max_connections' => 50,
                'max_idle_time' => 300,
                'health_check_interval' => 60,
            ]);
            
            // Apply configuration
            $poolManager->setConfig($poolConfig);
            
            // Initialize pools with minimum connections
            if (!app()->runningInConsole()) {
                app()->booted(function () use ($poolManager) {
                    try {
                        $poolManager->initialize();
                        Log::info('Database connection pooling initialized', [
                            'config' => $poolManager->getConfig(),
                            'stats' => $poolManager->getStats()
                        ]);
                    } catch (\Exception $e) {
                        Log::warning('Failed to initialize connection pooling', [
                            'error' => $e->getMessage()
                        ]);
                    }
                });
            }
            
            // Register cleanup on shutdown
            register_shutdown_function(function () use ($poolManager) {
                try {
                    // Check if app is still available before cleanup
                    if (app() && !app()->isDownForMaintenance()) {
                        $poolManager->cleanup();
                    }
                } catch (\Exception $e) {
                    // Silently ignore cleanup errors during shutdown
                }
            });
            
        } catch (\Exception $e) {
            Log::error('Database pool service provider failed', [
                'error' => $e->getMessage()
            ]);
        }
    }
}