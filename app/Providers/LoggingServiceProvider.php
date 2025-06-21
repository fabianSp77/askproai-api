<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\Logging\StructuredLogger;

class LoggingServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Register StructuredLogger as a singleton
        $this->app->singleton(StructuredLogger::class, function ($app) {
            return new StructuredLogger();
        });

        // Register a helper alias for easy access
        $this->app->alias(StructuredLogger::class, 'structured.logger');
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // The CorrelationIdMiddleware handles correlation IDs
        // No need for additional logic here since it's registered as global middleware
    }
}