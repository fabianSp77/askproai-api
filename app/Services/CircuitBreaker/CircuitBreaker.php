<?php

namespace App\Services\CircuitBreaker;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use App\Exceptions\CircuitBreakerOpenException;

class CircuitBreaker
{
    private int $failureThreshold;
    private int $successThreshold;
    private int $timeout;
    private int $halfOpenRequests;
    
    public function __construct(
        int $failureThreshold = 5,
        int $successThreshold = 2,
        int $timeout = 60,
        int $halfOpenRequests = 3
    ) {
        $this->failureThreshold = $failureThreshold;
        $this->successThreshold = $successThreshold;
        $this->timeout = $timeout;
        $this->halfOpenRequests = $halfOpenRequests;
    }
    
    /**
     * Execute operation with circuit breaker protection
     */
    public function call(string $service, callable $operation, ?callable $fallback = null)
    {
        $state = $this->getState($service);
        
        Log::debug('Circuit breaker state', [
            'service' => $service,
            'state' => $state,
            'failures' => $this->getFailureCount($service),
        ]);
        
        switch ($state) {
            case CircuitState::OPEN:
                if ($fallback) {
                    Log::info('Circuit breaker open, using fallback', ['service' => $service]);
                    return $fallback();
                }
                throw new CircuitBreakerOpenException("Service {$service} is currently unavailable");
                
            case CircuitState::HALF_OPEN:
                if (!$this->canMakeHalfOpenRequest($service)) {
                    if ($fallback) {
                        return $fallback();
                    }
                    throw new CircuitBreakerOpenException("Service {$service} is in half-open state, limit reached");
                }
                break;
        }
        
        try {
            $startTime = microtime(true);
            $result = $operation();
            $duration = microtime(true) - $startTime;
            
            $this->recordSuccess($service, $duration);
            
            return $result;
        } catch (\Exception $e) {
            $this->recordFailure($service, $e);
            
            if ($fallback && $this->getState($service) === CircuitState::OPEN) {
                Log::warning('Operation failed, circuit opened, using fallback', [
                    'service' => $service,
                    'error' => $e->getMessage(),
                ]);
                return $fallback();
            }
            
            throw $e;
        }
    }
    
    /**
     * Get current circuit state
     */
    private function getState(string $service): string
    {
        $failures = $this->getFailureCount($service);
        $lastFailureTime = $this->getLastFailureTime($service);
        
        // Check if circuit should be open
        if ($failures >= $this->failureThreshold) {
            // Check if timeout has passed
            if ($lastFailureTime && (time() - $lastFailureTime) > $this->timeout) {
                return CircuitState::HALF_OPEN;
            }
            return CircuitState::OPEN;
        }
        
        return CircuitState::CLOSED;
    }
    
    /**
     * Record successful operation
     */
    private function recordSuccess(string $service, float $duration): void
    {
        $state = $this->getState($service);
        
        if ($state === CircuitState::HALF_OPEN) {
            $successes = $this->incrementSuccessCount($service);
            
            Log::info('Circuit breaker half-open success', [
                'service' => $service,
                'successes' => $successes,
                'threshold' => $this->successThreshold,
            ]);
            
            // Close circuit if success threshold reached
            if ($successes >= $this->successThreshold) {
                $this->resetCircuit($service);
                Log::info('Circuit breaker closed', ['service' => $service]);
            }
        }
        
        // Track metrics
        $this->recordMetrics($service, true, $duration);
    }
    
    /**
     * Record failed operation
     */
    private function recordFailure(string $service, \Exception $exception): void
    {
        $failures = $this->incrementFailureCount($service);
        $this->setLastFailureTime($service, time());
        
        // Reset half-open success count on failure
        if ($this->getState($service) === CircuitState::HALF_OPEN) {
            $this->resetSuccessCount($service);
        }
        
        Log::warning('Circuit breaker failure recorded', [
            'service' => $service,
            'failures' => $failures,
            'threshold' => $this->failureThreshold,
            'error' => $exception->getMessage(),
        ]);
        
        // Track metrics
        $this->recordMetrics($service, false, 0);
    }
    
    /**
     * Check if we can make a half-open request
     */
    private function canMakeHalfOpenRequest(string $service): bool
    {
        $key = "circuit_breaker.{$service}.half_open_requests";
        $current = Cache::get($key, 0);
        
        if ($current >= $this->halfOpenRequests) {
            return false;
        }
        
        Cache::put($key, $current + 1, $this->timeout);
        return true;
    }
    
    /**
     * Reset circuit to closed state
     */
    private function resetCircuit(string $service): void
    {
        Cache::forget("circuit_breaker.{$service}.failures");
        Cache::forget("circuit_breaker.{$service}.last_failure");
        Cache::forget("circuit_breaker.{$service}.successes");
        Cache::forget("circuit_breaker.{$service}.half_open_requests");
    }
    
    // Cache helper methods
    
    private function getFailureCount(string $service): int
    {
        return Cache::get("circuit_breaker.{$service}.failures", 0);
    }
    
    private function incrementFailureCount(string $service): int
    {
        $key = "circuit_breaker.{$service}.failures";
        $failures = Cache::increment($key);
        Cache::put($key, $failures, $this->timeout * 2);
        return $failures;
    }
    
    private function getLastFailureTime(string $service): ?int
    {
        return Cache::get("circuit_breaker.{$service}.last_failure");
    }
    
    private function setLastFailureTime(string $service, int $time): void
    {
        Cache::put("circuit_breaker.{$service}.last_failure", $time, $this->timeout * 2);
    }
    
    private function incrementSuccessCount(string $service): int
    {
        $key = "circuit_breaker.{$service}.successes";
        return Cache::increment($key);
    }
    
    private function resetSuccessCount(string $service): void
    {
        Cache::forget("circuit_breaker.{$service}.successes");
    }
    
    /**
     * Check if circuit is open for a service
     */
    public function isOpen(string $service): bool
    {
        return $this->getState($service) === CircuitState::OPEN;
    }
    
    /**
     * Record metrics for monitoring
     */
    private function recordMetrics(string $service, bool $success, float $duration): void
    {
        $status = $success ? 'success' : 'failure';
        $state = $this->getState($service);
        
        // Store in database for monitoring
        \DB::table('circuit_breaker_metrics')->insert([
            'service' => $service,
            'status' => $status,
            'state' => $state,
            'duration_ms' => round($duration * 1000, 2),
            'created_at' => now(),
        ]);
    }
    
    /**
     * Get circuit breaker status for all services
     */
    public static function getStatus(): array
    {
        $services = ['calcom', 'retell', 'stripe'];
        $status = [];
        
        foreach ($services as $service) {
            $breaker = new self();
            $status[$service] = [
                'state' => $breaker->getState($service),
                'failures' => $breaker->getFailureCount($service),
                'last_failure' => $breaker->getLastFailureTime($service),
            ];
        }
        
        return $status;
    }
}