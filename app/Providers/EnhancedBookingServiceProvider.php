<?php

namespace App\Providers;

use App\Services\AvailabilityService;
use App\Services\Booking\EnhancedBookingService;
use App\Services\CalcomV2Service;
use App\Services\CacheService;
use App\Services\CircuitBreaker\CircuitBreaker;
use App\Services\Locking\TimeSlotLockManager;
use App\Services\Logging\StructuredLogger;
use App\Services\NotificationService;
use Illuminate\Support\ServiceProvider;

class EnhancedBookingServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Register the EnhancedBookingService as a singleton
        $this->app->singleton(EnhancedBookingService::class, function ($app) {
            // Get or create dependencies
            $lockManager = $app->make(TimeSlotLockManager::class);
            $circuitBreaker = $app->make(CircuitBreaker::class);
            $logger = $app->make(StructuredLogger::class);
            $calcomService = $app->make(CalcomV2Service::class);
            $notificationService = $app->make(NotificationService::class);
            
            // Create availability service with cache
            $cacheService = $app->make(CacheService::class);
            $availabilityService = new AvailabilityService($cacheService);

            return new EnhancedBookingService(
                $lockManager,
                $circuitBreaker,
                $logger,
                $calcomService,
                $notificationService,
                $availabilityService
            );
        });

        // Register the structured logger as a singleton
        $this->app->singleton(StructuredLogger::class, function ($app) {
            return new StructuredLogger();
        });

        // Register the circuit breaker as a singleton
        $this->app->singleton(CircuitBreaker::class, function ($app) {
            return new CircuitBreaker(
                failureThreshold: config('circuit_breaker.failure_threshold', 5),
                successThreshold: config('circuit_breaker.success_threshold', 2),
                timeout: config('circuit_breaker.timeout', 60),
                halfOpenRequests: config('circuit_breaker.half_open_requests', 3)
            );
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Add configuration for circuit breaker
        $this->mergeConfigFrom(
            __DIR__ . '/../../config/circuit_breaker.php',
            'circuit_breaker'
        );
    }
}