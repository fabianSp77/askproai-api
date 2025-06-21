<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Services\Monitoring\HealthCheckService;
use App\Services\Monitoring\PerformanceMonitor;
use App\Services\Monitoring\SecurityMonitor;
use App\Services\Monitoring\AlertingService;

class MonitoringServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Register monitoring services as singletons
        $this->app->singleton(HealthCheckService::class);
        $this->app->singleton(PerformanceMonitor::class);
        $this->app->singleton(SecurityMonitor::class);
        $this->app->singleton(AlertingService::class);
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        if (!config('monitoring.enabled')) {
            return;
        }

        $this->configureLogging();
        $this->configureDatabaseMonitoring();
        $this->registerEventListeners();
    }

    /**
     * Configure custom logging channels
     */
    private function configureLogging(): void
    {
        // Add monitoring channel
        config(['logging.channels.monitoring' => [
            'driver' => 'daily',
            'path' => storage_path('logs/monitoring.log'),
            'level' => env('LOG_LEVEL_MONITORING', 'debug'),
            'days' => 14,
        ]]);

        // Add security channel
        config(['logging.channels.security' => [
            'driver' => 'daily',
            'path' => storage_path('logs/security.log'),
            'level' => env('LOG_LEVEL_SECURITY', 'warning'),
            'days' => 30,
        ]]);

        // Add performance channel
        config(['logging.channels.performance' => [
            'driver' => 'daily',
            'path' => storage_path('logs/performance.log'),
            'level' => env('LOG_LEVEL_PERFORMANCE', 'info'),
            'days' => 7,
        ]]);
    }

    /**
     * Configure database monitoring
     */
    private function configureDatabaseMonitoring(): void
    {
        if (!config('monitoring.apm.database.log_queries')) {
            return;
        }

        DB::listen(function ($query) {
            if ($query->time > config('monitoring.apm.database.slow_query_threshold', 100)) {
                Log::channel('performance')->warning('Slow query detected', [
                    'sql' => $query->sql,
                    'bindings' => $query->bindings,
                    'time' => $query->time,
                ]);
            }
        });
    }

    /**
     * Register event listeners for monitoring
     */
    private function registerEventListeners(): void
    {
        $events = app('events');
        $alerting = app(AlertingService::class);
        $security = app(SecurityMonitor::class);

        // Monitor authentication events
        $events->listen('Illuminate\Auth\Events\Failed', function ($event) use ($security) {
            $security->checkFailedLogin(request(), $event->credentials['email'] ?? 'unknown');
        });

        $events->listen('Illuminate\Auth\Events\Login', function ($event) use ($security) {
            $security->logEvent('successful_login', request(), [
                'user_id' => $event->user->id,
            ]);
        });

        // Monitor Stripe events
        $events->listen('App\Events\StripeWebhookReceived', function ($event) use ($alerting) {
            if ($event->type === 'payment_intent.payment_failed') {
                $alerting->recordEvent('payment_failure');
            }
        });

        $events->listen('App\Events\StripeWebhookFailed', function ($event) use ($alerting) {
            $alerting->recordEvent('stripe_webhook_failure');
        });

        // Monitor customer portal events
        $events->listen('App\Events\CustomerRegistered', function ($event) {
            cache()->increment('business_metrics:portal_registrations:' . date('Ymd'));
        });

        $events->listen('App\Events\CustomerLoggedIn', function ($event) {
            cache()->increment('business_metrics:portal_logins:' . date('Ymd'));
        });

        // Monitor subscription events
        $events->listen('App\Events\SubscriptionCreated', function ($event) {
            cache()->increment('business_metrics:subscriptions_created:' . date('Ymd'));
            cache()->increment('business_metrics:subscriptions_created');
        });

        $events->listen('App\Events\PaymentProcessed', function ($event) {
            $key = 'business_metrics:revenue_processed:' . date('Ymd');
            cache()->increment($key, $event->amount);
            cache()->increment('business_metrics:revenue_processed', $event->amount);
        });
    }
}