<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Contracts\Cache\Repository as CacheInterface;
use App\Gateway\ApiGatewayManager;
use App\Gateway\RateLimit\AdvancedRateLimiter;
use App\Gateway\Cache\GatewayCacheManager;
use App\Gateway\Cache\CacheInvalidator;
use App\Gateway\CircuitBreaker\CircuitBreaker;
use App\Gateway\Auth\AuthenticationGateway;
use App\Gateway\Discovery\ServiceRegistry;
use App\Gateway\Monitoring\GatewayMetrics;

class ApiGatewayServiceProvider extends ServiceProvider
{
    /**
     * Register services
     */
    public function register(): void
    {
        // Register gateway configuration
        $this->mergeConfigFrom(
            __DIR__ . '/../../config/gateway.php',
            'gateway'
        );

        // Register core gateway components
        $this->registerRateLimiter();
        $this->registerCacheManager();
        $this->registerCircuitBreaker();
        $this->registerAuthGateway();
        $this->registerServiceRegistry();
        $this->registerMetrics();
        $this->registerGatewayManager();
    }

    /**
     * Bootstrap services
     */
    public function boot(): void
    {
        // Publish configuration
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../../config/gateway.php' => config_path('gateway.php'),
            ], 'gateway-config');
        }

        // Register console commands
        if ($this->app->runningInConsole()) {
            $this->commands([
                \App\Console\Commands\GatewayStatusCommand::class,
                \App\Console\Commands\GatewayCacheClearCommand::class,
                \App\Console\Commands\GatewayMetricsCommand::class,
            ]);
        }

        // Register event listeners for cache invalidation
        $this->registerEventListeners();
    }

    /**
     * Register rate limiter
     */
    private function registerRateLimiter(): void
    {
        $this->app->singleton(AdvancedRateLimiter::class, function ($app) {
            return new AdvancedRateLimiter(
                $app->make(CacheInterface::class),
                config('gateway.rate_limiting', [])
            );
        });
    }

    /**
     * Register cache manager
     */
    private function registerCacheManager(): void
    {
        $this->app->singleton(CacheInvalidator::class, function ($app) {
            return new CacheInvalidator();
        });

        $this->app->singleton(GatewayCacheManager::class, function ($app) {
            return new GatewayCacheManager(
                config('gateway.caching', []),
                $app->make(CacheInvalidator::class)
            );
        });
    }

    /**
     * Register circuit breaker
     */
    private function registerCircuitBreaker(): void
    {
        $this->app->singleton(CircuitBreaker::class, function ($app) {
            return new CircuitBreaker(
                $app->make(CacheInterface::class),
                config('gateway.circuit_breaker', [])
            );
        });
    }

    /**
     * Register authentication gateway
     */
    private function registerAuthGateway(): void
    {
        $this->app->singleton(AuthenticationGateway::class, function ($app) {
            return new AuthenticationGateway(
                // Authentication providers would be injected here
            );
        });
    }

    /**
     * Register service registry
     */
    private function registerServiceRegistry(): void
    {
        $this->app->singleton(ServiceRegistry::class, function ($app) {
            $registry = new ServiceRegistry();
            
            // Register existing Laravel controllers as services
            $this->registerLaravelServices($registry);
            
            return $registry;
        });
    }

    /**
     * Register metrics collector
     */
    private function registerMetrics(): void
    {
        $this->app->singleton(GatewayMetrics::class, function ($app) {
            return new GatewayMetrics(
                config('gateway.monitoring', [])
            );
        });

        // Bind as alias for easier access
        $this->app->alias(GatewayMetrics::class, 'gateway.metrics');
    }

    /**
     * Register main gateway manager
     */
    private function registerGatewayManager(): void
    {
        $this->app->singleton(ApiGatewayManager::class, function ($app) {
            return new ApiGatewayManager(
                $app->make(ServiceRegistry::class),
                $app->make(AdvancedRateLimiter::class),
                $app->make(GatewayCacheManager::class),
                $app->make(CircuitBreaker::class),
                $app->make(AuthenticationGateway::class),
                $app->make(GatewayMetrics::class),
                config('gateway', [])
            );
        });
    }

    /**
     * Register Laravel controllers as services in the registry
     */
    private function registerLaravelServices(ServiceRegistry $registry): void
    {
        // Business Portal API services
        $businessApiServices = [
            'dashboard' => [
                'controller' => \App\Http\Controllers\Portal\Api\DashboardApiController::class,
                'endpoints' => [
                    ['pattern' => 'business/api/dashboard', 'methods' => ['GET']],
                ],
            ],
            'calls' => [
                'controller' => \App\Http\Controllers\Portal\Api\CallsApiController::class,
                'endpoints' => [
                    ['pattern' => 'business/api/calls', 'methods' => ['GET']],
                    ['pattern' => 'business/api/calls/{id}', 'methods' => ['GET']],
                    ['pattern' => 'business/api/calls/{id}/timeline', 'methods' => ['GET']],
                    ['pattern' => 'business/api/calls/export', 'methods' => ['POST']],
                ],
            ],
            'appointments' => [
                'controller' => \App\Http\Controllers\Portal\Api\AppointmentsApiController::class,
                'endpoints' => [
                    ['pattern' => 'business/api/appointments', 'methods' => ['GET', 'POST']],
                    ['pattern' => 'business/api/appointments/{id}', 'methods' => ['GET', 'PUT', 'DELETE']],
                    ['pattern' => 'business/api/appointments/calendar', 'methods' => ['GET']],
                ],
            ],
            'customers' => [
                'controller' => \App\Http\Controllers\Portal\Api\CustomersApiController::class,
                'endpoints' => [
                    ['pattern' => 'business/api/customers', 'methods' => ['GET']],
                    ['pattern' => 'business/api/customers/{id}', 'methods' => ['GET']],
                    ['pattern' => 'business/api/customers/{id}/timeline', 'methods' => ['GET']],
                ],
            ],
            'analytics' => [
                'controller' => \App\Http\Controllers\Portal\Api\AnalyticsApiController::class,
                'endpoints' => [
                    ['pattern' => 'business/api/analytics/*', 'methods' => ['GET']],
                ],
            ],
            'settings' => [
                'controller' => \App\Http\Controllers\Portal\Api\SettingsApiController::class,
                'endpoints' => [
                    ['pattern' => 'business/api/settings', 'methods' => ['GET']],
                    ['pattern' => 'business/api/settings/*', 'methods' => ['GET', 'PUT', 'POST']],
                ],
            ],
            'billing' => [
                'controller' => \App\Http\Controllers\Portal\Api\BillingApiController::class,
                'endpoints' => [
                    ['pattern' => 'business/api/billing', 'methods' => ['GET']],
                    ['pattern' => 'business/api/billing/*', 'methods' => ['GET', 'POST', 'PUT', 'DELETE']],
                ],
            ],
            'team' => [
                'controller' => \App\Http\Controllers\Portal\Api\TeamApiController::class,
                'endpoints' => [
                    ['pattern' => 'business/api/team', 'methods' => ['GET']],
                    ['pattern' => 'business/api/team/*', 'methods' => ['GET', 'POST', 'PUT', 'DELETE']],
                ],
            ],
        ];

        foreach ($businessApiServices as $name => $config) {
            $registry->register($name, new \App\Gateway\Discovery\ServiceDefinition(
                $name,
                'v1',
                $config['endpoints'],
                [], // health checks will be added later
                [], // load balancing config
                []  // circuit breaker config
            ));
        }
    }

    /**
     * Register event listeners for cache invalidation
     */
    private function registerEventListeners(): void
    {
        // Listen for model events to invalidate cache
        $events = [
            'eloquent.created: App\Models\Call' => 'calls.created',
            'eloquent.updated: App\Models\Call' => 'calls.updated',
            'eloquent.created: App\Models\Appointment' => 'appointments.created',
            'eloquent.updated: App\Models\Appointment' => 'appointments.updated',
            'eloquent.deleted: App\Models\Appointment' => 'appointments.cancelled',
            'eloquent.created: App\Models\Customer' => 'customers.created',
            'eloquent.updated: App\Models\Customer' => 'customers.updated',
            'eloquent.created: App\Models\Staff' => 'staff.created',
            'eloquent.updated: App\Models\Staff' => 'staff.updated',
            'eloquent.updated: App\Models\Company' => 'company.updated',
        ];

        foreach ($events as $laravelEvent => $gatewayEvent) {
            $this->app['events']->listen($laravelEvent, function () use ($gatewayEvent) {
                $this->app->make(CacheInvalidator::class)->invalidateByEvent($gatewayEvent);
            });
        }
    }

    /**
     * Get the services provided by the provider
     */
    public function provides(): array
    {
        return [
            ApiGatewayManager::class,
            AdvancedRateLimiter::class,
            GatewayCacheManager::class,
            CacheInvalidator::class,
            CircuitBreaker::class,
            AuthenticationGateway::class,
            ServiceRegistry::class,
            GatewayMetrics::class,
            'gateway.metrics',
        ];
    }
}