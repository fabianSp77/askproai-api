<?php

namespace App\Services\CustomerPortal;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Circuit Breaker for Cal.com API
 *
 * PATTERN: Circuit Breaker (Michael Nygard)
 *
 * STATES:
 * - CLOSED: Normal operation, requests pass through
 * - OPEN: Too many failures, requests fail immediately
 * - HALF_OPEN: Testing if service recovered
 *
 * THRESHOLDS:
 * - Failure threshold: 5 failures in 60 seconds
 * - Timeout: 60 seconds (how long to stay OPEN)
 * - Success threshold: 2 successes to close from HALF_OPEN
 *
 * BENEFITS:
 * - Prevents cascading failures
 * - Reduces load on failing service
 * - Automatic recovery testing
 * - Fast-fail for better UX
 */
class CalcomCircuitBreaker
{
    private const STATE_CLOSED = 'closed';
    private const STATE_OPEN = 'open';
    private const STATE_HALF_OPEN = 'half_open';

    private const FAILURE_THRESHOLD = 5;
    private const TIMEOUT_SECONDS = 60;
    private const SUCCESS_THRESHOLD = 2;
    private const WINDOW_SECONDS = 60;

    private const CACHE_KEY_STATE = 'circuit_breaker:calcom:state';
    private const CACHE_KEY_FAILURES = 'circuit_breaker:calcom:failures';
    private const CACHE_KEY_SUCCESSES = 'circuit_breaker:calcom:successes';
    private const CACHE_KEY_OPENED_AT = 'circuit_breaker:calcom:opened_at';

    /**
     * Check if circuit breaker is open (blocking requests)
     */
    public function isOpen(): bool
    {
        $state = $this->getState();

        if ($state === self::STATE_OPEN) {
            // Check if timeout expired, transition to HALF_OPEN
            $openedAt = Cache::get(self::CACHE_KEY_OPENED_AT);
            if ($openedAt && now()->diffInSeconds($openedAt) >= self::TIMEOUT_SECONDS) {
                $this->transitionToHalfOpen();
                return false; // Allow test request
            }
            return true;
        }

        return false;
    }

    /**
     * Record successful attempt
     */
    public function recordSuccess(): void
    {
        $state = $this->getState();

        if ($state === self::STATE_HALF_OPEN) {
            // Increment success counter
            $successes = Cache::get(self::CACHE_KEY_SUCCESSES, 0) + 1;
            Cache::put(self::CACHE_KEY_SUCCESSES, $successes, now()->addMinutes(5));

            // If success threshold reached, close circuit
            if ($successes >= self::SUCCESS_THRESHOLD) {
                $this->transitionToClosed();
                Log::info('Circuit breaker CLOSED - Cal.com service recovered');
            }
        } elseif ($state === self::STATE_CLOSED) {
            // Reset failure counter on success
            Cache::forget(self::CACHE_KEY_FAILURES);
        }
    }

    /**
     * Record failed attempt
     */
    public function recordFailure(): void
    {
        $state = $this->getState();

        if ($state === self::STATE_HALF_OPEN) {
            // Failure in HALF_OPEN → reopen circuit
            $this->transitionToOpen();
            Log::warning('Circuit breaker REOPENED - Cal.com still failing');
            return;
        }

        if ($state === self::STATE_CLOSED) {
            // Increment failure counter
            $failures = $this->getFailureCount() + 1;
            Cache::put(self::CACHE_KEY_FAILURES, $failures, now()->addSeconds(self::WINDOW_SECONDS));

            // Check if threshold exceeded
            if ($failures >= self::FAILURE_THRESHOLD) {
                $this->transitionToOpen();
                Log::error('Circuit breaker OPENED - Cal.com service degraded', [
                    'failures' => $failures,
                    'threshold' => self::FAILURE_THRESHOLD,
                    'window_seconds' => self::WINDOW_SECONDS,
                ]);

                // Alert monitoring system
                // TODO: Integrate with monitoring (Sentry, PagerDuty, etc.)
            }
        }
    }

    /**
     * Record request attempt (for monitoring)
     */
    public function recordAttempt(): void
    {
        // Increment attempt counter for metrics
        Cache::increment('circuit_breaker:calcom:attempts', 1);
    }

    /**
     * Get current state
     */
    public function getState(): string
    {
        return Cache::get(self::CACHE_KEY_STATE, self::STATE_CLOSED);
    }

    /**
     * Get failure count in current window
     */
    public function getFailureCount(): int
    {
        return Cache::get(self::CACHE_KEY_FAILURES, 0);
    }

    /**
     * Get metrics for monitoring dashboard
     */
    public function getMetrics(): array
    {
        return [
            'state' => $this->getState(),
            'failures' => $this->getFailureCount(),
            'successes' => Cache::get(self::CACHE_KEY_SUCCESSES, 0),
            'attempts' => Cache::get('circuit_breaker:calcom:attempts', 0),
            'opened_at' => Cache::get(self::CACHE_KEY_OPENED_AT),
            'failure_threshold' => self::FAILURE_THRESHOLD,
            'timeout_seconds' => self::TIMEOUT_SECONDS,
        ];
    }

    /**
     * Manual reset (admin action)
     */
    public function reset(): void
    {
        $this->transitionToClosed();
        Log::info('Circuit breaker manually RESET by admin');
    }

    // ==========================================
    // STATE TRANSITIONS
    // ==========================================

    private function transitionToOpen(): void
    {
        Cache::put(self::CACHE_KEY_STATE, self::STATE_OPEN, now()->addMinutes(10));
        Cache::put(self::CACHE_KEY_OPENED_AT, now(), now()->addMinutes(10));
        Cache::forget(self::CACHE_KEY_SUCCESSES);
    }

    private function transitionToHalfOpen(): void
    {
        Cache::put(self::CACHE_KEY_STATE, self::STATE_HALF_OPEN, now()->addMinutes(5));
        Cache::put(self::CACHE_KEY_SUCCESSES, 0, now()->addMinutes(5));
        Cache::forget(self::CACHE_KEY_FAILURES);
        Log::info('Circuit breaker HALF_OPEN - testing Cal.com recovery');
    }

    private function transitionToClosed(): void
    {
        Cache::forget(self::CACHE_KEY_STATE);
        Cache::forget(self::CACHE_KEY_FAILURES);
        Cache::forget(self::CACHE_KEY_SUCCESSES);
        Cache::forget(self::CACHE_KEY_OPENED_AT);
    }
}
