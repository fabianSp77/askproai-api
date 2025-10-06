<?php

namespace App\Services\Cache;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Collection;
use Closure;

class CacheManager
{
    /**
     * Cache key prefixes for different data types
     */
    const PREFIX_MODEL = 'model:';
    const PREFIX_QUERY = 'query:';
    const PREFIX_STATS = 'stats:';
    const PREFIX_MENU = 'menu:';
    const PREFIX_PERMISSION = 'permission:';
    const PREFIX_WIDGET = 'widget:';

    /**
     * Default cache durations (in minutes)
     */
    const DURATION_SHORT = 5;      // 5 minutes for frequently changing data
    const DURATION_MEDIUM = 60;    // 1 hour for moderate data
    const DURATION_LONG = 1440;    // 24 hours for stable data
    const DURATION_FOREVER = null; // Until manually cleared

    /**
     * Remember a model by its ID
     */
    public static function rememberModel(string $modelClass, $id, ?int $minutes = self::DURATION_MEDIUM): ?Model
    {
        $key = self::PREFIX_MODEL . class_basename($modelClass) . ':' . $id;

        return Cache::remember($key, $minutes, function () use ($modelClass, $id) {
            return $modelClass::find($id);
        });
    }

    /**
     * Remember a collection with tags for easy invalidation
     */
    public static function rememberCollection(string $key, Closure $callback, ?int $minutes = self::DURATION_MEDIUM, array $tags = []): mixed
    {
        $cacheKey = self::PREFIX_QUERY . $key;

        // If Redis supports tagging
        if (config('cache.default') === 'redis' && !empty($tags)) {
            return Cache::tags($tags)->remember($cacheKey, $minutes, $callback);
        }

        return Cache::remember($cacheKey, $minutes, $callback);
    }

    /**
     * Cache dashboard statistics
     */
    public static function rememberStats(string $statKey, Closure $callback, ?int $minutes = self::DURATION_SHORT): mixed
    {
        $key = self::PREFIX_STATS . $statKey;
        return Cache::remember($key, $minutes, $callback);
    }

    /**
     * Cache Filament menu items per user role
     */
    public static function rememberMenu(string $userId, string $role, Closure $callback): mixed
    {
        $key = self::PREFIX_MENU . $userId . ':' . $role;
        return Cache::remember($key, self::DURATION_LONG, $callback);
    }

    /**
     * Cache user permissions
     */
    public static function rememberPermissions(string $userId, Closure $callback): mixed
    {
        $key = self::PREFIX_PERMISSION . $userId;
        return Cache::remember($key, self::DURATION_LONG, $callback);
    }

    /**
     * Cache widget data
     */
    public static function rememberWidget(string $widgetKey, Closure $callback, ?int $minutes = self::DURATION_SHORT): mixed
    {
        $key = self::PREFIX_WIDGET . $widgetKey;
        return Cache::remember($key, $minutes, $callback);
    }

    /**
     * Clear cache for a specific model
     */
    public static function forgetModel(string $modelClass, $id): bool
    {
        $key = self::PREFIX_MODEL . class_basename($modelClass) . ':' . $id;
        return Cache::forget($key);
    }

    /**
     * Clear cache by tags
     */
    public static function forgetByTags(array $tags): bool
    {
        if (config('cache.default') === 'redis') {
            try {
                Cache::tags($tags)->flush();
                return true;
            } catch (\Exception $e) {
                Log::error('Failed to clear cache by tags', [
                    'tags' => $tags,
                    'error' => $e->getMessage()
                ]);
                return false;
            }
        }

        return false;
    }

    /**
     * Clear all cache with a specific prefix
     */
    public static function forgetByPrefix(string $prefix): bool
    {
        try {
            $pattern = config('cache.prefix') . $prefix . '*';
            $keys = Cache::getRedis()->keys($pattern);

            if (!empty($keys)) {
                foreach ($keys as $key) {
                    // Remove the Laravel cache prefix
                    $key = str_replace(config('cache.prefix'), '', $key);
                    Cache::forget($key);
                }
            }

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to clear cache by prefix', [
                'prefix' => $prefix,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Clear all menu caches
     */
    public static function clearMenuCache(): bool
    {
        return self::forgetByPrefix(self::PREFIX_MENU);
    }

    /**
     * Clear all permission caches
     */
    public static function clearPermissionCache(): bool
    {
        return self::forgetByPrefix(self::PREFIX_PERMISSION);
    }

    /**
     * Clear all stats caches
     */
    public static function clearStatsCache(): bool
    {
        return self::forgetByPrefix(self::PREFIX_STATS);
    }

    /**
     * Clear all widget caches
     */
    public static function clearWidgetCache(): bool
    {
        return self::forgetByPrefix(self::PREFIX_WIDGET);
    }

    /**
     * Warm up cache with frequently accessed data
     */
    public static function warmUp(): void
    {
        Log::info('Starting cache warm-up');

        try {
            // Cache active companies
            self::rememberCollection('active_companies', function () {
                return \App\Models\Company::where('is_active', true)
                    ->select('id', 'name', 'credit_balance')
                    ->get();
            }, self::DURATION_MEDIUM, ['companies']);

            // Cache services
            self::rememberCollection('active_services', function () {
                return \App\Models\Service::where('is_active', true)
                    ->select('id', 'name', 'price', 'duration')
                    ->get();
            }, self::DURATION_LONG, ['services']);

            // Cache staff availability
            self::rememberCollection('bookable_staff', function () {
                return \App\Models\Staff::where('is_bookable', true)
                    ->where('is_active', true)
                    ->select('id', 'name', 'branch_id')
                    ->get();
            }, self::DURATION_MEDIUM, ['staff']);

            Log::info('Cache warm-up completed');
        } catch (\Exception $e) {
            Log::error('Cache warm-up failed', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Get cache statistics
     */
    public static function getStats(): array
    {
        try {
            $redis = Cache::getRedis();
            $info = $redis->info();

            return [
                'used_memory' => $info['used_memory_human'] ?? 'N/A',
                'connected_clients' => $info['connected_clients'] ?? 0,
                'total_commands_processed' => $info['total_commands_processed'] ?? 0,
                'expired_keys' => $info['expired_keys'] ?? 0,
                'evicted_keys' => $info['evicted_keys'] ?? 0,
                'keyspace_hits' => $info['keyspace_hits'] ?? 0,
                'keyspace_misses' => $info['keyspace_misses'] ?? 0,
                'hit_rate' => self::calculateHitRate($info),
            ];
        } catch (\Exception $e) {
            Log::error('Failed to get cache stats', ['error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Calculate cache hit rate
     */
    private static function calculateHitRate(array $info): float
    {
        $hits = $info['keyspace_hits'] ?? 0;
        $misses = $info['keyspace_misses'] ?? 0;

        if (($hits + $misses) === 0) {
            return 0.0;
        }

        return round(($hits / ($hits + $misses)) * 100, 2);
    }
}