<?php

namespace App\Services\Booking;

use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Cache\Lock;
use Closure;
use Exception;

class BookingLockService
{
    /**
     * TTL for locks in seconds
     */
    private int $ttl = 120;

    /**
     * Max wait time for acquiring lock in seconds
     */
    private int $maxWait = 10;

    /**
     * Acquire lock for staff member at specific time
     */
    public function acquireStaffLock(string $staffId, Carbon $start, Carbon $end, ?int $resourceId = null): ?Lock
    {
        $key = $this->buildLockKey($staffId, $start, $end, $resourceId);

        Log::debug('Attempting to acquire lock', [
            'key' => $key,
            'ttl' => $this->ttl
        ]);

        $lock = Cache::lock($key, $this->ttl);

        if ($lock->get()) {
            Log::info('Lock acquired successfully', ['key' => $key]);
            return $lock;
        }

        // Try to wait for lock
        $acquired = $lock->block($this->maxWait);

        if ($acquired) {
            Log::info('Lock acquired after waiting', ['key' => $key]);
            return $lock;
        }

        Log::warning('Failed to acquire lock', ['key' => $key]);
        return null;
    }

    /**
     * Acquire lock for composite booking
     */
    public function acquireCompositeLock(string $compositeUid): ?Lock
    {
        $key = "composite:{$compositeUid}";
        $lock = Cache::lock($key, $this->ttl);

        if ($lock->get()) {
            return $lock;
        }

        return null;
    }

    /**
     * Try to acquire multiple locks atomically
     */
    public function acquireMultipleLocks(array $lockRequests): array
    {
        // Sort lock keys to prevent deadlocks
        usort($lockRequests, function($a, $b) {
            return $this->buildLockKey(
                $a['staff_id'],
                $a['start'],
                $a['end'],
                $a['resource_id'] ?? null
            ) <=> $this->buildLockKey(
                $b['staff_id'],
                $b['start'],
                $b['end'],
                $b['resource_id'] ?? null
            );
        });

        $locks = [];
        $acquired = [];

        try {
            foreach ($lockRequests as $request) {
                $lock = $this->acquireStaffLock(
                    $request['staff_id'],
                    Carbon::parse($request['start']),
                    Carbon::parse($request['end']),
                    $request['resource_id'] ?? null
                );

                if (!$lock) {
                    // Failed to acquire lock, release all previously acquired
                    $this->releaseMultipleLocks($acquired);
                    return [];
                }

                $locks[] = [
                    'lock' => $lock,
                    'key' => $this->buildLockKey(
                        $request['staff_id'],
                        Carbon::parse($request['start']),
                        Carbon::parse($request['end']),
                        $request['resource_id'] ?? null
                    )
                ];
                $acquired[] = $lock;
            }

            Log::info('All locks acquired successfully', ['count' => count($locks)]);
            return $locks;

        } catch (Exception $e) {
            Log::error('Error acquiring locks', ['error' => $e->getMessage()]);
            $this->releaseMultipleLocks($acquired);
            return [];
        }
    }

    /**
     * Release multiple locks
     */
    public function releaseMultipleLocks(array $locks): void
    {
        foreach ($locks as $lock) {
            try {
                if ($lock instanceof Lock) {
                    $lock->release();
                } elseif (is_array($lock) && isset($lock['lock'])) {
                    $lock['lock']->release();
                }
            } catch (Exception $e) {
                Log::error('Error releasing lock', ['error' => $e->getMessage()]);
            }
        }
    }

    /**
     * Execute callback with locks
     */
    public function withLocks(array $lockRequests, Closure $callback)
    {
        $locks = $this->acquireMultipleLocks($lockRequests);

        if (empty($locks)) {
            throw new LockException('Failed to acquire required locks');
        }

        try {
            return $callback($locks);
        } finally {
            $this->releaseMultipleLocks($locks);
        }
    }

    /**
     * Check if time slot is locked
     */
    public function isLocked(string $staffId, Carbon $start, Carbon $end, ?int $resourceId = null): bool
    {
        $key = $this->buildLockKey($staffId, $start, $end, $resourceId);
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
     * Build lock key
     */
    private function buildLockKey(string $staffId, Carbon $start, Carbon $end, ?int $resourceId = null): string
    {
        $key = "booking:staff:{$staffId}:time:{$start->format('YmdHis')}:{$end->format('YmdHis')}";

        if ($resourceId) {
            $key .= ":resource:{$resourceId}";
        }

        return $key;
    }

    /**
     * Clear all locks (emergency use only)
     */
    public function clearAllLocks(): void
    {
        Log::warning('Clearing all booking locks - emergency operation');

        // This would need Redis SCAN command to find all booking:* keys
        // For now, log the attempt
        Log::warning('Lock clearing not fully implemented - requires Redis access');
    }

    /**
     * Get lock statistics
     */
    public function getLockStats(): array
    {
        return [
            'ttl' => $this->ttl,
            'max_wait' => $this->maxWait,
            'driver' => config('cache.default'),
            'redis_connection' => config('cache.stores.redis.connection')
        ];
    }

    /**
     * Set custom TTL
     */
    public function setTtl(int $seconds): void
    {
        if ($seconds < 10 || $seconds > 300) {
            throw new Exception('TTL must be between 10 and 300 seconds');
        }

        $this->ttl = $seconds;
    }

    /**
     * Set max wait time
     */
    public function setMaxWait(int $seconds): void
    {
        if ($seconds < 0 || $seconds > 30) {
            throw new Exception('Max wait must be between 0 and 30 seconds');
        }

        $this->maxWait = $seconds;
    }
}

class LockException extends Exception {}