<?php

namespace App\Services\Resilience;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Exception;

/**
 * Distributed Circuit Breaker - Multi-server resilience pattern
 *
 * Implements circuit breaker pattern with Redis state sharing:
 *
 * States:
 * - CLOSED: Normal operation (requests passing through)
 * - OPEN: Service unavailable (requests fail fast)
 * - HALF_OPEN: Testing if service recovered (limited requests)
 *
 * Shared via Redis:
 * - All servers see same circuit state
 * - Coordinated failure detection
 * - No duplicate recovery attempts
 *
 * Prevents:
 * - Cascading failures (one service down takes down entire system)
 * - Thundering herd (all servers retrying at same time)
 * - Resource exhaustion (failing fast instead of timeout)
 */
class DistributedCircuitBreaker
{
    /**
     * Circuit breaker states
     */
    const STATE_CLOSED = 'closed';        // Normal
    const STATE_open = 'open';            // Failed
    const STATE_HALF_OPEN = 'half_open'; // Testing recovery

    /**
     * Configuration
     */
    private string $serviceName;
    private int $failureThreshold;        // Failures before opening
    private int $successThreshold;        // Successes before closing
    private int $timeout;                 // Seconds before trying half-open
    private float $failureRate;           // % failures to trigger open (0.5 = 50%)

    /**
     * Create circuit breaker for service
     *
     * @param string $serviceName Service identifier (e.g., "calcom", "retell")
     * @param int $failureThreshold Failures to trigger open (default: 5)
     * @param int $successThreshold Successes to close (default: 2)
     * @param int $timeout Seconds before recovery attempt (default: 60)
     * @param float $failureRate Failure rate threshold (default: 0.5 = 50%)
     */
    public function __construct(
        string $serviceName,
        int $failureThreshold = 5,
        int $successThreshold = 2,
        int $timeout = 60,
        float $failureRate = 0.5
    ) {
        $this->serviceName = $serviceName;
        $this->failureThreshold = $failureThreshold;
        $this->successThreshold = $successThreshold;
        $this->timeout = $timeout;
        $this->failureRate = $failureRate;
    }

    /**
     * Execute operation with circuit breaker protection
     *
     * Returns result or throws if circuit open
     *
     * @param callable $operation The operation to execute
     * @param string $operationName Name for logging
     * @return mixed Operation result
     * @throws CircuitBreakerOpenException If circuit is open
     */
    public function execute(callable $operation, string $operationName = 'operation'): mixed
    {
        $state = $this->getState();

        Log::debug('🔌 Circuit breaker check', [
            'service' => $this->serviceName,
            'operation' => $operationName,
            'state' => $state,
        ]);

        // OPEN: Fail fast
        if ($state === self::STATE_OPEN) {
            Log::warning('🚫 Circuit open - failing fast', [
                'service' => $this->serviceName,
                'operation' => $operationName,
            ]);
            throw new CircuitBreakerOpenException(
                "Circuit breaker OPEN for {$this->serviceName}"
            );
        }

        // HALF_OPEN: Attempt recovery with limited quota
        if ($state === self::STATE_HALF_OPEN) {
            $halfOpenQuota = $this->getHalfOpenQuota();
            if ($halfOpenQuota <= 0) {
                throw new CircuitBreakerOpenException(
                    "Half-open quota exhausted for {$this->serviceName}"
                );
            }
            $this->decrementHalfOpenQuota();
        }

        // CLOSED or HALF_OPEN: Try operation
        try {
            $result = $operation();
            $this->recordSuccess();
            return $result;

        } catch (Exception $e) {
            $this->recordFailure();
            throw $e;
        }
    }

    /**
     * Get current circuit state
     *
     * Transitions:
     * - CLOSED → OPEN if failures exceed threshold
     * - OPEN → HALF_OPEN if timeout elapsed
     * - HALF_OPEN → CLOSED if successes exceed threshold
     * - HALF_OPEN → OPEN if failures detected
     *
     * @return string Circuit state
     */
    private function getState(): string
    {
        $state = Cache::get($this->stateKey(), self::STATE_CLOSED);
        $lastFailure = Cache::get($this->lastFailureKey());
        $failures = Cache::get($this->failureCountKey(), 0);
        $successes = Cache::get($this->successCountKey(), 0);

        // HALF_OPEN: Check if recovery attempt period passed
        if ($state === self::STATE_HALF_OPEN) {
            if ($successes >= $this->successThreshold) {
                // Recovered! Close circuit
                $this->setState(self::STATE_CLOSED);
                Log::info('✅ Circuit CLOSED - service recovered', [
                    'service' => $this->serviceName,
                    'successes' => $successes,
                ]);
                return self::STATE_CLOSED;
            }
            if ($failures > 0) {
                // Still failing, reopen
                $this->setState(self::STATE_OPEN);
                Log::warning('🔴 Circuit REOPENED - recovery failed', [
                    'service' => $this->serviceName,
                ]);
                return self::STATE_OPEN;
            }
            return self::STATE_HALF_OPEN;
        }

        // OPEN: Check if timeout elapsed to try recovery
        if ($state === self::STATE_OPEN && $lastFailure) {
            $timeSinceFailure = now()->getTimestamp() - $lastFailure;
            if ($timeSinceFailure >= $this->timeout) {
                // Time to try recovery
                $this->setState(self::STATE_HALF_OPEN);
                $this->resetCounters();
                $this->initializeHalfOpenQuota();
                Log::info('🟡 Circuit HALF_OPEN - testing recovery', [
                    'service' => $this->serviceName,
                    'seconds_since_failure' => $timeSinceFailure,
                ]);
                return self::STATE_HALF_OPEN;
            }
            return self::STATE_OPEN;
        }

        // CLOSED: Check if failures exceed threshold
        if ($state === self::STATE_CLOSED) {
            $total = $failures + $successes;
            if ($total > 0) {
                $actualFailureRate = $failures / $total;
                if ($failures >= $this->failureThreshold ||
                    $actualFailureRate >= $this->failureRate) {
                    $this->setState(self::STATE_OPEN);
                    $this->recordLastFailure();
                    Log::error('🔴 Circuit OPENED', [
                        'service' => $this->serviceName,
                        'failures' => $failures,
                        'failure_rate' => round($actualFailureRate * 100, 2) . '%',
                    ]);
                    return self::STATE_OPEN;
                }
            }
            return self::STATE_CLOSED;
        }

        return $state;
    }

    /**
     * Record successful operation
     */
    private function recordSuccess(): void
    {
        Cache::increment($this->successCountKey());
        // Only reset failures in HALF_OPEN state
        if ($this->getState() === self::STATE_HALF_OPEN) {
            Log::debug('✅ Half-open success recorded', [
                'service' => $this->serviceName,
            ]);
        }
    }

    /**
     * Record failed operation
     */
    private function recordFailure(): void
    {
        Cache::increment($this->failureCountKey());
        $this->recordLastFailure();
        Log::debug('❌ Failure recorded', [
            'service' => $this->serviceName,
        ]);
    }

    /**
     * Set circuit state
     */
    private function setState(string $state): void
    {
        Cache::put($this->stateKey(), $state, 86400);  // 24 hour safety TTL
        Log::debug("Circuit state set to {$state}", [
            'service' => $this->serviceName,
        ]);
    }

    /**
     * Record last failure timestamp
     */
    private function recordLastFailure(): void
    {
        Cache::put($this->lastFailureKey(), now()->getTimestamp(), 86400);
    }

    /**
     * Reset counters after state transition
     */
    private function resetCounters(): void
    {
        Cache::forget($this->failureCountKey());
        Cache::forget($this->successCountKey());
    }

    /**
     * Initialize half-open quota (limited requests to test recovery)
     */
    private function initializeHalfOpenQuota(): void
    {
        Cache::put($this->halfOpenQuotaKey(), 3, 300);  // 3 requests, 5 min TTL
    }

    /**
     * Get half-open quota
     */
    private function getHalfOpenQuota(): int
    {
        return Cache::get($this->halfOpenQuotaKey(), 0);
    }

    /**
     * Decrement half-open quota
     */
    private function decrementHalfOpenQuota(): void
    {
        Cache::decrement($this->halfOpenQuotaKey());
    }

    /**
     * Get circuit state for monitoring
     *
     * @return array State information
     */
    public function getStatus(): array
    {
        return [
            'service' => $this->serviceName,
            'state' => $this->getState(),
            'failures' => Cache::get($this->failureCountKey(), 0),
            'successes' => Cache::get($this->successCountKey(), 0),
            'last_failure' => Cache::get($this->lastFailureKey()),
            'half_open_quota' => $this->getHalfOpenQuota(),
        ];
    }

    /**
     * Manually reset circuit (admin/testing only)
     */
    public function reset(): void
    {
        Cache::forget($this->stateKey());
        $this->resetCounters();
        Cache::forget($this->lastFailureKey());
        Cache::forget($this->halfOpenQuotaKey());
        Log::info('🔄 Circuit breaker manually reset', [
            'service' => $this->serviceName,
        ]);
    }

    /**
     * Cache key helpers
     */
    private function stateKey(): string
    {
        return "circuit_breaker:{$this->serviceName}:state";
    }

    private function failureCountKey(): string
    {
        return "circuit_breaker:{$this->serviceName}:failures";
    }

    private function successCountKey(): string
    {
        return "circuit_breaker:{$this->serviceName}:successes";
    }

    private function lastFailureKey(): string
    {
        return "circuit_breaker:{$this->serviceName}:last_failure";
    }

    private function halfOpenQuotaKey(): string
    {
        return "circuit_breaker:{$this->serviceName}:half_open_quota";
    }
}
