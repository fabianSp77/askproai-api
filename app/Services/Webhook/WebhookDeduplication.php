<?php

namespace App\Services\Webhook;

use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Log;

/**
 * Redis-based webhook deduplication service
 * Uses atomic operations to prevent race conditions
 */
class WebhookDeduplication
{
    /**
     * Check if webhook is duplicate and set if not
     * Uses Redis SETNX for atomic operation
     */
    public function checkAndSet(string $key, int $ttl = 3600): bool
    {
        try {
            // Use Lua script for atomic check-and-set with TTL
            $script = <<<'LUA'
                local key = KEYS[1]
                local value = ARGV[1]
                local ttl = ARGV[2]
                
                -- Try to set if not exists
                local result = redis.call('SETNX', key, value)
                
                if result == 1 then
                    -- Key was set, add expiration
                    redis.call('EXPIRE', key, ttl)
                    return 1
                else
                    -- Key already exists
                    return 0
                end
LUA;
            
            $result = Redis::eval(
                $script,
                1,
                $key,
                time(),
                $ttl
            );
            
            return $result === 1;
            
        } catch (\Exception $e) {
            // Log error but don't fail the request
            Log::error('Deduplication check failed', [
                'key' => $key,
                'error' => $e->getMessage()
            ]);
            
            // Fail open - process webhook to avoid data loss
            return true;
        }
    }
    
    /**
     * Bulk check for multiple keys
     */
    public function bulkCheckAndSet(array $keys, int $ttl = 3600): array
    {
        $pipeline = Redis::pipeline();
        
        foreach ($keys as $key) {
            $pipeline->set($key, time(), 'NX', 'EX', $ttl);
        }
        
        $results = $pipeline->execute();
        
        $processed = [];
        foreach ($keys as $index => $key) {
            $processed[$key] = $results[$index] === 'OK';
        }
        
        return $processed;
    }
    
    /**
     * Check if key exists without setting
     */
    public function exists(string $key): bool
    {
        return Redis::exists($key) > 0;
    }
    
    /**
     * Remove deduplication key (for testing/cleanup)
     */
    public function remove(string $key): bool
    {
        return Redis::del($key) > 0;
    }
    
    /**
     * Get remaining TTL for a key
     */
    public function ttl(string $key): int
    {
        return Redis::ttl($key);
    }
}