<?php

namespace App\Services\ServiceDesk;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Cache\Lock;
use Closure;
use Exception;

/**
 * Service Desk Lock Service
 *
 * Mirrors BookingLockService pattern for service desk case creation.
 * Prevents duplicate case creation through Redis-based distributed locks
 * and idempotency checks.
 *
 * FEATURES:
 * - Distributed lock acquisition with TTL
 * - Idempotency tracking via Cache
 * - Double-check pattern after lock acquisition
 * - Automatic lock release via withCaseLock closure
 *
 * CRITICAL:
 * - MUST prevent duplicate cases for same call_id
 * - MUST handle concurrent requests safely
 * - MUST provide clear logging for debugging
 */
class ServiceDeskLockService
{
    /**
     * TTL for locks in seconds
     *
     * Lower than BookingLockService (120s) since case creation is faster
     */
    private int $ttl = 120;

    /**
     * Max wait time for acquiring lock in seconds
     *
     * Lower than BookingLockService (10s) for faster failure response
     */
    private int $maxWait = 5;

    /**
     * Acquire lock for call-based case creation
     *
     * @param string $callId Retell call ID
     * @return Lock|null Returns lock if acquired, null otherwise
     */
    public function acquireCaseLock(string $callId): ?Lock
    {
        $key = $this->buildLockKey($callId);

        Log::debug('[ServiceDeskLock] Attempting to acquire case lock', [
            'key' => $key,
            'ttl' => $this->ttl,
            'call_id' => $callId,
        ]);

        $lock = Cache::lock($key, $this->ttl);

        // Try immediate acquisition
        if ($lock->get()) {
            Log::info('[ServiceDeskLock] Lock acquired immediately', [
                'key' => $key,
                'call_id' => $callId,
            ]);
            return $lock;
        }

        // Try to wait for lock
        $acquired = $lock->block($this->maxWait);

        if ($acquired) {
            Log::info('[ServiceDeskLock] Lock acquired after waiting', [
                'key' => $key,
                'call_id' => $callId,
                'max_wait' => $this->maxWait,
            ]);
            return $lock;
        }

        Log::warning('[ServiceDeskLock] Failed to acquire lock', [
            'key' => $key,
            'call_id' => $callId,
            'max_wait' => $this->maxWait,
        ]);

        return null;
    }

    /**
     * Check if case already created (idempotency check)
     *
     * @param string $callId Retell call ID
     * @return bool True if case already exists
     */
    public function isCaseAlreadyCreated(string $callId): bool
    {
        $cacheKey = $this->buildIdempotencyKey($callId);
        $exists = Cache::has($cacheKey);

        if ($exists) {
            Log::info('[ServiceDeskLock] Case already created (idempotent)', [
                'call_id' => $callId,
                'cache_key' => $cacheKey,
            ]);
        }

        return $exists;
    }

    /**
     * Mark case as created (for idempotency tracking)
     *
     * @param string $callId Retell call ID
     * @param int $caseId Created case ID
     * @return void
     */
    public function markCaseCreated(string $callId, int $caseId): void
    {
        $cacheKey = $this->buildIdempotencyKey($callId);

        // Store for 1 hour (3600s) - sufficient for duplicate request window
        Cache::put($cacheKey, $caseId, 3600);

        Log::info('[ServiceDeskLock] Case marked as created', [
            'call_id' => $callId,
            'case_id' => $caseId,
            'cache_key' => $cacheKey,
            'ttl' => 3600,
        ]);
    }

    /**
     * Get existing case ID from idempotency cache
     *
     * @param string $callId Retell call ID
     * @return int|null Case ID if found, null otherwise
     */
    public function getExistingCaseId(string $callId): ?int
    {
        $cacheKey = $this->buildIdempotencyKey($callId);
        $caseId = Cache::get($cacheKey);

        if ($caseId) {
            Log::debug('[ServiceDeskLock] Retrieved existing case ID', [
                'call_id' => $callId,
                'case_id' => $caseId,
            ]);
        }

        return $caseId;
    }

    /**
     * Execute callback with case lock
     *
     * Mirrors BookingLockService::withLocks pattern
     * Automatically releases lock after execution
     *
     * @param string $callId Retell call ID
     * @param Closure $callback Callback to execute with lock
     * @return mixed Callback return value
     * @throws ServiceDeskLockException If lock cannot be acquired
     */
    public function withCaseLock(string $callId, Closure $callback)
    {
        $lock = $this->acquireCaseLock($callId);

        if (!$lock) {
            throw new ServiceDeskLockException(
                "Failed to acquire case lock for call: {$callId}"
            );
        }

        try {
            Log::info('[ServiceDeskLock] Executing callback with lock', [
                'call_id' => $callId,
            ]);

            return $callback($lock);
        } finally {
            try {
                $lock->release();
                Log::debug('[ServiceDeskLock] Lock released', [
                    'call_id' => $callId,
                ]);
            } catch (Exception $e) {
                Log::error('[ServiceDeskLock] Error releasing lock', [
                    'call_id' => $callId,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Check if case creation is locked
     *
     * @param string $callId Retell call ID
     * @return bool True if locked, false otherwise
     */
    public function isLocked(string $callId): bool
    {
        $key = $this->buildLockKey($callId);
        $lock = Cache::lock($key, 0);

        // Try to acquire with 0 TTL to just check
        if ($lock->get()) {
            // Was not locked, release immediately
            $lock->release();
            return false;
        }

        return true;
    }

    /**
     * Build lock key for Redis
     *
     * @param string $callId Retell call ID
     * @return string Redis lock key
     */
    private function buildLockKey(string $callId): string
    {
        return "service_desk:case:lock:{$callId}";
    }

    /**
     * Build idempotency cache key
     *
     * @param string $callId Retell call ID
     * @return string Redis cache key
     */
    private function buildIdempotencyKey(string $callId): string
    {
        return "case_created:{$callId}";
    }

    /**
     * Get lock statistics (for monitoring)
     *
     * @return array Lock service configuration
     */
    public function getLockStats(): array
    {
        return [
            'ttl' => $this->ttl,
            'max_wait' => $this->maxWait,
            'driver' => config('cache.default'),
            'redis_connection' => config('cache.stores.redis.connection'),
            'service' => 'ServiceDeskLockService',
        ];
    }

    /**
     * Set custom TTL (for testing or special cases)
     *
     * @param int $seconds TTL in seconds (10-300)
     * @return void
     * @throws Exception If TTL out of range
     */
    public function setTtl(int $seconds): void
    {
        if ($seconds < 10 || $seconds > 300) {
            throw new Exception('TTL must be between 10 and 300 seconds');
        }

        $this->ttl = $seconds;
    }

    /**
     * Set max wait time (for testing or special cases)
     *
     * @param int $seconds Max wait in seconds (0-30)
     * @return void
     * @throws Exception If max wait out of range
     */
    public function setMaxWait(int $seconds): void
    {
        if ($seconds < 0 || $seconds > 30) {
            throw new Exception('Max wait must be between 0 and 30 seconds');
        }

        $this->maxWait = $seconds;
    }

    /**
     * Clear idempotency cache for call (emergency use only)
     *
     * @param string $callId Retell call ID
     * @return void
     */
    public function clearIdempotency(string $callId): void
    {
        $cacheKey = $this->buildIdempotencyKey($callId);
        Cache::forget($cacheKey);

        Log::warning('[ServiceDeskLock] Idempotency cleared - emergency operation', [
            'call_id' => $callId,
            'cache_key' => $cacheKey,
        ]);
    }
}

/**
 * Service Desk Lock Exception
 *
 * Thrown when lock acquisition fails
 */
class ServiceDeskLockException extends Exception {}
