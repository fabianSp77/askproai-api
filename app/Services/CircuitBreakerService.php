<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

/**
 * Circuit Breaker Service for External API Resilience
 * 
 * Implements circuit breaker pattern for:
 * - Google Calendar API calls
 * - Webhook delivery
 * - External integrations
 * - Automatic failure recovery
 */
class CircuitBreakerService
{
    protected const STATE_CLOSED = 'closed';
    protected const STATE_OPEN = 'open';
    protected const STATE_HALF_OPEN = 'half_open';
    
    protected string $service;
    protected int $failureThreshold;
    protected int $timeout;
    protected int $retryTimeout;
    
    public function __construct(
        string $service,
        int $failureThreshold = 5,
        int $timeout = 60,
        int $retryTimeout = 300
    ) {
        $this->service = $service;
        $this->failureThreshold = $failureThreshold;
        $this->timeout = $timeout;
        $this->retryTimeout = $retryTimeout;
    }
    
    /**
     * Execute a callable with circuit breaker protection
     */
    public function call(callable $callback, array $fallback = null)
    {
        $state = $this->getState();
        
        switch ($state) {
            case self::STATE_OPEN:
                if ($this->shouldAttemptReset()) {
                    $this->setState(self::STATE_HALF_OPEN);
                    return $this->executeCall($callback, $fallback);
                }
                
                Log::warning('Circuit breaker OPEN for service: ' . $this->service);
                return $this->handleFailure('Circuit breaker is OPEN', $fallback);
                
            case self::STATE_HALF_OPEN:
            case self::STATE_CLOSED:
            default:
                return $this->executeCall($callback, $fallback);
        }
    }
    
    /**
     * Execute the actual call with monitoring
     */
    protected function executeCall(callable $callback, array $fallback = null)
    {
        $startTime = microtime(true);
        
        try {
            $result = $callback();
            
            // Success - reset failure count and close circuit
            $this->onSuccess();
            
            $duration = round((microtime(true) - $startTime) * 1000, 2);
            Log::info('Circuit breaker call succeeded', [
                'service' => $this->service,
                'duration_ms' => $duration
            ]);
            
            return $result;
            
        } catch (\Exception $e) {
            $duration = round((microtime(true) - $startTime) * 1000, 2);
            
            Log::error('Circuit breaker call failed', [
                'service' => $this->service,
                'error' => $e->getMessage(),
                'duration_ms' => $duration
            ]);
            
            $this->onFailure($e);
            
            return $this->handleFailure($e->getMessage(), $fallback);
        }
    }
    
    /**
     * Handle successful call
     */
    protected function onSuccess(): void
    {
        $this->resetFailures();
        $this->setState(self::STATE_CLOSED);
    }
    
    /**
     * Handle failed call
     */
    protected function onFailure(\Exception $e): void
    {
        $failures = $this->incrementFailures();
        
        if ($failures >= $this->failureThreshold) {
            $this->setState(self::STATE_OPEN);
            $this->setOpenedAt(now());
            
            Log::warning('Circuit breaker opened due to failures', [
                'service' => $this->service,
                'failures' => $failures,
                'threshold' => $this->failureThreshold,
                'error' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Handle failure with fallback
     */
    protected function handleFailure(string $error, array $fallback = null)
    {
        if ($fallback) {
            Log::info('Using fallback for failed circuit breaker call', [
                'service' => $this->service,
                'error' => $error
            ]);
            return $fallback;
        }
        
        throw new \Exception("Service unavailable: {$this->service}. Error: {$error}");
    }
    
    /**
     * Check if circuit breaker should attempt reset
     */
    protected function shouldAttemptReset(): bool
    {
        $openedAt = $this->getOpenedAt();
        
        if (!$openedAt) {
            return true;
        }
        
        return $openedAt->addSeconds($this->retryTimeout)->isPast();
    }
    
    /**
     * Get current state
     */
    public function getState(): string
    {
        return Cache::get($this->getStateKey(), self::STATE_CLOSED);
    }
    
    /**
     * Set circuit breaker state
     */
    protected function setState(string $state): void
    {
        Cache::put($this->getStateKey(), $state, 3600);
    }
    
    /**
     * Get failure count
     */
    protected function getFailures(): int
    {
        return Cache::get($this->getFailuresKey(), 0);
    }
    
    /**
     * Increment failure count
     */
    protected function incrementFailures(): int
    {
        $key = $this->getFailuresKey();
        $failures = Cache::get($key, 0) + 1;
        Cache::put($key, $failures, 3600);
        return $failures;
    }
    
    /**
     * Reset failure count
     */
    protected function resetFailures(): void
    {
        Cache::forget($this->getFailuresKey());
    }
    
    /**
     * Get when circuit was opened
     */
    protected function getOpenedAt(): ?Carbon
    {
        $timestamp = Cache::get($this->getOpenedAtKey());
        return $timestamp ? Carbon::parse($timestamp) : null;
    }
    
    /**
     * Set when circuit was opened
     */
    protected function setOpenedAt(Carbon $time): void
    {
        Cache::put($this->getOpenedAtKey(), $time->toIso8601String(), 3600);
    }
    
    /**
     * Get cache keys
     */
    protected function getStateKey(): string
    {
        return "circuit_breaker_state_{$this->service}";
    }
    
    protected function getFailuresKey(): string
    {
        return "circuit_breaker_failures_{$this->service}";
    }
    
    protected function getOpenedAtKey(): string
    {
        return "circuit_breaker_opened_at_{$this->service}";
    }
    
    /**
     * Get circuit breaker metrics
     */
    public function getMetrics(): array
    {
        return [
            'service' => $this->service,
            'state' => $this->getState(),
            'failures' => $this->getFailures(),
            'failure_threshold' => $this->failureThreshold,
            'opened_at' => $this->getOpenedAt()?->toIso8601String(),
            'retry_timeout_seconds' => $this->retryTimeout,
            'is_healthy' => $this->getState() === self::STATE_CLOSED
        ];
    }
    
    /**
     * Reset circuit breaker to closed state
     */
    public function reset(): void
    {
        $this->setState(self::STATE_CLOSED);
        $this->resetFailures();
        Cache::forget($this->getOpenedAtKey());
        
        Log::info('Circuit breaker manually reset', [
            'service' => $this->service
        ]);
    }
    
    /**
     * Create circuit breaker for Google Calendar
     */
    public static function forGoogleCalendar(): self
    {
        return new self(
            service: 'google_calendar',
            failureThreshold: 3,
            timeout: 30,
            retryTimeout: 60
        );
    }
    
    /**
     * Create circuit breaker for webhook delivery
     */
    public static function forWebhooks(): self
    {
        return new self(
            service: 'webhooks',
            failureThreshold: 5,
            timeout: 10,
            retryTimeout: 300
        );
    }
    
    /**
     * Create circuit breaker for Retell API
     */
    public static function forRetellApi(): self
    {
        return new self(
            service: 'retell_api',
            failureThreshold: 3,
            timeout: 15,
            retryTimeout: 120
        );
    }
}