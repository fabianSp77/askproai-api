<?php

namespace App\Services\RateLimiter;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use App\Exceptions\RateLimitExceededException;

class ApiRateLimiter
{
    /**
     * Rate limit configurations per service
     */
    private array $limits = [
        'calcom' => [
            'requests_per_minute' => 60,
            'requests_per_hour' => 1000,
            'backoff_minutes' => 5,
        ],
        'retell' => [
            'requests_per_minute' => 100,
            'requests_per_hour' => 5000,
            'backoff_minutes' => 3,
        ],
        'default' => [
            'requests_per_minute' => 30,
            'requests_per_hour' => 500,
            'backoff_minutes' => 1,
        ],
    ];
    
    /**
     * Check if request is allowed and increment counter
     */
    public function attempt(string $service, string $identifier = 'default'): bool
    {
        $config = $this->limits[$service] ?? $this->limits['default'];
        
        // Check if currently in backoff period
        if ($this->isInBackoff($service, $identifier)) {
            throw new RateLimitExceededException(
                "Rate limit exceeded for {$service}. Please wait before retrying."
            );
        }
        
        // Check minute limit
        $minuteKey = $this->getKey($service, $identifier, 'minute');
        $minuteCount = (int) Cache::get($minuteKey, 0);
        
        if ($minuteCount >= $config['requests_per_minute']) {
            $this->setBackoff($service, $identifier, $config['backoff_minutes']);
            throw new RateLimitExceededException(
                "Minute rate limit exceeded for {$service}. Max: {$config['requests_per_minute']} requests/minute"
            );
        }
        
        // Check hour limit
        $hourKey = $this->getKey($service, $identifier, 'hour');
        $hourCount = (int) Cache::get($hourKey, 0);
        
        if ($hourCount >= $config['requests_per_hour']) {
            $this->setBackoff($service, $identifier, $config['backoff_minutes']);
            throw new RateLimitExceededException(
                "Hour rate limit exceeded for {$service}. Max: {$config['requests_per_hour']} requests/hour"
            );
        }
        
        // Increment counters
        Cache::increment($minuteKey);
        Cache::increment($hourKey);
        
        // Set expiration if new keys
        if ($minuteCount === 0) {
            Cache::put($minuteKey, 1, 60);
        }
        if ($hourCount === 0) {
            Cache::put($hourKey, 1, 3600);
        }
        
        return true;
    }
    
    /**
     * Get current usage stats
     */
    public function getUsage(string $service, string $identifier = 'default'): array
    {
        $config = $this->limits[$service] ?? $this->limits['default'];
        
        $minuteKey = $this->getKey($service, $identifier, 'minute');
        $hourKey = $this->getKey($service, $identifier, 'hour');
        
        return [
            'minute' => [
                'current' => (int) Cache::get($minuteKey, 0),
                'limit' => $config['requests_per_minute'],
                'remaining' => max(0, $config['requests_per_minute'] - (int) Cache::get($minuteKey, 0)),
            ],
            'hour' => [
                'current' => (int) Cache::get($hourKey, 0),
                'limit' => $config['requests_per_hour'],
                'remaining' => max(0, $config['requests_per_hour'] - (int) Cache::get($hourKey, 0)),
            ],
            'in_backoff' => $this->isInBackoff($service, $identifier),
            'backoff_ends_at' => $this->getBackoffEndTime($service, $identifier),
        ];
    }
    
    /**
     * Reset rate limit counters
     */
    public function reset(string $service, string $identifier = 'default'): void
    {
        Cache::forget($this->getKey($service, $identifier, 'minute'));
        Cache::forget($this->getKey($service, $identifier, 'hour'));
        Cache::forget($this->getBackoffKey($service, $identifier));
        
        Log::info('Rate limit reset', [
            'service' => $service,
            'identifier' => $identifier,
        ]);
    }
    
    /**
     * Apply exponential backoff for repeated failures
     */
    public function applyBackoff(string $service, string $identifier = 'default', int $attempt = 1): int
    {
        $baseDelay = $this->limits[$service]['backoff_minutes'] ?? 1;
        $maxDelay = 60; // Max 1 hour
        
        // Exponential backoff: base * 2^(attempt-1)
        $delay = min($baseDelay * pow(2, $attempt - 1), $maxDelay);
        
        $this->setBackoff($service, $identifier, $delay);
        
        return $delay;
    }
    
    /**
     * Check if currently in backoff period
     */
    private function isInBackoff(string $service, string $identifier): bool
    {
        return Cache::has($this->getBackoffKey($service, $identifier));
    }
    
    /**
     * Set backoff period
     */
    private function setBackoff(string $service, string $identifier, int $minutes): void
    {
        $key = $this->getBackoffKey($service, $identifier);
        Cache::put($key, now()->addMinutes($minutes), $minutes * 60);
        
        Log::warning('API rate limit backoff applied', [
            'service' => $service,
            'identifier' => $identifier,
            'minutes' => $minutes,
        ]);
    }
    
    /**
     * Get backoff end time
     */
    private function getBackoffEndTime(string $service, string $identifier): ?string
    {
        $value = Cache::get($this->getBackoffKey($service, $identifier));
        return $value ? $value->format('Y-m-d H:i:s') : null;
    }
    
    /**
     * Generate cache key
     */
    private function getKey(string $service, string $identifier, string $period): string
    {
        return "rate_limit:{$service}:{$identifier}:{$period}";
    }
    
    /**
     * Generate backoff cache key
     */
    private function getBackoffKey(string $service, string $identifier): string
    {
        return "rate_limit_backoff:{$service}:{$identifier}";
    }
    
    /**
     * Middleware-friendly check
     */
    public function checkWebhook(string $service, string $clientIp): bool
    {
        try {
            return $this->attempt($service, $clientIp);
        } catch (RateLimitExceededException $e) {
            Log::warning('Webhook rate limit exceeded', [
                'service' => $service,
                'ip' => $clientIp,
                'message' => $e->getMessage(),
            ]);
            return false;
        }
    }
}