<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class CircuitBreaker
{
    private string $name;
    private int $failureThreshold;
    private int $recoveryTimeout;
    private int $successThreshold;
    private array $metrics = [];
    
    // Circuit states
    const STATE_CLOSED = 'closed';
    const STATE_OPEN = 'open';
    const STATE_HALF_OPEN = 'half_open';
    
    public function __construct(
        string $name,
        int $failureThreshold = 5,
        int $recoveryTimeout = 60,
        int $successThreshold = 2
    ) {
        $this->name = $name;
        $this->failureThreshold = $failureThreshold;
        $this->recoveryTimeout = $recoveryTimeout;
        $this->successThreshold = $successThreshold;
    }
    
    /**
     * Execute action with circuit breaker protection
     */
    public function call(callable $action, ?callable $fallback = null)
    {
        $state = $this->getState();
        
        // Log state for monitoring
        $this->logMetric('state', $state);
        
        if ($state === self::STATE_OPEN) {
            if ($this->shouldAttemptReset()) {
                $this->transitionTo(self::STATE_HALF_OPEN);
                Log::info("Circuit breaker {$this->name} transitioning to half-open");
            } else {
                Log::warning("Circuit breaker {$this->name} is open, using fallback");
                $this->logMetric('rejected', 1);
                
                if ($fallback) {
                    return $fallback();
                }
                
                throw new CircuitOpenException(
                    "Circuit breaker {$this->name} is open. Service unavailable."
                );
            }
        }
        
        try {
            $startTime = microtime(true);
            $result = $action();
            $duration = (microtime(true) - $startTime) * 1000; // Convert to ms
            
            $this->onSuccess($duration);
            return $result;
            
        } catch (\Exception $e) {
            $this->onFailure($e);
            
            if ($fallback && $state === self::STATE_HALF_OPEN) {
                return $fallback();
            }
            
            throw $e;
        }
    }
    
    /**
     * Execute async action with circuit breaker
     */
    public function callAsync(callable $action, ?callable $fallback = null): \Illuminate\Http\Client\PendingRequest
    {
        return $this->call($action, $fallback);
    }
    
    /**
     * Handle successful execution
     */
    private function onSuccess(float $duration): void
    {
        $state = $this->getState();
        $successCount = $this->getSuccessCount();
        
        // Log success metrics
        $this->logMetric('success', 1);
        $this->logMetric('duration', $duration);
        
        if ($state === self::STATE_HALF_OPEN) {
            $successCount++;
            $this->setSuccessCount($successCount);
            
            if ($successCount >= $this->successThreshold) {
                $this->transitionTo(self::STATE_CLOSED);
                $this->resetCounters();
                Log::info("Circuit breaker {$this->name} closed after {$successCount} successes");
            }
        } elseif ($state === self::STATE_CLOSED) {
            // Reset failure count on success in closed state
            $this->setFailureCount(0);
        }
    }
    
    /**
     * Handle failed execution
     */
    private function onFailure(\Exception $exception): void
    {
        $state = $this->getState();
        $failureCount = $this->getFailureCount();
        
        // Log failure metrics
        $this->logMetric('failure', 1);
        $this->logMetric('error_type', get_class($exception));
        
        Log::error("Circuit breaker {$this->name} failure", [
            'state' => $state,
            'failure_count' => $failureCount + 1,
            'error' => $exception->getMessage()
        ]);
        
        if ($state === self::STATE_HALF_OPEN) {
            // Immediately open on failure in half-open state
            $this->transitionTo(self::STATE_OPEN);
            $this->setLastFailureTime(now());
            Log::warning("Circuit breaker {$this->name} reopened due to failure in half-open state");
            
        } elseif ($state === self::STATE_CLOSED) {
            $failureCount++;
            $this->setFailureCount($failureCount);
            
            if ($failureCount >= $this->failureThreshold) {
                $this->transitionTo(self::STATE_OPEN);
                $this->setLastFailureTime(now());
                Log::warning("Circuit breaker {$this->name} opened after {$failureCount} failures");
            }
        }
    }
    
    /**
     * Check if circuit should attempt reset
     */
    private function shouldAttemptReset(): bool
    {
        $lastFailureTime = $this->getLastFailureTime();
        
        if (!$lastFailureTime) {
            return true;
        }
        
        return $lastFailureTime->addSeconds($this->recoveryTimeout)->isPast();
    }
    
    /**
     * Get current circuit state
     */
    public function getState(): string
    {
        return Cache::get($this->getCacheKey('state'), self::STATE_CLOSED);
    }
    
    /**
     * Transition to new state
     */
    private function transitionTo(string $state): void
    {
        Cache::put($this->getCacheKey('state'), $state, now()->addHours(24));
        
        // Emit event for monitoring
        event(new CircuitBreakerStateChanged($this->name, $state));
    }
    
    /**
     * Get failure count
     */
    private function getFailureCount(): int
    {
        return Cache::get($this->getCacheKey('failures'), 0);
    }
    
    /**
     * Set failure count
     */
    private function setFailureCount(int $count): void
    {
        Cache::put($this->getCacheKey('failures'), $count, now()->addHours(1));
    }
    
    /**
     * Get success count (for half-open state)
     */
    private function getSuccessCount(): int
    {
        return Cache::get($this->getCacheKey('successes'), 0);
    }
    
    /**
     * Set success count
     */
    private function setSuccessCount(int $count): void
    {
        Cache::put($this->getCacheKey('successes'), $count, now()->addHours(1));
    }
    
    /**
     * Get last failure time
     */
    private function getLastFailureTime(): ?Carbon
    {
        $timestamp = Cache::get($this->getCacheKey('last_failure'));
        return $timestamp ? Carbon::parse($timestamp) : null;
    }
    
    /**
     * Set last failure time
     */
    private function setLastFailureTime(Carbon $time): void
    {
        Cache::put($this->getCacheKey('last_failure'), $time->toIso8601String(), now()->addHours(24));
    }
    
    /**
     * Reset all counters
     */
    private function resetCounters(): void
    {
        Cache::forget($this->getCacheKey('failures'));
        Cache::forget($this->getCacheKey('successes'));
        Cache::forget($this->getCacheKey('last_failure'));
    }
    
    /**
     * Get cache key for this circuit
     */
    private function getCacheKey(string $suffix): string
    {
        return "circuit_breaker.{$this->name}.{$suffix}";
    }
    
    /**
     * Log metric for monitoring
     */
    private function logMetric(string $metric, $value): void
    {
        $this->metrics[] = [
            'circuit' => $this->name,
            'metric' => $metric,
            'value' => $value,
            'timestamp' => microtime(true)
        ];
        
        // Send to monitoring system (Prometheus)
        if (class_exists('\App\Services\MetricsCollector')) {
            app(\App\Services\MetricsCollector::class)->record(
                "circuit_breaker_{$metric}",
                $value,
                ['circuit' => $this->name]
            );
        }
    }
    
    /**
     * Get current metrics
     */
    public function getMetrics(): array
    {
        return [
            'name' => $this->name,
            'state' => $this->getState(),
            'failure_count' => $this->getFailureCount(),
            'success_count' => $this->getSuccessCount(),
            'last_failure' => $this->getLastFailureTime()?->toIso8601String(),
            'thresholds' => [
                'failure' => $this->failureThreshold,
                'success' => $this->successThreshold,
                'recovery_timeout' => $this->recoveryTimeout
            ],
            'recent_metrics' => $this->metrics
        ];
    }
    
    /**
     * Manually reset the circuit (for admin override)
     */
    public function reset(): void
    {
        $this->transitionTo(self::STATE_CLOSED);
        $this->resetCounters();
        Log::info("Circuit breaker {$this->name} manually reset");
    }
    
    /**
     * Manually trip the circuit (for testing/maintenance)
     */
    public function trip(): void
    {
        $this->transitionTo(self::STATE_OPEN);
        $this->setLastFailureTime(now());
        Log::info("Circuit breaker {$this->name} manually tripped");
    }
}

class CircuitOpenException extends \Exception
{
    //
}

class CircuitBreakerStateChanged
{
    public string $circuit;
    public string $state;
    
    public function __construct(string $circuit, string $state)
    {
        $this->circuit = $circuit;
        $this->state = $state;
    }
}