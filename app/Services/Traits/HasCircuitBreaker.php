<?php

namespace App\Services\Traits;

use App\Services\CircuitBreaker\CircuitBreakerManager;
use Illuminate\Support\Facades\Log;

/**
 * Trait to add circuit breaker functionality to any service
 */
trait HasCircuitBreaker
{
    protected ?CircuitBreakerManager $circuitBreakerManager = null;
    
    /**
     * Get the circuit breaker manager instance
     */
    protected function getCircuitBreaker(): CircuitBreakerManager
    {
        if ($this->circuitBreakerManager === null) {
            $this->circuitBreakerManager = CircuitBreakerManager::getInstance();
        }
        
        return $this->circuitBreakerManager;
    }
    
    /**
     * Execute operation with circuit breaker protection
     */
    protected function withCircuitBreaker(string $service, callable $operation, ?callable $fallback = null)
    {
        return $this->getCircuitBreaker()->call($service, $operation, $fallback);
    }
    
    /**
     * Check if service is available
     */
    protected function isServiceAvailable(string $service): bool
    {
        return $this->getCircuitBreaker()->isAvailable($service);
    }
    
    /**
     * Log circuit breaker event
     */
    protected function logCircuitBreakerEvent(string $service, string $event, array $context = []): void
    {
        Log::info("Circuit breaker {$event}", array_merge([
            'service' => $service,
            'class' => static::class,
        ], $context));
    }
    
    /**
     * Get health status for a service
     */
    protected function getServiceHealthStatus(string $service): array
    {
        $allStatus = $this->getCircuitBreaker()->getAllStatus();
        return $allStatus[$service] ?? ['available' => false, 'state' => 'unknown'];
    }
    
    /**
     * Create a fallback response for when service is unavailable
     */
    protected function createFallbackResponse(string $service, \Exception $exception, $defaultValue = null)
    {
        $this->logCircuitBreakerEvent($service, 'fallback_used', [
            'error' => $exception->getMessage(),
            'default_value' => $defaultValue
        ]);
        
        return $defaultValue;
    }
}