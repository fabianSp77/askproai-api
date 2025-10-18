<?php

namespace App\Services\Cache;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Exception;

/**
 * Tiered Cache Manager - Multi-level caching strategy
 *
 * Implements 3-level caching for optimal performance:
 *
 * L1 (Hot Cache - In-Memory):
 *  - TTL: 1-5 seconds
 *  - Driver: Array/APCu (ultra-fast, single-request)
 *  - Data: Current user session, request-scoped values
 *  - Hit Cost: <1ms
 *  - Miss Cost: <10ms (fallback to L2)
 *
 * L2 (Warm Cache - Redis):
 *  - TTL: 5-60 minutes
 *  - Driver: Redis
 *  - Data: Availability, schedules, configurations
 *  - Hit Cost: 5-20ms
 *  - Miss Cost: 100-500ms (fallback to L3)
 *
 * L3 (Cold Cache - Database):
 *  - TTL: Persistent (manual invalidation)
 *  - Driver: Database
 *  - Data: Companies, staff, services (slow-changing)
 *  - Hit Cost: 50-200ms
 *  - Miss Cost: 200-1000ms (query + compute)
 *
 * Automatic Promotion:
 * - L3 miss → compute & store in L2 & L3
 * - L2 miss → fetch from L3 & store in L2
 * - L1 hit → update access timestamp for promotion
 */
class TieredCacheManager
{
    /**
     * L1 Cache (In-Memory - Request scope)
     */
    private array $l1Cache = [];

    /**
     * Get value from tiered cache with automatic promotion
     *
     * Strategy:
     * 1. Try L1 (in-memory) - returns immediately
     * 2. Try L2 (Redis) - promote to L1
     * 3. Try L3 (Database) - promote to L2 & L1
     * 4. Compute & store in all levels
     *
     * @param string $key Cache key (use format: domain:entity:id)
     * @param callable $callback Function to compute value if not cached
     * @param array $options TTL overrides and metadata
     * @return mixed Cached or computed value
     */
    public function remember(string $key, callable $callback, array $options = []): mixed
    {
        // L1: Fast in-memory check
        if (isset($this->l1Cache[$key])) {
            Log::debug('🚀 L1 cache hit', ['key' => $key]);
            return $this->l1Cache[$key];
        }

        // L2: Redis check
        $l2TTL = $options['l2_ttl'] ?? 300;  // 5 minutes default
        $l2Value = Cache::store('redis')->get($key);

        if ($l2Value !== null) {
            Log::debug('⚡ L2 cache hit', ['key' => $key]);
            // Promote L2 → L1
            $this->l1Cache[$key] = $l2Value;
            return $l2Value;
        }

        // L3: Database check (via callback context)
        $l3TTL = $options['l3_ttl'] ?? null;
        $l3Store = $options['l3_store'] ?? null;

        if ($l3Store) {
            $l3Value = $l3Store->get($key);
            if ($l3Value !== null) {
                Log::debug('💾 L3 cache hit', ['key' => $key]);
                // Promote L3 → L2 → L1
                Cache::store('redis')->put($key, $l3Value, $l2TTL);
                $this->l1Cache[$key] = $l3Value;
                return $l3Value;
            }
        }

        // Cache miss: Compute value
        Log::debug('💥 Cache miss - computing', ['key' => $key]);
        $value = $callback();

        // Store in all levels
        $this->storeInAllLevels($key, $value, $options);

        return $value;
    }

    /**
     * Store value in all cache levels
     *
     * @param string $key Cache key
     * @param mixed $value Value to cache
     * @param array $options TTL configuration
     */
    private function storeInAllLevels(string $key, mixed $value, array $options): void
    {
        // L1: In-memory (request scope - no TTL needed)
        $this->l1Cache[$key] = $value;

        // L2: Redis
        $l2TTL = $options['l2_ttl'] ?? 300;
        try {
            Cache::store('redis')->put($key, $value, $l2TTL);
            Log::debug('Stored in L2 (Redis)', ['key' => $key, 'ttl' => $l2TTL]);
        } catch (Exception $e) {
            Log::warning('Failed to store in L2', ['key' => $key, 'error' => $e->getMessage()]);
        }

        // L3: Database (if handler provided)
        if ($options['l3_store'] ?? null) {
            try {
                $options['l3_store']->put($key, $value);
                Log::debug('Stored in L3 (Database)', ['key' => $key]);
            } catch (Exception $e) {
                Log::warning('Failed to store in L3', ['key' => $key, 'error' => $e->getMessage()]);
            }
        }
    }

    /**
     * Invalidate key across all cache levels
     *
     * @param string $key Cache key
     * @return bool Success status
     */
    public function forget(string $key): bool
    {
        // L1: In-memory
        unset($this->l1Cache[$key]);

        // L2: Redis
        try {
            Cache::store('redis')->forget($key);
        } catch (Exception $e) {
            Log::warning('Failed to invalidate L2', ['key' => $key, 'error' => $e->getMessage()]);
        }

        return true;
    }

    /**
     * Invalidate by pattern across all levels
     *
     * @param array $patterns Cache key patterns
     * @return bool Success status
     */
    public function forgetByPatterns(array $patterns): bool
    {
        // L1: In-memory pattern clear
        foreach ($patterns as $pattern) {
            $this->clearL1Pattern($pattern);
        }

        // L2: Redis pattern clear
        try {
            $strategies = new InvalidationStrategies();
            $strategies->invalidateByPatterns($patterns);
        } catch (Exception $e) {
            Log::warning('Failed to invalidate L2 by pattern', ['error' => $e->getMessage()]);
        }

        return true;
    }

    /**
     * Clear L1 cache by pattern
     *
     * @param string $pattern Wildcard pattern
     */
    private function clearL1Pattern(string $pattern): void
    {
        $regex = str_replace('*', '.*', preg_quote($pattern, '/'));
        foreach ($this->l1Cache as $key => $value) {
            if (preg_match("/^{$regex}$/", $key)) {
                unset($this->l1Cache[$key]);
            }
        }
    }

    /**
     * Get tier-specific statistics
     *
     * Returns cache performance metrics for each tier
     *
     * @return array Statistics by tier
     */
    public function getStats(): array
    {
        return [
            'l1' => [
                'keys' => count($this->l1Cache),
                'driver' => 'in-memory',
                'ttl' => 'request-scope',
            ],
            'l2' => [
                'driver' => 'redis',
                'ttl' => '5-60 minutes',
                'hits' => Cache::get('cache:l2:hits', 0),
                'misses' => Cache::get('cache:l2:misses', 0),
            ],
            'l3' => [
                'driver' => 'database',
                'ttl' => 'persistent',
                'hits' => Cache::get('cache:l3:hits', 0),
                'misses' => Cache::get('cache:l3:misses', 0),
            ],
        ];
    }

    /**
     * Analyze cache effectiveness
     *
     * Returns insights on tier usage and effectiveness
     *
     * @return array Analysis
     */
    public function analyzeEffectiveness(): array
    {
        $l2Hits = Cache::get('cache:l2:hits', 0);
        $l2Misses = Cache::get('cache:l2:misses', 0);
        $l3Hits = Cache::get('cache:l3:hits', 0);
        $l3Misses = Cache::get('cache:l3:misses', 0);

        $l2Total = $l2Hits + $l2Misses;
        $l3Total = $l3Hits + $l3Misses;

        return [
            'l2_hit_rate' => $l2Total > 0 ? round(($l2Hits / $l2Total) * 100, 2) : 0,
            'l3_hit_rate' => $l3Total > 0 ? round(($l3Hits / $l3Total) * 100, 2) : 0,
            'l2_requests' => $l2Total,
            'l3_requests' => $l3Total,
            'cache_efficiency' => $this->calculateEfficiency($l2Hits, $l2Misses, $l3Hits, $l3Misses),
            'recommendations' => $this->getRecommendations($l2Hits, $l2Misses, $l3Hits, $l3Misses),
        ];
    }

    /**
     * Calculate overall cache efficiency score (0-100)
     *
     * @return float Efficiency percentage
     */
    private function calculateEfficiency(int $l2Hits, int $l2Misses, int $l3Hits, int $l3Misses): float
    {
        $totalHits = $l2Hits + $l3Hits;
        $totalRequests = $totalHits + $l2Misses + $l3Misses;

        if ($totalRequests === 0) {
            return 0.0;
        }

        // Weight: L2 hits worth more than L3 hits (faster)
        $hitScore = ($l2Hits * 2) + $l3Hits;
        $maxScore = $totalRequests * 2;

        return min(100, round(($hitScore / $maxScore) * 100, 2));
    }

    /**
     * Get cache optimization recommendations
     *
     * @return array Recommendations
     */
    private function getRecommendations(int $l2Hits, int $l2Misses, int $l3Hits, int $l3Misses): array
    {
        $recommendations = [];

        $l2HitRate = $l2Hits + $l2Misses > 0 ? $l2Hits / ($l2Hits + $l2Misses) : 0;
        if ($l2HitRate < 0.8) {
            $recommendations[] = 'L2 hit rate low (<80%) - increase TTL or cache more data';
        }

        $l3HitRate = $l3Hits + $l3Misses > 0 ? $l3Hits / ($l3Hits + $l3Misses) : 0;
        if ($l3HitRate < 0.9) {
            $recommendations[] = 'L3 hit rate low (<90%) - consider warming cache on startup';
        }

        if ($l2Misses > 100 && $l3Misses > 50) {
            $recommendations[] = 'High miss rate - implement predictive pre-fetching';
        }

        return $recommendations;
    }

    /**
     * Clear entire L1 cache
     *
     * Use at end of request to prevent memory leak
     */
    public function clearL1(): void
    {
        $this->l1Cache = [];
        Log::debug('L1 cache cleared');
    }
}
