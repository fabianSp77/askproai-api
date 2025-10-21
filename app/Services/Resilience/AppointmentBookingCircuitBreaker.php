<?php

namespace App\Services\Resilience;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * Appointment Booking Circuit Breaker
 *
 * Implements circuit breaker pattern to prevent cascading failures in appointment booking.
 * Uses Redis for fast state checks and PostgreSQL for persistence.
 *
 * STATES:
 * - CLOSED: Normal operation, requests pass through
 * - OPEN: Fast fail, requests rejected immediately
 * - HALF_OPEN: Testing recovery, allow single request
 *
 * CONFIGURATION:
 * - Failure threshold: 3 consecutive failures → OPEN
 * - Cooldown period: 30 seconds before trying HALF_OPEN
 * - Success threshold: 2 consecutive successes in HALF_OPEN → CLOSED
 *
 * USAGE:
 * $result = $circuitBreaker->executeWithCircuitBreaker(
 *     'appointment_booking:service:123',
 *     fn() => $this->bookAppointment(...)
 * );
 */
class AppointmentBookingCircuitBreaker
{
    // Circuit states
    private const STATE_CLOSED = 'closed';
    private const STATE_OPEN = 'open';
    private const STATE_HALF_OPEN = 'half_open';

    // Configuration
    private const FAILURE_THRESHOLD = 3;      // Open after N failures
    private const COOLDOWN_SECONDS = 30;      // Wait before trying HALF_OPEN
    private const SUCCESS_THRESHOLD = 2;      // Close after N successes in HALF_OPEN
    private const TIMEOUT_SECONDS = 10;       // Operation timeout

    // Redis key prefixes
    private const REDIS_PREFIX = 'circuit_breaker:';

    /**
     * Execute operation with circuit breaker protection
     *
     * @param string $circuitKey Unique circuit identifier (e.g., 'appointment_booking:service:123')
     * @param callable $operation The operation to execute
     * @return mixed Operation result
     * @throws CircuitOpenException If circuit is open
     * @throws \Exception If operation fails
     */
    public function executeWithCircuitBreaker(string $circuitKey, callable $operation)
    {
        $state = $this->getState($circuitKey);

        Log::debug('🔌 Circuit breaker check', [
            'circuit_key' => $circuitKey,
            'state' => $state
        ]);

        // OPEN state: Fast fail
        if ($state === self::STATE_OPEN) {
            // Check if cooldown period has elapsed
            if ($this->shouldAttemptReset($circuitKey)) {
                Log::info('🔄 Circuit breaker entering HALF_OPEN state', [
                    'circuit_key' => $circuitKey
                ]);
                $this->setState($circuitKey, self::STATE_HALF_OPEN);
                // Continue to HALF_OPEN logic below
            } else {
                Log::warning('⚡ Circuit breaker OPEN - request rejected', [
                    'circuit_key' => $circuitKey
                ]);
                throw new CircuitOpenException("Circuit breaker is OPEN for: {$circuitKey}");
            }
        }

        // HALF_OPEN state: Allow single test request
        if ($state === self::STATE_HALF_OPEN) {
            // Check if another request is already testing
            if ($this->isTestRequestInProgress($circuitKey)) {
                Log::warning('⏳ Circuit breaker HALF_OPEN - test in progress, rejecting request', [
                    'circuit_key' => $circuitKey
                ]);
                throw new CircuitOpenException("Circuit breaker test request in progress: {$circuitKey}");
            }

            $this->markTestRequestInProgress($circuitKey);
        }

        // Execute operation with timeout
        try {
            $result = $this->executeWithTimeout($operation, self::TIMEOUT_SECONDS);

            // Success - record it
            $this->recordSuccess($circuitKey);

            return $result;

        } catch (\Exception $e) {
            // Failure - record it
            $this->recordFailure($circuitKey, $e);

            throw $e;

        } finally {
            // Clear test request flag if in HALF_OPEN
            if ($state === self::STATE_HALF_OPEN) {
                $this->clearTestRequestFlag($circuitKey);
            }
        }
    }

    /**
     * Record successful operation
     *
     * @param string $circuitKey
     * @return void
     */
    public function recordSuccess(string $circuitKey): void
    {
        $state = $this->getState($circuitKey);

        Log::info('✅ Circuit breaker: Operation succeeded', [
            'circuit_key' => $circuitKey,
            'state' => $state
        ]);

        if ($state === self::STATE_HALF_OPEN) {
            // Increment success counter
            $successes = $this->incrementSuccessCounter($circuitKey);

            Log::debug('🔄 Circuit breaker HALF_OPEN success', [
                'circuit_key' => $circuitKey,
                'successes' => $successes,
                'threshold' => self::SUCCESS_THRESHOLD
            ]);

            // If enough successes, close circuit
            if ($successes >= self::SUCCESS_THRESHOLD) {
                $this->closeCircuit($circuitKey);
            }
        } elseif ($state === self::STATE_CLOSED) {
            // Reset failure counter on success in CLOSED state
            $this->resetFailureCounter($circuitKey);
        }

        // Persist to database
        $this->persistState($circuitKey);
    }

    /**
     * Record failed operation
     *
     * @param string $circuitKey
     * @param \Exception $exception
     * @return void
     */
    public function recordFailure(string $circuitKey, \Exception $exception): void
    {
        $state = $this->getState($circuitKey);

        Log::error('❌ Circuit breaker: Operation failed', [
            'circuit_key' => $circuitKey,
            'state' => $state,
            'error' => $exception->getMessage(),
            'error_class' => get_class($exception)
        ]);

        if ($state === self::STATE_HALF_OPEN) {
            // Single failure in HALF_OPEN reopens circuit
            $this->openCircuit($circuitKey, 'Failed in HALF_OPEN state');
        } elseif ($state === self::STATE_CLOSED) {
            // Increment failure counter
            $failures = $this->incrementFailureCounter($circuitKey);

            Log::debug('🔄 Circuit breaker failure count', [
                'circuit_key' => $circuitKey,
                'failures' => $failures,
                'threshold' => self::FAILURE_THRESHOLD
            ]);

            // If threshold exceeded, open circuit
            if ($failures >= self::FAILURE_THRESHOLD) {
                $this->openCircuit($circuitKey, "Exceeded failure threshold ({$failures} failures)");
            }
        }

        // Persist to database
        $this->persistState($circuitKey);
    }

    /**
     * Get current circuit state
     *
     * @param string $circuitKey
     * @return string
     */
    public function getState(string $circuitKey): string
    {
        $redis = Redis::connection();
        $state = $redis->get($this->getRedisKey($circuitKey, 'state'));

        return $state ?? self::STATE_CLOSED;
    }

    /**
     * Set circuit state
     *
     * @param string $circuitKey
     * @param string $state
     * @return void
     */
    private function setState(string $circuitKey, string $state): void
    {
        $redis = Redis::connection();
        $redis->set($this->getRedisKey($circuitKey, 'state'), $state);
        $redis->expire($this->getRedisKey($circuitKey, 'state'), 86400); // 24 hours TTL
    }

    /**
     * Open circuit (start rejecting requests)
     *
     * @param string $circuitKey
     * @param string $reason
     * @return void
     */
    private function openCircuit(string $circuitKey, string $reason): void
    {
        Log::warning('🚨 Circuit breaker OPENING', [
            'circuit_key' => $circuitKey,
            'reason' => $reason
        ]);

        $redis = Redis::connection();

        // Set state to OPEN
        $this->setState($circuitKey, self::STATE_OPEN);

        // Record when opened
        $redis->set($this->getRedisKey($circuitKey, 'opened_at'), now()->timestamp);
        $redis->expire($this->getRedisKey($circuitKey, 'opened_at'), 86400);

        // Reset counters
        $this->resetFailureCounter($circuitKey);
        $this->resetSuccessCounter($circuitKey);

        // Persist to database
        $this->persistState($circuitKey);
    }

    /**
     * Close circuit (resume normal operation)
     *
     * @param string $circuitKey
     * @return void
     */
    private function closeCircuit(string $circuitKey): void
    {
        Log::info('✅ Circuit breaker CLOSING (recovered)', [
            'circuit_key' => $circuitKey
        ]);

        $redis = Redis::connection();

        // Set state to CLOSED
        $this->setState($circuitKey, self::STATE_CLOSED);

        // Record when closed
        $redis->set($this->getRedisKey($circuitKey, 'closed_at'), now()->timestamp);
        $redis->expire($this->getRedisKey($circuitKey, 'closed_at'), 86400);

        // Clear opened_at timestamp
        $redis->del($this->getRedisKey($circuitKey, 'opened_at'));

        // Reset counters
        $this->resetFailureCounter($circuitKey);
        $this->resetSuccessCounter($circuitKey);

        // Persist to database
        $this->persistState($circuitKey);
    }

    /**
     * Check if cooldown period has elapsed
     *
     * @param string $circuitKey
     * @return bool
     */
    private function shouldAttemptReset(string $circuitKey): bool
    {
        $redis = Redis::connection();
        $openedAt = $redis->get($this->getRedisKey($circuitKey, 'opened_at'));

        if (!$openedAt) {
            return true; // No opened timestamp, allow reset
        }

        $elapsedSeconds = now()->timestamp - (int)$openedAt;

        return $elapsedSeconds >= self::COOLDOWN_SECONDS;
    }

    /**
     * Check if test request is in progress (HALF_OPEN state)
     *
     * @param string $circuitKey
     * @return bool
     */
    private function isTestRequestInProgress(string $circuitKey): bool
    {
        $redis = Redis::connection();
        return $redis->exists($this->getRedisKey($circuitKey, 'test_in_progress')) > 0;
    }

    /**
     * Mark test request as in progress
     *
     * @param string $circuitKey
     * @return void
     */
    private function markTestRequestInProgress(string $circuitKey): void
    {
        $redis = Redis::connection();
        $redis->setex($this->getRedisKey($circuitKey, 'test_in_progress'), self::TIMEOUT_SECONDS + 5, '1');
    }

    /**
     * Clear test request flag
     *
     * @param string $circuitKey
     * @return void
     */
    private function clearTestRequestFlag(string $circuitKey): void
    {
        $redis = Redis::connection();
        $redis->del($this->getRedisKey($circuitKey, 'test_in_progress'));
    }

    /**
     * Increment failure counter
     *
     * @param string $circuitKey
     * @return int New failure count
     */
    private function incrementFailureCounter(string $circuitKey): int
    {
        $redis = Redis::connection();
        $key = $this->getRedisKey($circuitKey, 'failures');
        $failures = $redis->incr($key);
        $redis->expire($key, 3600); // 1 hour TTL

        // Record last failure timestamp
        $redis->set($this->getRedisKey($circuitKey, 'last_failure'), now()->timestamp);
        $redis->expire($this->getRedisKey($circuitKey, 'last_failure'), 3600);

        return $failures;
    }

    /**
     * Reset failure counter
     *
     * @param string $circuitKey
     * @return void
     */
    private function resetFailureCounter(string $circuitKey): void
    {
        $redis = Redis::connection();
        $redis->del($this->getRedisKey($circuitKey, 'failures'));
        $redis->del($this->getRedisKey($circuitKey, 'last_failure'));
    }

    /**
     * Increment success counter (HALF_OPEN state)
     *
     * @param string $circuitKey
     * @return int New success count
     */
    private function incrementSuccessCounter(string $circuitKey): int
    {
        $redis = Redis::connection();
        $key = $this->getRedisKey($circuitKey, 'successes');
        $successes = $redis->incr($key);
        $redis->expire($key, 3600); // 1 hour TTL

        return $successes;
    }

    /**
     * Reset success counter
     *
     * @param string $circuitKey
     * @return void
     */
    private function resetSuccessCounter(string $circuitKey): void
    {
        $redis = Redis::connection();
        $redis->del($this->getRedisKey($circuitKey, 'successes'));
    }

    /**
     * Execute operation with timeout
     *
     * @param callable $operation
     * @param int $timeoutSeconds
     * @return mixed
     * @throws \Exception
     */
    private function executeWithTimeout(callable $operation, int $timeoutSeconds)
    {
        // Note: PHP doesn't have built-in operation timeout for callables
        // This is a placeholder - in production, use process timeout or async execution

        $startTime = microtime(true);

        try {
            $result = $operation();
        } catch (\Exception $e) {
            throw $e;
        }

        $duration = microtime(true) - $startTime;

        if ($duration > $timeoutSeconds) {
            Log::warning('⏱️ Operation exceeded timeout', [
                'duration_seconds' => $duration,
                'timeout_seconds' => $timeoutSeconds
            ]);
            // Note: Operation already completed, just logging slow execution
        }

        return $result;
    }

    /**
     * Persist circuit state to database
     *
     * @param string $circuitKey
     * @return void
     */
    private function persistState(string $circuitKey): void
    {
        $redis = Redis::connection();

        $state = $this->getState($circuitKey);
        $failures = $redis->get($this->getRedisKey($circuitKey, 'failures')) ?? 0;
        $successes = $redis->get($this->getRedisKey($circuitKey, 'successes')) ?? 0;
        $lastFailure = $redis->get($this->getRedisKey($circuitKey, 'last_failure'));
        $openedAt = $redis->get($this->getRedisKey($circuitKey, 'opened_at'));
        $closedAt = $redis->get($this->getRedisKey($circuitKey, 'closed_at'));

        DB::table('circuit_breaker_states')->updateOrInsert(
            ['circuit_key' => $circuitKey],
            [
                'state' => $state,
                'failure_count' => (int)$failures,
                'success_count' => (int)$successes,
                'last_failure_at' => $lastFailure ? Carbon::createFromTimestamp($lastFailure) : null,
                'opened_at' => $openedAt ? Carbon::createFromTimestamp($openedAt) : null,
                'closed_at' => $closedAt ? Carbon::createFromTimestamp($closedAt) : null,
                'updated_at' => now()
            ]
        );
    }

    /**
     * Get Redis key for circuit breaker data
     *
     * @param string $circuitKey
     * @param string $suffix
     * @return string
     */
    private function getRedisKey(string $circuitKey, string $suffix): string
    {
        return self::REDIS_PREFIX . $circuitKey . ':' . $suffix;
    }

    /**
     * Get circuit statistics
     *
     * @param string $circuitKey
     * @return array
     */
    public function getStatistics(string $circuitKey): array
    {
        $redis = Redis::connection();

        return [
            'circuit_key' => $circuitKey,
            'state' => $this->getState($circuitKey),
            'failure_count' => (int)($redis->get($this->getRedisKey($circuitKey, 'failures')) ?? 0),
            'success_count' => (int)($redis->get($this->getRedisKey($circuitKey, 'successes')) ?? 0),
            'last_failure_at' => $redis->get($this->getRedisKey($circuitKey, 'last_failure'))
                ? Carbon::createFromTimestamp($redis->get($this->getRedisKey($circuitKey, 'last_failure')))
                : null,
            'opened_at' => $redis->get($this->getRedisKey($circuitKey, 'opened_at'))
                ? Carbon::createFromTimestamp($redis->get($this->getRedisKey($circuitKey, 'opened_at')))
                : null,
            'closed_at' => $redis->get($this->getRedisKey($circuitKey, 'closed_at'))
                ? Carbon::createFromTimestamp($redis->get($this->getRedisKey($circuitKey, 'closed_at')))
                : null,
        ];
    }
}

/**
 * Exception thrown when circuit breaker is open
 */
class CircuitOpenException extends \Exception
{
    public function __construct(string $message = "Circuit breaker is open")
    {
        parent::__construct($message);
    }
}
