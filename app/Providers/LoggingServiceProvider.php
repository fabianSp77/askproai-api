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
        // Add correlation ID to all requests
        $this->app['router']->matched(function ($event) {
            $logger = $this->app->make(StructuredLogger::class);
            
            // Set correlation ID in response headers
            $correlationId = $logger->getCorrelationId();
            $event->response->header('X-Correlation-ID', $correlationId);
            
            // Add to log context
            \Log::shareContext([
                'correlation_id' => $correlationId
            ]);
        });
    }
}