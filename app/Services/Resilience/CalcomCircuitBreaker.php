<?php

namespace App\Services\Resilience;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Circuit Breaker Pattern Implementation for Cal.com API
 *
 * Prevents cascading failures when Cal.com is experiencing issues
 *
 * STATES:
 * - CLOSED: Normal operation, requests go through
 * - OPEN: Too many failures, requests immediately rejected
 * - HALF_OPEN: Testing if service recovered, limited requests allowed
 *
 * CONFIGURATION:
 * - threshold: 5 failures within window before opening
 * - window: 60 seconds to count failures
 * - half_open_timeout: 30 seconds before trying again
 */
class CalcomCircuitBreaker
{
    private const STATE_CLOSED = 'closed';
    private const STATE_OPEN = 'open';
    private const STATE_HALF_OPEN = 'half_open';

    private const CACHE_KEY_STATE = 'circuit_breaker:calcom:state';
    private const CACHE_KEY_FAILURES = 'circuit_breaker:calcom:failures';
    private const CACHE_KEY_OPENED_AT = 'circuit_breaker:calcom:opened_at';

    private int $threshold = 5;           // failures to trigger open
    private int $window = 60;              // seconds to count failures
    private int $halfOpenTimeout = 30;     // seconds before retrying

    public function __construct()
    {
        $config = config('appointments.circuit_breaker', []);
        $this->threshold = $config['threshold'] ?? 5;
        $this->window = $config['window'] ?? 60;
        $this->halfOpenTimeout = $config['half_open_timeout'] ?? 30;
    }

    /**
     * Get current circuit state
     */
    public function getState(): string
    {
        $state = Cache::get(self::CACHE_KEY_STATE, self::STATE_CLOSED);

        // If open, check if we should transition to half-open
        if ($state === self::STATE_OPEN) {
            $openedAt = Cache::get(self::CACHE_KEY_OPENED_AT);
            if ($openedAt && (now()->timestamp - $openedAt) > $this->halfOpenTimeout) {
                $this->setState(self::STATE_HALF_OPEN);
                return self::STATE_HALF_OPEN;
            }
        }

        return $state;
    }

    /**
     * Check if circuit is open (requests should be rejected)
     */
    public function isOpen(): bool
    {
        return $this->getState() === self::STATE_OPEN;
    }

    /**
     * Check if circuit is in half-open state (testing recovery)
     */
    public function isHalfOpen(): bool
    {
        return $this->getState() === self::STATE_HALF_OPEN;
    }

    /**
     * Record a failure
     * Opens circuit if threshold exceeded
     */
    public function recordFailure(?string $reason = null): void
    {
        $state = $this->getState();

        // Count failures only if closed or half-open
        if ($state === self::STATE_CLOSED || $state === self::STATE_HALF_OPEN) {
            $failures = Cache::get(self::CACHE_KEY_FAILURES, 0);
            $failures++;

            // Store failure count with window TTL
            Cache::put(self::CACHE_KEY_FAILURES, $failures, $this->window);

            Log::warning('Circuit breaker failure recorded', [
                'failure_count' => $failures,
                'threshold' => $this->threshold,
                'reason' => $reason,
            ]);

            // Open circuit if threshold exceeded
            if ($failures >= $this->threshold) {
                $this->setState(self::STATE_OPEN);
                Log::error('Circuit breaker OPENED - Cal.com API failing', [
                    'failures' => $failures,
                    'threshold' => $this->threshold,
                    'reason' => $reason,
                ]);
            }
        }

        // If half-open and failure, go back to open
        if ($state === self::STATE_HALF_OPEN) {
            $this->setState(self::STATE_OPEN);
            Log::error('Circuit breaker REOPENED - Recovery failed', [
                'reason' => $reason,
            ]);
        }
    }

    /**
     * Record a success
     * Closes circuit if in half-open state
     */
    public function recordSuccess(): void
    {
        $state = $this->getState();

        if ($state === self::STATE_HALF_OPEN) {
            // Recovery successful, close circuit
            $this->setState(self::STATE_CLOSED);
            Cache::forget(self::CACHE_KEY_FAILURES);
            Cache::forget(self::CACHE_KEY_OPENED_AT);

            Log::info('Circuit breaker CLOSED - Cal.com recovered', [
                'recovery_successful' => true,
            ]);
        } elseif ($state === self::STATE_CLOSED) {
            // Normal operation, reset failure count
            $failures = Cache::get(self::CACHE_KEY_FAILURES, 0);
            if ($failures > 0) {
                $failures--;
                Cache::put(self::CACHE_KEY_FAILURES, $failures, $this->window);
            }
        }
    }

    /**
     * Manually reset circuit to closed state
     * Used for testing or admin operations
     */
    public function reset(): void
    {
        $this->setState(self::STATE_CLOSED);
        Cache::forget(self::CACHE_KEY_FAILURES);
        Cache::forget(self::CACHE_KEY_OPENED_AT);
        Log::info('Circuit breaker manually RESET to CLOSED state');
    }

    /**
     * Set circuit state and log transition
     */
    private function setState(string $state): void
    {
        $oldState = Cache::get(self::CACHE_KEY_STATE, self::STATE_CLOSED);

        if ($oldState !== $state) {
            Cache::put(self::CACHE_KEY_STATE, $state, 3600); // 1 hour

            if ($state === self::STATE_OPEN) {
                Cache::put(self::CACHE_KEY_OPENED_AT, now()->timestamp, 3600);
            }

            Log::info('Circuit breaker state transition', [
                'from' => $oldState,
                'to' => $state,
            ]);
        }
    }

    /**
     * Get circuit breaker status for monitoring
     */
    public function getStatus(): array
    {
        return [
            'state' => $this->getState(),
            'failures' => Cache::get(self::CACHE_KEY_FAILURES, 0),
            'threshold' => $this->threshold,
            'opened_at' => Cache::get(self::CACHE_KEY_OPENED_AT),
            'half_open_timeout' => $this->halfOpenTimeout,
            'is_open' => $this->isOpen(),
            'is_half_open' => $this->isHalfOpen(),
        ];
    }
}
