<?php

namespace App\Services\RateLimiter;

use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Log;

/**
 * Enhanced rate limiter with sliding window algorithm
 * Provides better accuracy than fixed window counters
 */
class EnhancedRateLimiter
{
    /**
     * Attempt to pass rate limit check
     * 
     * @param string $key Rate limit key
     * @param int $maxAttempts Maximum attempts allowed
     * @param int $decaySeconds Time window in seconds
     * @return bool True if within limits
     */
    public function attempt(string $key, int $maxAttempts = 60, int $decaySeconds = 60): bool
    {
        $now = microtime(true);
        $window = $now - $decaySeconds;
        
        // Use Redis commands directly
        // Remove old entries outside the window
        Redis::zremrangebyscore($key, 0, $window);
        
        // Count current entries in window
        $count = Redis::zcard($key);
        
        // Check if under limit
        if ($count >= $maxAttempts) {
            return false;
        }
        
        // Add current request
        Redis::zadd($key, $now, $now);
        
        // Set expiration
        Redis::expire($key, $decaySeconds + 1);
        
        return true;
    }
    
    /**
     * Get current usage for a key
     */
    public function current(string $key, int $decaySeconds = 60): int
    {
        $now = microtime(true);
        $window = $now - $decaySeconds;
        
        // Clean old entries and get count
        Redis::zremrangebyscore($key, 0, $window);
        return Redis::zcard($key);
    }
    
    /**
     * Reset rate limit for a key
     */
    public function reset(string $key): void
    {
        Redis::del($key);
    }
    
    /**
     * Get remaining attempts
     */
    public function remaining(string $key, int $maxAttempts = 60, int $decaySeconds = 60): int
    {
        $current = $this->current($key, $decaySeconds);
        return max(0, $maxAttempts - $current);
    }
    
    /**
     * Multi-tier rate limiting (e.g., per second, minute, hour)
     */
    public function multiTierAttempt(string $baseKey, array $tiers): bool
    {
        foreach ($tiers as $tier) {
            $key = "{$baseKey}:{$tier['window']}";
            
            if (!$this->attempt($key, $tier['limit'], $tier['window'])) {
                // Log which tier failed
                Log::warning('Rate limit exceeded', [
                    'key' => $key,
                    'tier' => $tier,
                    'current' => $this->current($key, $tier['window'])
                ]);
                
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Get rate limit headers for HTTP response
     */
    public function getHeaders(string $key, int $maxAttempts = 60, int $decaySeconds = 60): array
    {
        $current = $this->current($key, $decaySeconds);
        $remaining = max(0, $maxAttempts - $current);
        
        return [
            'X-RateLimit-Limit' => $maxAttempts,
            'X-RateLimit-Remaining' => $remaining,
            'X-RateLimit-Reset' => time() + $decaySeconds,
        ];
    }
}