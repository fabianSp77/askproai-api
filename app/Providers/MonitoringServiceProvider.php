<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\QueryPerformanceMonitor;

class MonitoringServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Register QueryPerformanceMonitor as singleton
        $this->app->singleton(QueryPerformanceMonitor::class, function ($app) {
            return new QueryPerformanceMonitor();
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Apply query performance monitoring to admin routes in debug mode
        // TEMPORARILY DISABLED - causing issues with business portal
        // if (config('app.debug') || config('monitoring.query_performance')) {
        //     $this->app['router']->pushMiddlewareToGroup('web', 'query.performance');
        // }
    }
}