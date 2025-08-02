<?php

namespace App\Gateway\Cache;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

class GatewayCacheManager
{
    private array $config;
    private CacheInvalidator $invalidator;

    public function __construct(array $config = [], CacheInvalidator $invalidator = null)
    {
        $this->config = $config;
        $this->invalidator = $invalidator ?? new CacheInvalidator();
    }

    /**
     * Get cached response or execute callback
     */
    public function get(Request $request, callable $callback): Response
    {
        if (!$this->shouldCache($request)) {
            return $callback();
        }

        $cacheKey = $this->getCacheKey($request);
        $ttl = $this->getTtl($request);

        // Try L1 Cache (Redis, fast access)
        $cached = $this->getFromL1Cache($cacheKey);
        if ($cached !== null) {
            $this->recordCacheHit($cacheKey, 'l1');
            return $this->unserializeResponse($cached);
        }

        // Try L2 Cache (Redis, longer TTL)
        $cached = $this->getFromL2Cache($cacheKey);
        if ($cached !== null) {
            $this->recordCacheHit($cacheKey, 'l2');
            $this->putL1Cache($cacheKey, $cached, min($ttl, 300)); // Max 5 min for L1
            return $this->unserializeResponse($cached);
        }

        // Cache miss - execute callback
        $this->recordCacheMiss($cacheKey);
        $response = $callback();

        // Cache successful responses
        if ($this->shouldCacheResponse($response)) {
            $serialized = $this->serializeResponse($response);
            $this->putAllLevels($cacheKey, $serialized, $ttl);
        }

        return $response;
    }

    /**
     * Check if request should be cached
     */
    private function shouldCache(Request $request): bool
    {
        // Don't cache if disabled
        if (!($this->config['enabled'] ?? true)) {
            return false;
        }

        // Only cache GET requests
        if ($request->method() !== 'GET') {
            return false;
        }

        // Don't cache requests with certain headers
        $noCacheHeaders = ['Cache-Control', 'Pragma'];
        foreach ($noCacheHeaders as $header) {
            if ($request->hasHeader($header) && strpos($request->header($header), 'no-cache') !== false) {
                return false;
            }
        }

        // Don't cache authenticated requests with sensitive data
        if ($this->containsSensitiveData($request)) {
            return false;
        }

        return true;
    }

    /**
     * Check if response should be cached
     */
    private function shouldCacheResponse(Response $response): bool
    {
        // Only cache successful responses
        if ($response->getStatusCode() < 200 || $response->getStatusCode() >= 300) {
            return false;
        }

        // Check for no-cache headers
        if ($response->headers->has('Cache-Control')) {
            $cacheControl = $response->headers->get('Cache-Control');
            if (strpos($cacheControl, 'no-cache') !== false || strpos($cacheControl, 'no-store') !== false) {
                return false;
            }
        }

        return true;
    }

    /**
     * Generate cache key for request
     */
    private function getCacheKey(Request $request): string
    {
        $user = $request->user();
        $parts = [
            'gateway_cache',
            $request->path(),
            $request->method(),
        ];

        // Include user context if enabled
        if ($this->config['cache_keys']['include_user_context'] ?? true) {
            $parts[] = $user ? "user:{$user->id}" : 'guest';
        }

        // Include company context if enabled
        if ($this->config['cache_keys']['include_company_context'] ?? true) {
            $parts[] = $user ? "company:{$user->company_id}" : 'no_company';
        }

        // Include query parameters if enabled
        if ($this->config['cache_keys']['include_query_params'] ?? true) {
            $queryString = $request->getQueryString();
            $parts[] = $queryString ? md5($queryString) : 'no_query';
        }

        return implode(':', $parts);
    }

    /**
     * Get TTL for request
     */
    private function getTtl(Request $request): int
    {
        $endpoint = $this->normalizeEndpoint($request->path());
        
        return $this->config['endpoint_ttls'][$endpoint] 
            ?? $this->config['default_ttl'] 
            ?? 300;
    }

    /**
     * Get from L1 cache (fast, short TTL)
     */
    private function getFromL1Cache(string $key): ?array
    {
        try {
            $cached = Redis::get("l1:{$key}");
            return $cached ? json_decode($cached, true) : null;
        } catch (\Exception $e) {
            Log::warning('L1 cache read failed', ['key' => $key, 'error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Get from L2 cache (slower, longer TTL)
     */
    private function getFromL2Cache(string $key): ?array
    {
        try {
            $cached = Redis::get("l2:{$key}");
            return $cached ? json_decode($cached, true) : null;
        } catch (\Exception $e) {
            Log::warning('L2 cache read failed', ['key' => $key, 'error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Put in L1 cache
     */
    private function putL1Cache(string $key, array $data, int $ttl): void
    {
        try {
            Redis::setex("l1:{$key}", $ttl, json_encode($data));
        } catch (\Exception $e) {
            Log::warning('L1 cache write failed', ['key' => $key, 'error' => $e->getMessage()]);
        }
    }

    /**
     * Put in L2 cache
     */
    private function putL2Cache(string $key, array $data, int $ttl): void
    {
        try {
            Redis::setex("l2:{$key}", $ttl, json_encode($data));
        } catch (\Exception $e) {
            Log::warning('L2 cache write failed', ['key' => $key, 'error' => $e->getMessage()]);
        }
    }

    /**
     * Put in all cache levels
     */
    private function putAllLevels(string $key, array $data, int $ttl): void
    {
        $this->putL1Cache($key, $data, min($ttl, 300)); // L1: max 5 minutes
        $this->putL2Cache($key, $data, $ttl);           // L2: full TTL
    }

    /**
     * Serialize response for caching
     */
    private function serializeResponse(Response $response): array
    {
        return [
            'status' => $response->getStatusCode(),
            'headers' => $response->headers->all(),
            'content' => $response->getContent(),
            'cached_at' => now()->timestamp,
        ];
    }

    /**
     * Unserialize cached response
     */
    private function unserializeResponse(array $cached): Response
    {
        $response = new Response($cached['content'], $cached['status']);
        
        // Add original headers
        foreach ($cached['headers'] as $name => $values) {
            $response->headers->set($name, $values);
        }

        // Add cache headers
        $response->headers->set('X-Cache', 'HIT');
        $response->headers->set('X-Cache-Age', now()->timestamp - $cached['cached_at']);

        return $response;
    }

    /**
     * Check if request contains sensitive data
     */
    private function containsSensitiveData(Request $request): bool
    {
        $sensitivePatterns = [
            '/password/',
            '/token/',
            '/api[_-]?key/',
            '/secret/',
            '/private/',
        ];

        $path = strtolower($request->path());
        foreach ($sensitivePatterns as $pattern) {
            if (preg_match($pattern, $path)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Normalize endpoint for configuration lookup
     */
    private function normalizeEndpoint(string $path): string
    {
        // Remove IDs and normalize paths
        $path = preg_replace('/\/\d+(?=\/|$)/', '/{id}', $path);
        $path = preg_replace('/\/[a-f0-9-]{36}(?=\/|$)/', '/{uuid}', $path);
        
        return $path;
    }

    /**
     * Record cache hit for metrics
     */
    private function recordCacheHit(string $key, string $level): void
    {
        if (app()->bound('gateway.metrics')) {
            app('gateway.metrics')->recordCacheHit($key, true, $level);
        }
    }

    /**
     * Record cache miss for metrics
     */
    private function recordCacheMiss(string $key): void
    {
        if (app()->bound('gateway.metrics')) {
            app('gateway.metrics')->recordCacheHit($key, false);
        }
    }

    /**
     * Invalidate cache by pattern
     */
    public function invalidateByPattern(string $pattern): int
    {
        try {
            $keys = Redis::keys("*{$pattern}*");
            if (!empty($keys)) {
                $deleted = Redis::del($keys);
                Log::info('Cache invalidated', ['pattern' => $pattern, 'keys_deleted' => $deleted]);
                return $deleted;
            }
            return 0;
        } catch (\Exception $e) {
            Log::error('Cache invalidation failed', ['pattern' => $pattern, 'error' => $e->getMessage()]);
            return 0;
        }
    }

    /**
     * Invalidate cache by event
     */
    public function invalidateByEvent(string $event): void
    {
        $this->invalidator->invalidateByEvent($event);
    }

    /**
     * Get cache health status
     */
    public function getHealthStatus(): array
    {
        try {
            $info = Redis::info('memory');
            
            return [
                'status' => 'healthy',
                'l1_keys' => Redis::eval('return #redis.call("keys", "l1:*")', 0),
                'l2_keys' => Redis::eval('return #redis.call("keys", "l2:*")', 0),
                'memory_used' => $info['used_memory_human'] ?? 'unknown',
                'hit_rate' => $this->calculateHitRate(),
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'unhealthy',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Calculate cache hit rate from metrics
     */
    private function calculateHitRate(): ?float
    {
        try {
            $hits = Redis::get('cache_metrics:hits') ?? 0;
            $misses = Redis::get('cache_metrics:misses') ?? 0;
            $total = $hits + $misses;
            
            return $total > 0 ? ($hits / $total) * 100 : null;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Clear all cache levels
     */
    public function flush(): bool
    {
        try {
            $l1Keys = Redis::keys('l1:*');
            $l2Keys = Redis::keys('l2:*');
            
            if (!empty($l1Keys)) Redis::del($l1Keys);
            if (!empty($l2Keys)) Redis::del($l2Keys);
            
            Log::info('Gateway cache flushed', [
                'l1_keys_deleted' => count($l1Keys),
                'l2_keys_deleted' => count($l2Keys),
            ]);
            
            return true;
        } catch (\Exception $e) {
            Log::error('Gateway cache flush failed', ['error' => $e->getMessage()]);
            return false;
        }
    }
}