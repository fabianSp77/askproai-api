<?php

namespace App\Traits;

use App\Services\Cache\CacheManager;
use Illuminate\Support\Facades\Cache;

trait Cacheable
{
    /**
     * Boot the cacheable trait
     */
    protected static function bootCacheable(): void
    {
        // Clear cache when model is created
        static::created(function ($model) {
            $model->clearModelCache();
            $model->clearRelatedCaches();
        });

        // Clear cache when model is updated
        static::updated(function ($model) {
            $model->clearModelCache();
            $model->clearRelatedCaches();
        });

        // Clear cache when model is deleted
        static::deleted(function ($model) {
            $model->clearModelCache();
            $model->clearRelatedCaches();
        });

        // Clear cache when model is restored (if using soft deletes)
        if (method_exists(static::class, 'restored')) {
            static::restored(function ($model) {
                $model->clearModelCache();
                $model->clearRelatedCaches();
            });
        }
    }

    /**
     * Get cache key for this model
     */
    public function getCacheKey(): string
    {
        return CacheManager::PREFIX_MODEL . class_basename($this) . ':' . $this->getKey();
    }

    /**
     * Get cache tags for this model
     */
    public function getCacheTags(): array
    {
        $tags = [strtolower(class_basename($this)) . 's'];

        // Add company-specific tag if applicable
        if (method_exists($this, 'company') && $this->company_id) {
            $tags[] = 'company:' . $this->company_id;
        }

        // Add tenant-specific tag if applicable
        if (property_exists($this, 'tenant_id') && $this->tenant_id) {
            $tags[] = 'tenant:' . $this->tenant_id;
        }

        return $tags;
    }

    /**
     * Remember this model in cache
     */
    public function remember(?int $minutes = CacheManager::DURATION_MEDIUM)
    {
        return CacheManager::rememberModel(static::class, $this->getKey(), $minutes);
    }

    /**
     * Clear this model from cache
     */
    public function clearModelCache(): void
    {
        Cache::forget($this->getCacheKey());

        // Clear by tags if Redis
        if (config('cache.default') === 'redis') {
            CacheManager::forgetByTags($this->getCacheTags());
        }
    }

    /**
     * Clear related caches (override in model for specific relationships)
     */
    public function clearRelatedCaches(): void
    {
        // Clear stats cache as most changes affect statistics
        CacheManager::clearStatsCache();

        // Clear widget cache as they often display model data
        CacheManager::clearWidgetCache();

        // Model-specific cache clearing
        $this->clearSpecificCaches();
    }

    /**
     * Override in model to clear specific caches
     */
    protected function clearSpecificCaches(): void
    {
        // Override in model class for specific cache clearing
    }

    /**
     * Find and cache a model by ID
     */
    public static function findCached($id, ?int $minutes = CacheManager::DURATION_MEDIUM)
    {
        return CacheManager::rememberModel(static::class, $id, $minutes);
    }

    /**
     * Get all cached (use sparingly for small datasets)
     */
    public static function allCached(?int $minutes = CacheManager::DURATION_LONG)
    {
        $key = 'all:' . strtolower(class_basename(static::class)) . 's';

        return CacheManager::rememberCollection($key, function () {
            return static::all();
        }, $minutes, [strtolower(class_basename(static::class)) . 's']);
    }

    /**
     * Get active records cached
     */
    public static function activeCached(?int $minutes = CacheManager::DURATION_MEDIUM)
    {
        $key = 'active:' . strtolower(class_basename(static::class)) . 's';

        return CacheManager::rememberCollection($key, function () {
            $query = static::query();

            // Check for is_active column
            if (in_array('is_active', (new static)->getFillable())) {
                $query->where('is_active', true);
            }
            // Check for status column
            elseif (in_array('status', (new static)->getFillable())) {
                $query->where('status', 'active');
            }

            return $query->get();
        }, $minutes, [strtolower(class_basename(static::class)) . 's']);
    }

    /**
     * Cache a query result
     */
    public static function cacheQuery(string $key, \Closure $callback, ?int $minutes = CacheManager::DURATION_MEDIUM, array $tags = [])
    {
        $fullKey = strtolower(class_basename(static::class)) . ':' . $key;

        if (empty($tags)) {
            $tags = [strtolower(class_basename(static::class)) . 's'];
        }

        return CacheManager::rememberCollection($fullKey, $callback, $minutes, $tags);
    }

    /**
     * Clear all caches for this model type
     */
    public static function clearAllModelCaches(): void
    {
        $modelName = strtolower(class_basename(static::class));

        // Clear by tags if Redis
        if (config('cache.default') === 'redis') {
            CacheManager::forgetByTags([$modelName . 's']);
        }

        // Clear by prefix
        CacheManager::forgetByPrefix(CacheManager::PREFIX_MODEL . class_basename(static::class));
        CacheManager::forgetByPrefix(CacheManager::PREFIX_QUERY . $modelName);
    }
}