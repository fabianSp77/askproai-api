<?php

namespace App\Services\Cache;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Log;
use Closure;

/**
 * Enhanced Cache Manager with multi-tier caching
 * L1: In-memory cache (array)
 * L2: Redis cache (fast)
 * L3: Database cache (persistent)
 */
class CacheManager
{
    private array $memoryCache = [];
    private int $memoryCacheSize = 0;
    private int $maxMemoryCacheSize = 50 * 1024 * 1024; // 50MB
    
    /**
     * Get or set cache with multi-tier support
     */
    public function remember(string $key, int $ttl, Closure $callback, array $options = [])
    {
        // Check L1 (memory) cache first
        if (isset($this->memoryCache[$key])) {
            return $this->memoryCache[$key]['value'];
        }
        
        // Check L2 (Redis) cache
        $value = Cache::store('redis')->get($key);
        if ($value !== null) {
            $this->setMemoryCache($key, $value);
            return $value;
        }
        
        // Generate value
        $value = $callback();
        
        // Store in all cache layers
        $this->setMultiTierCache($key, $value, $ttl, $options);
        
        return $value;
    }
    
    /**
     * Remember forever with compression for large data
     */
    public function rememberForever(string $key, Closure $callback, bool $compress = false)
    {
        $value = Cache::rememberForever($key, function() use ($callback, $compress) {
            $data = $callback();
            
            if ($compress && is_string($data) && strlen($data) > 1024) {
                return [
                    'compressed' => true,
                    'data' => gzcompress($data, 9)
                ];
            }
            
            return $data;
        });
        
        // Decompress if needed
        if (is_array($value) && isset($value['compressed']) && $value['compressed']) {
            return gzuncompress($value['data']);
        }
        
        return $value;
    }
    
    /**
     * Batch get multiple cache keys
     */
    public function many(array $keys): array
    {
        $results = [];
        $missingKeys = [];
        
        // Check memory cache first
        foreach ($keys as $key) {
            if (isset($this->memoryCache[$key])) {
                $results[$key] = $this->memoryCache[$key]['value'];
            } else {
                $missingKeys[] = $key;
            }
        }
        
        // Get missing from Redis
        if (!empty($missingKeys)) {
            $redisResults = Cache::store('redis')->many($missingKeys);
            
            foreach ($redisResults as $key => $value) {
                if ($value !== null) {
                    $results[$key] = $value;
                    $this->setMemoryCache($key, $value);
                }
            }
        }
        
        return $results;
    }
    
    /**
     * Set multiple cache values
     */
    public function putMany(array $values, int $ttl = 3600): bool
    {
        // Use Laravel's cache putMany
        Cache::store('redis')->putMany($values, $ttl);
        
        // Also store in memory cache
        foreach ($values as $key => $value) {
            $this->setMemoryCache($key, $value);
        }
        
        return true;
    }
    
    /**
     * Tagged cache for easy invalidation
     */
    public function tags(array $tags)
    {
        return Cache::tags($tags);
    }
    
    /**
     * Invalidate cache by pattern
     */
    public function forgetByPattern(string $pattern): int
    {
        $count = 0;
        
        // Clear from memory cache
        foreach (array_keys($this->memoryCache) as $key) {
            if (fnmatch($pattern, $key)) {
                unset($this->memoryCache[$key]);
                $count++;
            }
        }
        
        // Clear from Redis using SCAN
        try {
            $cursor = 0;
            do {
                $result = Redis::scan($cursor, ['MATCH' => $pattern, 'COUNT' => 100]);
                
                if (is_array($result) && count($result) === 2) {
                    [$cursor, $keys] = $result;
                    
                    if (!empty($keys)) {
                        Redis::del(...$keys);
                        $count += count($keys);
                    }
                } else {
                    break;
                }
            } while ($cursor != 0);
        } catch (\Exception $e) {
            // Fallback to simple pattern matching if scan is not available
            Log::warning('Redis SCAN not available, using fallback', ['error' => $e->getMessage()]);
        }
        
        return $count;
    }
    
    /**
     * Cache warming for critical data
     */
    public function warm(array $warmups): void
    {
        foreach ($warmups as $warmup) {
            $key = $warmup['key'];
            $callback = $warmup['callback'];
            $ttl = $warmup['ttl'] ?? 3600;
            
            try {
                $value = $callback();
                $this->setMultiTierCache($key, $value, $ttl);
            } catch (\Exception $e) {
                \Log::error('Cache warming failed', [
                    'key' => $key,
                    'error' => $e->getMessage()
                ]);
            }
        }
    }
    
    /**
     * Get cache statistics
     */
    public function stats(): array
    {
        $redisInfo = Redis::info();
        
        return [
            'memory_cache' => [
                'entries' => count($this->memoryCache),
                'size_bytes' => $this->memoryCacheSize,
                'hit_rate' => $this->calculateMemoryHitRate(),
            ],
            'redis_cache' => [
                'used_memory' => $redisInfo['used_memory_human'] ?? 'N/A',
                'connected_clients' => $redisInfo['connected_clients'] ?? 0,
                'total_commands' => $redisInfo['total_commands_processed'] ?? 0,
                'keyspace_hits' => $redisInfo['keyspace_hits'] ?? 0,
                'keyspace_misses' => $redisInfo['keyspace_misses'] ?? 0,
                'hit_rate' => $this->calculateRedisHitRate($redisInfo),
            ],
        ];
    }
    
    /**
     * Set memory cache with size management
     */
    private function setMemoryCache(string $key, $value): void
    {
        $serialized = serialize($value);
        $size = strlen($serialized);
        
        // Evict old entries if cache is too large
        while ($this->memoryCacheSize + $size > $this->maxMemoryCacheSize && !empty($this->memoryCache)) {
            $oldestKey = array_key_first($this->memoryCache);
            $this->memoryCacheSize -= strlen(serialize($this->memoryCache[$oldestKey]['value']));
            unset($this->memoryCache[$oldestKey]);
        }
        
        $this->memoryCache[$key] = [
            'value' => $value,
            'time' => microtime(true),
            'hits' => 0
        ];
        
        $this->memoryCacheSize += $size;
    }
    
    /**
     * Set cache in all tiers
     */
    private function setMultiTierCache(string $key, $value, int $ttl, array $options = []): void
    {
        // L1: Memory cache
        $this->setMemoryCache($key, $value);
        
        // L2: Redis cache
        Cache::store('redis')->put($key, $value, $ttl);
        
        // L3: Database cache (optional, for critical data)
        if ($options['persistent'] ?? false) {
            \DB::table('cache_entries')->updateOrInsert(
                ['key' => $key],
                [
                    'value' => serialize($value),
                    'expires_at' => now()->addSeconds($ttl),
                    'updated_at' => now()
                ]
            );
        }
    }
    
    /**
     * Calculate memory cache hit rate
     */
    private function calculateMemoryHitRate(): float
    {
        $totalHits = 0;
        $totalRequests = 0;
        
        foreach ($this->memoryCache as $entry) {
            $totalHits += $entry['hits'];
            $totalRequests += $entry['hits'] + 1;
        }
        
        return $totalRequests > 0 ? round(($totalHits / $totalRequests) * 100, 2) : 0;
    }
    
    /**
     * Calculate Redis hit rate
     */
    private function calculateRedisHitRate(array $info): float
    {
        $hits = $info['keyspace_hits'] ?? 0;
        $misses = $info['keyspace_misses'] ?? 0;
        $total = $hits + $misses;
        
        return $total > 0 ? round(($hits / $total) * 100, 2) : 0;
    }
}