<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Prometheus\CollectorRegistry;
use Prometheus\Storage\Redis;

class MetricsServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(CollectorRegistry::class, function ($app) {
            return new CollectorRegistry(new Redis([
                'host' => config('database.redis.default.host'),
                'port' => config('database.redis.default.port'),
                'password' => config('database.redis.default.password'),
                'database' => 2, // Use separate Redis DB for metrics
            ]));
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Register metrics collectors
        $this->registerMetrics();
        
        // Register middleware to collect HTTP metrics
        $this->app['router']->pushMiddlewareToGroup('api', \App\Http\Middleware\MetricsMiddleware::class);
    }

    /**
     * Register all metrics collectors
     */
    private function registerMetrics(): void
    {
        $registry = $this->app->make(CollectorRegistry::class);
        
        try {
            // HTTP request duration histogram
            $registry->getOrRegisterHistogram(
                'askproai',
                'http_request_duration_seconds',
                'The HTTP request latencies in seconds.',
                ['method', 'route', 'status_code']
            );
            
            // Security threats counter
            $registry->getOrRegisterCounter(
                'askproai',
                'security_threats_total',
                'Total number of security threats detected',
                ['type', 'ip']
            );
            
            // Rate limit violations counter
            $registry->getOrRegisterCounter(
                'askproai',
                'rate_limit_exceeded_total',
                'Total number of rate limit violations',
                ['endpoint', 'user_type']
            );
            
            // Queue metrics
            $registry->getOrRegisterGauge(
                'askproai',
                'queue_size',
                'Current size of job queues',
                ['queue']
            );
            
            // Active calls gauge
            $registry->getOrRegisterGauge(
                'askproai',
                'active_calls',
                'Number of active calls',
                ['agent_id']
            );
            
            // API response time by endpoint
            $registry->getOrRegisterHistogram(
                'askproai',
                'api_response_time_seconds',
                'API response time by endpoint',
                ['endpoint', 'method']
            );
        } catch (\Exception $e) {
            \Log::error('Failed to register Prometheus metrics: ' . $e->getMessage());
        }
    }
}