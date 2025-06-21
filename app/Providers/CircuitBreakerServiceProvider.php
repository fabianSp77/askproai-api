<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\CircuitBreaker\CircuitBreaker;
use App\Services\CircuitBreaker\CircuitBreakerService;

class CircuitBreakerServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        // Register circuit breaker for Cal.com
        $this->app->singleton('circuit.breaker.calcom', function () {
            return new CircuitBreaker(
                failureThreshold: config('services.calcom.circuit_breaker.failure_threshold', 5),
                successThreshold: config('services.calcom.circuit_breaker.success_threshold', 2),
                timeout: config('services.calcom.circuit_breaker.timeout', 60),
                halfOpenRequests: config('services.calcom.circuit_breaker.half_open_requests', 3)
            );
        });
        
        // Register circuit breaker for Retell.ai
        $this->app->singleton('circuit.breaker.retell', function () {
            return new CircuitBreaker(
                failureThreshold: config('services.retell.circuit_breaker.failure_threshold', 5),
                successThreshold: config('services.retell.circuit_breaker.success_threshold', 2),
                timeout: config('services.retell.circuit_breaker.timeout', 60),
                halfOpenRequests: config('services.retell.circuit_breaker.half_open_requests', 3)
            );
        });
        
        // Register circuit breaker for Stripe
        $this->app->singleton('circuit.breaker.stripe', function () {
            return new CircuitBreaker(
                failureThreshold: config('services.stripe.circuit_breaker.failure_threshold', 3),
                successThreshold: config('services.stripe.circuit_breaker.success_threshold', 2),
                timeout: config('services.stripe.circuit_breaker.timeout', 120),
                halfOpenRequests: config('services.stripe.circuit_breaker.half_open_requests', 2)
            );
        });
        
        // Register circuit breaker for Email service
        $this->app->singleton('circuit.breaker.email', function () {
            return new CircuitBreaker(
                failureThreshold: config('mail.circuit_breaker.failure_threshold', 10),
                successThreshold: config('mail.circuit_breaker.success_threshold', 3),
                timeout: config('mail.circuit_breaker.timeout', 300),
                halfOpenRequests: config('mail.circuit_breaker.half_open_requests', 5)
            );
        });
        
        // Register circuit breakers for MCP services
        $this->app->singleton('circuit.breaker.webhook', function () {
            return new CircuitBreaker(
                failureThreshold: 5,
                successThreshold: 2,
                timeout: 60,
                halfOpenRequests: 3
            );
        });
        
        $this->app->singleton('circuit.breaker.database', function () {
            return new CircuitBreaker(
                failureThreshold: 3,
                successThreshold: 1,
                timeout: 30,
                halfOpenRequests: 2
            );
        });
        
        $this->app->singleton('circuit.breaker.queue', function () {
            return new CircuitBreaker(
                failureThreshold: 5,
                successThreshold: 2,
                timeout: 60,
                halfOpenRequests: 3
            );
        });
        
        // Register the CircuitBreakerService
        $this->app->singleton(CircuitBreakerService::class, function () {
            return new CircuitBreakerService();
        });
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
}