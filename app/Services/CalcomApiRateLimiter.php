<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

/**
 * 🔒 SECURITY FIX: Atomic Rate Limiter (2025-11-11)
 *
 * Uses Redis Lua scripts for atomic check-and-increment operations
 * to prevent race conditions that could exceed Cal.com rate limits.
 *
 * PREVIOUS ISSUE:
 * Non-atomic check-and-increment allowed burst exceeding rate limit:
 * 1. Request A reads count=119 ✓
 * 2. Request B reads count=119 ✓
 * 3. Both increment → count=121 ❌ (exceeded limit!)
 *
 * FIX:
 * Lua script executes atomically in Redis, preventing race conditions.
 */
class CalcomApiRateLimiter
{
    // 🔧 FIX 2025-11-11: Updated to match Cal.com V2 API limit
    // Cal.com API Key rate limit: 120 req/min (was 60)
    // Reference: https://cal.com/docs/api-reference/v2/introduction#rate-limits
    private const MAX_REQUESTS_PER_MINUTE = 120;
    private const CACHE_KEY = 'calcom_api_rate_limit';
    private const LOCK_KEY = 'calcom_api_rate_limit_lock';
    private const TTL_SECONDS = 120; // Expire after 2 minutes

    /**
     * 🔒 ATOMIC: Check if we can make an API call and reserve slot
     *
     * Uses Lua script for atomic check-and-increment to prevent race conditions.
     *
     * @return bool True if request allowed and slot reserved, false if limit exceeded
     */
    public function canMakeRequest(): bool
    {
        $now = now();
        $minute = $now->format('Y-m-d-H-i');
        $key = $this->getCacheKey($minute);

        // 🔒 ATOMIC OPERATION: Check and increment in single Redis operation
        $result = $this->atomicCheckAndIncrement($key);

        if (!$result['allowed']) {
            Log::channel('calcom')->warning('[Cal.com] Rate limit reached', [
                'count' => $result['count'],
                'minute' => $minute,
                'limit' => self::MAX_REQUESTS_PER_MINUTE,
                'security' => 'Atomic rate limiter - race condition prevented'
            ]);
            return false;
        }

        // Log every 10th request for monitoring
        if ($result['count'] % 10 === 0) {
            Log::channel('calcom')->debug('[Cal.com] API requests this minute', [
                'count' => $result['count'],
                'minute' => $minute,
                'limit' => self::MAX_REQUESTS_PER_MINUTE,
                'remaining' => self::MAX_REQUESTS_PER_MINUTE - $result['count']
            ]);
        }

        return true;
    }

    /**
     * 🔒 ATOMIC: Check count and increment in single Redis operation
     *
     * Lua script ensures atomicity - no race condition possible.
     *
     * @param string $key Redis key
     * @return array{allowed: bool, count: int}
     */
    protected function atomicCheckAndIncrement(string $key): array
    {
        // Lua script for atomic check-and-increment
        $luaScript = <<<'LUA'
            local key = KEYS[1]
            local limit = tonumber(ARGV[1])
            local ttl = tonumber(ARGV[2])

            -- Get current count (0 if not exists)
            local count = tonumber(redis.call('GET', key) or "0")

            -- Check if under limit
            if count < limit then
                -- Increment counter
                count = redis.call('INCR', key)

                -- Set expiry on first increment
                if count == 1 then
                    redis.call('EXPIRE', key, ttl)
                end

                return {1, count}  -- Allowed, return new count
            else
                return {0, count}  -- Not allowed, return current count
            end
LUA;

        // Execute Lua script atomically in Redis
        $result = Redis::eval(
            $luaScript,
            1, // Number of keys
            $key, // KEYS[1]
            self::MAX_REQUESTS_PER_MINUTE, // ARGV[1]
            self::TTL_SECONDS // ARGV[2]
        );

        return [
            'allowed' => (bool) $result[0],
            'count' => (int) $result[1]
        ];
    }

    /**
     * @deprecated Use canMakeRequest() which now atomically increments
     *
     * This method is kept for backwards compatibility but does nothing,
     * as canMakeRequest() now performs atomic check-and-increment.
     */
    public function incrementRequestCount(): void
    {
        // DEPRECATED: No-op, increment now happens atomically in canMakeRequest()
        // Kept for backwards compatibility with existing code
    }

    /**
     * Wait until we can make a request
     *
     * Note: This method already calls canMakeRequest() which now atomically
     * reserves slots. Be careful with this in high-concurrency scenarios.
     */
    public function waitForAvailability(): void
    {
        $attempts = 0;
        $maxAttempts = 60; // Max 60 seconds wait

        while ($attempts < $maxAttempts) {
            // Sleep first to avoid immediate retry
            sleep(1);
            $attempts++;

            // Check if we can make request (atomically reserves slot if available)
            if ($this->canMakeRequest()) {
                return; // Slot reserved, exit
            }
        }

        // If we get here, we've waited 60 seconds and still no slot
        Log::channel('calcom')->error('[Cal.com] Rate limit wait timeout', [
            'waited_seconds' => $maxAttempts,
            'limit' => self::MAX_REQUESTS_PER_MINUTE
        ]);
    }

    /**
     * Get remaining requests for current minute
     */
    public function getRemainingRequests(): int
    {
        $now = now();
        $minute = $now->format('Y-m-d-H-i');
        $key = $this->getCacheKey($minute);

        $count = (int) Redis::get($key) ?: 0;

        return max(0, self::MAX_REQUESTS_PER_MINUTE - $count);
    }

    /**
     * Get cache key for a specific minute
     *
     * @param string $minute Format: Y-m-d-H-i
     * @return string
     */
    protected function getCacheKey(string $minute): string
    {
        return self::CACHE_KEY . ':' . $minute;
    }

    /**
     * Reset rate limit (for testing)
     */
    public function reset(): void
    {
        $now = now();
        $minute = $now->format('Y-m-d-H-i');
        $key = $this->getCacheKey($minute);

        Redis::del($key);
    }
}