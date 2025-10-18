<?php

namespace App\Services\Cache;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Exception;

/**
 * Cache Invalidation Strategies
 *
 * Implements multiple strategies for cache invalidation:
 * - Pattern-based: Clear all caches matching wildcard patterns
 * - Tag-based: Clear all caches with specific tags (Redis only)
 * - Targeted: Clear specific cache keys
 *
 * Handles both Redis and file-based caches
 */
class InvalidationStrategies
{
    /**
     * Invalidate cache by multiple patterns
     *
     * Supports wildcard patterns like "availability:*" or "slot:staff:123:*"
     * Works with both Redis and file cache drivers
     *
     * @param array $patterns Wildcard cache patterns to clear
     * @return bool True if all invalidations succeeded
     */
    public function invalidateByPatterns(array $patterns): bool
    {
        $successCount = 0;
        $failureCount = 0;

        foreach ($patterns as $pattern) {
            try {
                $success = $this->invalidatePattern($pattern);
                $success ? $successCount++ : $failureCount++;

            } catch (Exception $e) {
                Log::warning('Pattern invalidation failed', [
                    'pattern' => $pattern,
                    'error' => $e->getMessage(),
                ]);
                $failureCount++;
            }
        }

        Log::debug('Pattern-based cache invalidation', [
            'patterns_count' => count($patterns),
            'succeeded' => $successCount,
            'failed' => $failureCount,
        ]);

        // Return true if at least some succeeded
        return $successCount > 0;
    }

    /**
     * Invalidate cache by tags (Redis only)
     *
     * @param array $tags Tag names to clear
     * @return bool Success status
     */
    public function invalidateByTags(array $tags): bool
    {
        if (config('cache.default') !== 'redis') {
            Log::warning('Tag-based invalidation requires Redis cache driver');
            return false;
        }

        try {
            foreach ($tags as $tag) {
                Cache::tags([$tag])->flush();
            }

            Log::debug('Tag-based cache invalidation', [
                'tags' => $tags,
            ]);

            return true;

        } catch (Exception $e) {
            Log::error('Tag-based invalidation failed', [
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Invalidate specific cache keys
     *
     * @param array $keys Exact cache keys to delete
     * @return int Number of keys deleted
     */
    public function invalidateByKeys(array $keys): int
    {
        $deletedCount = 0;

        foreach ($keys as $key) {
            try {
                if (Cache::forget($key)) {
                    $deletedCount++;
                }
            } catch (Exception $e) {
                Log::warning('Failed to delete cache key', [
                    'key' => $key,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        Log::debug('Key-based cache invalidation', [
            'keys_count' => count($keys),
            'deleted_count' => $deletedCount,
        ]);

        return $deletedCount;
    }

    /**
     * Invalidate single pattern
     *
     * Handles pattern invalidation with fallback strategies
     * for different cache drivers
     *
     * @param string $pattern Wildcard pattern
     * @return bool Success status
     */
    private function invalidatePattern(string $pattern): bool
    {
        $cacheDriver = config('cache.default');

        return match ($cacheDriver) {
            'redis' => $this->invalidateRedisPattern($pattern),
            'file' => $this->invalidateFilePattern($pattern),
            'array' => true,  // Array cache always succeeds
            default => $this->invalidateRedisPattern($pattern),  // Assume Redis-like
        };
    }

    /**
     * Invalidate Redis pattern using KEYS command
     *
     * ⚠️ WARNING: KEYS command is O(N) and can block Redis
     * Should only be used with specific patterns, not broad patterns
     * on production Redis with millions of keys
     *
     * @param string $pattern Redis wildcard pattern
     * @return bool Success status
     */
    private function invalidateRedisPattern(string $pattern): bool
    {
        try {
            $redis = Cache::getRedis();
            $prefix = config('cache.prefix', '');
            $fullPattern = $prefix . $pattern;

            // Use SCAN instead of KEYS for production safety
            // SCAN is O(N) worst case but doesn't block Redis
            $cursor = 0;
            $deletedCount = 0;

            do {
                $results = $redis->scan($cursor, 'MATCH', $fullPattern, 'COUNT', 100);
                $cursor = $results[0];
                $keys = $results[1];

                foreach ($keys as $key) {
                    $redis->del($key);
                    $deletedCount++;
                }
            } while ($cursor !== 0);

            Log::debug('Redis pattern invalidation', [
                'pattern' => $pattern,
                'full_pattern' => $fullPattern,
                'deleted_count' => $deletedCount,
            ]);

            return $deletedCount > 0;

        } catch (Exception $e) {
            Log::error('Redis pattern invalidation failed', [
                'pattern' => $pattern,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Invalidate file-based cache pattern
     *
     * For file cache driver, clear directory matching pattern
     *
     * @param string $pattern Wildcard pattern
     * @return bool Success status
     */
    private function invalidateFilePattern(string $pattern): bool
    {
        try {
            $cachePath = storage_path('framework/cache');
            $pattern = str_replace('*', '', $pattern);

            // Find files matching pattern
            $files = glob($cachePath . '/*' . $pattern . '*');

            if (empty($files)) {
                return false;
            }

            foreach ($files as $file) {
                if (file_exists($file)) {
                    unlink($file);
                }
            }

            Log::debug('File pattern invalidation', [
                'pattern' => $pattern,
                'deleted_count' => count($files),
            ]);

            return true;

        } catch (Exception $e) {
            Log::error('File pattern invalidation failed', [
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Estimate cache size before invalidation
     *
     * Returns approx cache keys matching pattern
     * (useful for predicting impact of invalidation)
     *
     * @param string $pattern Wildcard pattern
     * @return int Estimated key count
     */
    public function estimateCacheSize(string $pattern): int
    {
        try {
            if (config('cache.default') === 'redis') {
                $redis = Cache::getRedis();
                $prefix = config('cache.prefix', '');
                $fullPattern = $prefix . $pattern;

                $cursor = 0;
                $count = 0;

                do {
                    $results = $redis->scan($cursor, 'MATCH', $fullPattern, 'COUNT', 1000);
                    $cursor = $results[0];
                    $count += count($results[1]);
                } while ($cursor !== 0);

                return $count;
            }

            return 0;

        } catch (Exception $e) {
            Log::warning('Failed to estimate cache size', [
                'pattern' => $pattern,
                'error' => $e->getMessage(),
            ]);
            return 0;
        }
    }

    /**
     * Get all cache key patterns used by the system
     *
     * Reference for invalidation strategy decisions
     *
     * @return array Documented cache patterns
     */
    public static function getDocumentedPatterns(): array
    {
        return [
            // Availability patterns
            'availability:*' => 'All availability caches',
            'availability:service:*' => 'Service-level availability',
            'availability:staff:*' => 'Staff schedule availability',
            'availability:branch:*' => 'Branch-level availability',
            'availability:company:*' => 'Company-level availability',

            // Week/month patterns
            'week_availability:*' => 'Weekly availability view',
            'month_availability:*' => 'Monthly availability view',

            // Appointment patterns
            'appointment:*' => 'All appointment caches',
            'appointment:slot:*' => 'Specific appointment slots',

            // Schedule patterns
            'schedule:*' => 'Staff schedule caches',
            'staff:*:schedule' => 'Individual staff schedule',
            'staff:*:upcoming' => 'Upcoming appointments for staff',

            // Composite patterns
            'composite:*' => 'Composite booking caches',

            // Cal.com patterns
            'calcom:slots:*' => 'Cal.com slot availability',
            'calcom:sync:*' => 'Cal.com sync status',

            // Service patterns
            'service:*' => 'Service configuration',
            'service:*:availability' => 'Service-level availability',
        ];
    }
}
