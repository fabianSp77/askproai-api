<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class CalcomApiRateLimiter
{
    private const MAX_REQUESTS_PER_MINUTE = 60;
    private const CACHE_KEY = 'calcom_api_rate_limit';
    private const LOCK_KEY = 'calcom_api_rate_limit_lock';

    /**
     * Check if we can make an API call
     */
    public function canMakeRequest(): bool
    {
        $key = self::CACHE_KEY;
        $now = now();
        $minute = $now->format('Y-m-d-H-i');

        $count = Cache::get($key . ':' . $minute, 0);

        if ($count >= self::MAX_REQUESTS_PER_MINUTE) {
            Log::channel('calcom')->warning('[Cal.com] Rate limit reached', [
                'count' => $count,
                'minute' => $minute,
                'limit' => self::MAX_REQUESTS_PER_MINUTE
            ]);
            return false;
        }

        return true;
    }

    /**
     * Increment the request count
     */
    public function incrementRequestCount(): void
    {
        $now = now();
        $minute = $now->format('Y-m-d-H-i');
        $key = self::CACHE_KEY . ':' . $minute;

        Cache::increment($key);
        \Illuminate\Support\Facades\Redis::expire(config('cache.prefix') . $key, 120); // Expire after 2 minutes

        $count = Cache::get($key);

        if ($count % 10 === 0) {
            Log::channel('calcom')->debug('[Cal.com] API requests this minute', [
                'count' => $count,
                'minute' => $minute,
                'limit' => self::MAX_REQUESTS_PER_MINUTE
            ]);
        }
    }

    /**
     * Wait until we can make a request
     */
    public function waitForAvailability(): void
    {
        while (!$this->canMakeRequest()) {
            sleep(1); // Wait 1 second
        }
    }

    /**
     * Get remaining requests for current minute
     */
    public function getRemainingRequests(): int
    {
        $now = now();
        $minute = $now->format('Y-m-d-H-i');
        $key = self::CACHE_KEY . ':' . $minute;

        $count = Cache::get($key, 0);

        return max(0, self::MAX_REQUESTS_PER_MINUTE - $count);
    }

    /**
     * Reset rate limit (for testing)
     */
    public function reset(): void
    {
        $now = now();
        $minute = $now->format('Y-m-d-H-i');
        $key = self::CACHE_KEY . ':' . $minute;

        Cache::forget($key);
    }
}