<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Closure;

/**
 * Circuit Breaker Pattern Implementation
 *
 * Protects against cascading failures when external services (like Cal.com API) are down.
 *
 * States:
 * - CLOSED: Normal operation, requests pass through
 * - OPEN: Too many failures, requests are blocked
 * - HALF_OPEN: Testing if service recovered
 *
 * Usage:
 *   $breaker = new CircuitBreaker('calcom_api');
 *   $result = $breaker->call(function() {
 *       return $this->calcomService->getAvailableSlots(...);
 *   });
 */
class CircuitBreaker
{
    private const STATE_CLOSED = 'closed';
    private const STATE_OPEN = 'open';
    private const STATE_HALF_OPEN = 'half_open';

    private string $serviceName;
    private int $failureThreshold;
    private int $recoveryTimeout;
    private int $successThreshold;

    /**
     * @param string $serviceName Unique identifier for the protected service
     * @param int $failureThreshold Number of consecutive failures before opening circuit (default: 5)
     * @param int $recoveryTimeout Seconds to wait before attempting recovery (default: 60)
     * @param int $successThreshold Successful calls needed to close circuit (default: 2)
     */
    public function __construct(
        string $serviceName,
        int $failureThreshold = 5,
        int $recoveryTimeout = 60,
        int $successThreshold = 2
    ) {
        $this->serviceName = $serviceName;
        $this->failureThreshold = $failureThreshold;
        $this->recoveryTimeout = $recoveryTimeout;
        $this->successThreshold = $successThreshold;
    }

    /**
     * Execute callable through circuit breaker
     *
     * @param Closure $callable Function to execute
     * @return mixed Result from callable
     * @throws CircuitBreakerOpenException When circuit is open
     */
    public function call(Closure $callable)
    {
        $state = $this->getState();

        // If circuit is OPEN, deny request immediately
        if ($state === self::STATE_OPEN) {
            if ($this->shouldAttemptRecovery()) {
                $this->transitionToHalfOpen();
            } else {
                throw new CircuitBreakerOpenException(
                    "Circuit breaker is OPEN for service '{$this->serviceName}'. " .
                    "Service appears to be down. Retry after {$this->recoveryTimeout} seconds."
                );
            }
        }

        // Attempt to execute the callable
        try {
            $result = $callable();

            // Success - record it
            $this->recordSuccess();

            return $result;

        } catch (\Throwable $e) {
            // Failure - record it
            $this->recordFailure();

            // Re-throw original exception
            throw $e;
        }
    }

    /**
     * Get current circuit breaker state
     */
    public function getState(): string
    {
        return Cache::get($this->getStateKey(), self::STATE_CLOSED);
    }

    /**
     * Get current failure count
     */
    public function getFailureCount(): int
    {
        return (int) Cache::get($this->getFailureKey(), 0);
    }

    /**
     * Get current success count (for half-open state)
     */
    public function getSuccessCount(): int
    {
        return (int) Cache::get($this->getSuccessKey(), 0);
    }

    /**
     * Check if circuit breaker is open
     */
    public function isOpen(): bool
    {
        return $this->getState() === self::STATE_OPEN;
    }

    /**
     * Manually reset circuit breaker (admin operation)
     */
    public function reset(): void
    {
        Cache::forget($this->getStateKey());
        Cache::forget($this->getFailureKey());
        Cache::forget($this->getSuccessKey());
        Cache::forget($this->getOpenedAtKey());

        Log::info("Circuit breaker manually reset", [
            'service' => $this->serviceName
        ]);
    }

    /**
     * Get circuit breaker status for monitoring
     */
    public function getStatus(): array
    {
        $state = $this->getState();
        $openedAt = Cache::get($this->getOpenedAtKey());

        return [
            'service' => $this->serviceName,
            'state' => $state,
            'failure_count' => $this->getFailureCount(),
            'success_count' => $this->getSuccessCount(),
            'failure_threshold' => $this->failureThreshold,
            'recovery_timeout' => $this->recoveryTimeout,
            'opened_at' => $openedAt,
            'seconds_until_retry' => $state === self::STATE_OPEN && $openedAt
                ? max(0, $this->recoveryTimeout - (time() - $openedAt))
                : null,
        ];
    }

    /**
     * Record successful call
     */
    private function recordSuccess(): void
    {
        $state = $this->getState();

        if ($state === self::STATE_HALF_OPEN) {
            // Increment success counter
            $successCount = $this->getSuccessCount() + 1;
            Cache::put($this->getSuccessKey(), $successCount, 300);

            // If enough successes, close the circuit
            if ($successCount >= $this->successThreshold) {
                $this->transitionToClosed();
            }

            Log::info("Circuit breaker test success", [
                'service' => $this->serviceName,
                'success_count' => $successCount,
                'threshold' => $this->successThreshold
            ]);

        } elseif ($state === self::STATE_CLOSED) {
            // Reset failure counter on success
            Cache::forget($this->getFailureKey());
        }
    }

    /**
     * Record failed call
     */
    private function recordFailure(): void
    {
        $state = $this->getState();

        if ($state === self::STATE_HALF_OPEN) {
            // Failure during recovery test - reopen circuit
            $this->transitionToOpen();

            Log::warning("Circuit breaker recovery test failed", [
                'service' => $this->serviceName
            ]);

        } elseif ($state === self::STATE_CLOSED) {
            // Increment failure counter
            $failureCount = $this->getFailureCount() + 1;
            Cache::put($this->getFailureKey(), $failureCount, 300);

            Log::warning("Circuit breaker failure recorded", [
                'service' => $this->serviceName,
                'failure_count' => $failureCount,
                'threshold' => $this->failureThreshold
            ]);

            // If threshold exceeded, open the circuit
            if ($failureCount >= $this->failureThreshold) {
                $this->transitionToOpen();
            }
        }
    }

    /**
     * Check if enough time has passed to attempt recovery
     */
    private function shouldAttemptRecovery(): bool
    {
        $openedAt = Cache::get($this->getOpenedAtKey());

        if (!$openedAt) {
            return true; // No opened time recorded, allow recovery attempt
        }

        $elapsedSeconds = time() - $openedAt;

        return $elapsedSeconds >= $this->recoveryTimeout;
    }

    /**
     * Transition circuit breaker to CLOSED state
     */
    private function transitionToClosed(): void
    {
        Cache::put($this->getStateKey(), self::STATE_CLOSED, 86400);
        Cache::forget($this->getFailureKey());
        Cache::forget($this->getSuccessKey());
        Cache::forget($this->getOpenedAtKey());

        Log::info("Circuit breaker CLOSED (service recovered)", [
            'service' => $this->serviceName
        ]);
    }

    /**
     * Transition circuit breaker to OPEN state
     */
    private function transitionToOpen(): void
    {
        Cache::put($this->getStateKey(), self::STATE_OPEN, 86400);
        Cache::put($this->getOpenedAtKey(), time(), 86400);
        Cache::forget($this->getSuccessKey());

        Log::critical("Circuit breaker OPEN (service down)", [
            'service' => $this->serviceName,
            'failure_count' => $this->getFailureCount(),
            'recovery_timeout' => $this->recoveryTimeout
        ]);
    }

    /**
     * Transition circuit breaker to HALF_OPEN state
     */
    private function transitionToHalfOpen(): void
    {
        Cache::put($this->getStateKey(), self::STATE_HALF_OPEN, 86400);
        Cache::forget($this->getSuccessKey());

        Log::info("Circuit breaker HALF_OPEN (testing recovery)", [
            'service' => $this->serviceName
        ]);
    }

    /**
     * Cache key for circuit breaker state
     */
    private function getStateKey(): string
    {
        return "circuit_breaker:{$this->serviceName}:state";
    }

    /**
     * Cache key for failure count
     */
    private function getFailureKey(): string
    {
        return "circuit_breaker:{$this->serviceName}:failures";
    }

    /**
     * Cache key for success count
     */
    private function getSuccessKey(): string
    {
        return "circuit_breaker:{$this->serviceName}:successes";
    }

    /**
     * Cache key for opened timestamp
     */
    private function getOpenedAtKey(): string
    {
        return "circuit_breaker:{$this->serviceName}:opened_at";
    }
}

/**
 * Exception thrown when circuit breaker is open
 */
class CircuitBreakerOpenException extends \Exception
{
}
