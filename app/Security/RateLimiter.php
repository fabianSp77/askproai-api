<?php

namespace App\Security;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class RateLimiter
{
    /**
     * Rate limit configurations per endpoint
     */
    private array $limits = [
        'api/retell/webhook' => ['requests' => 100, 'minutes' => 1],
        'api/calcom/webhook' => ['requests' => 100, 'minutes' => 1],
        'api/appointments' => ['requests' => 60, 'minutes' => 1],
        'api/customers' => ['requests' => 60, 'minutes' => 1],
        'admin/*' => ['requests' => 300, 'minutes' => 1],
    ];

    /**
     * Check if request should be rate limited
     */
    public function tooManyAttempts(string $key, string $endpoint): bool
    {
        $limit = $this->getLimit($endpoint);
        $attempts = $this->attempts($key);

        return $attempts >= $limit['requests'];
    }

    /**
     * Increment the counter for a given key
     */
    public function hit(string $key, string $endpoint): int
    {
        $limit = $this->getLimit($endpoint);
        $cleanKey = $this->cleanKey($key);
        $minutes = $limit['minutes'];
        
        // Use Cache facade which works with any cache driver
        $attempts = Cache::remember($cleanKey, $minutes * 60, function() {
            return 0;
        });
        
        // Increment the counter
        $attempts = Cache::increment($cleanKey);

        // Log if getting close to limit
        if ($attempts > $limit['requests'] * 0.8) {
            Log::warning('Rate limit warning', [
                'key' => $key,
                'endpoint' => $endpoint,
                'attempts' => $attempts,
                'limit' => $limit['requests']
            ]);
        }

        return $attempts;
    }

    /**
     * Get the number of attempts for the given key
     */
    public function attempts(string $key): int
    {
        return (int) Cache::get($this->cleanKey($key), 0);
    }

    /**
     * Reset attempts for the given key
     */
    public function clear(string $key): void
    {
        Cache::forget($this->cleanKey($key));
    }

    /**
     * Get remaining attempts
     */
    public function remaining(string $key, string $endpoint): int
    {
        $limit = $this->getLimit($endpoint);
        $attempts = $this->attempts($key);

        return max(0, $limit['requests'] - $attempts);
    }

    /**
     * Get retry after time in seconds
     */
    public function availableIn(string $key): int
    {
        // Since we're using Cache, we don't have direct TTL access
        // Return a default value based on the rate limit window
        return 60; // Default to 60 seconds
    }

    /**
     * Get limit configuration for endpoint
     */
    private function getLimit(string $endpoint): array
    {
        // Check exact match first
        if (isset($this->limits[$endpoint])) {
            return $this->limits[$endpoint];
        }

        // Check wildcard patterns
        foreach ($this->limits as $pattern => $limit) {
            if (fnmatch($pattern, $endpoint)) {
                return $limit;
            }
        }

        // Default limit
        return ['requests' => 60, 'minutes' => 1];
    }

    /**
     * Clean the rate limiter key
     */
    private function cleanKey(string $key): string
    {
        return 'rate_limit:' . preg_replace('/[^a-zA-Z0-9_\-:]/', '', $key);
    }
}