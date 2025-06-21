<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\Cache\CacheManager;
use App\Services\Cache\CompanyCacheService;
use App\Services\Monitoring\MetricsCollectorService;
use App\Services\Webhook\WebhookDeduplication;
use App\Services\RateLimiter\EnhancedRateLimiter;
use App\Repositories\OptimizedAppointmentRepository;

class PerformanceServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Cache Services
        $this->app->singleton(CacheManager::class, function ($app) {
            return new CacheManager();
        });
        
        $this->app->singleton(CompanyCacheService::class, function ($app) {
            return new CompanyCacheService($app->make(CacheManager::class));
        });
        
        // Repository
        $this->app->singleton(OptimizedAppointmentRepository::class, function ($app) {
            return new OptimizedAppointmentRepository();
        });
        
        // Webhook Services
        $this->app->singleton(WebhookDeduplication::class, function ($app) {
            return new WebhookDeduplication();
        });
        
        $this->app->singleton(EnhancedRateLimiter::class, function ($app) {
            return new EnhancedRateLimiter();
        });
        
        // Monitoring
        $this->app->singleton(MetricsCollectorService::class, function ($app) {
            return new MetricsCollectorService();
        });
        
        // Alias for backward compatibility
        $this->app->alias(MetricsCollectorService::class, 'App\Services\Monitoring\MetricsCollector');
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Register console commands
        if ($this->app->runningInConsole()) {
            $this->commands([
                \App\Console\Commands\PerformanceBaseline::class,
            ]);
        }
    }
}