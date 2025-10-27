<?php

namespace App\Filament\Concerns;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Auth;

/**
 * Trait for caching Filament navigation badges to prevent memory exhaustion.
 *
 * SECURITY: Multi-tenant safe - includes company_id in cache key.
 * PERFORMANCE: Prevents 27+ COUNT() queries on every page load.
 *
 * Usage in Resource:
 *
 * use HasCachedNavigationBadge;
 *
 * public static function getNavigationBadge(): ?string
 * {
 *     return static::getCachedBadge(function() {
 *         return static::getModel()::where('status', 'active')->count();
 *     });
 * }
 */
trait HasCachedNavigationBadge
{
    /**
     * Get cached navigation badge with multi-tenant isolation.
     */
    protected static function getCachedBadge(callable $callback, int $ttl = 300): ?string
    {
        if (!Auth::check()) {
            return null;
        }

        try {
            $user = Auth::user();
            $cacheKey = static::getBadgeCacheKey($user);
            $result = Cache::remember($cacheKey, $ttl, $callback);

            return $result > 0 ? (string) $result : null;
        } catch (\Exception $e) {
            // Gracefully handle missing tables/columns during database restoration
            \Log::warning('Navigation badge error in ' . static::class . ': ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Get cached navigation badge color with multi-tenant isolation.
     */
    protected static function getCachedBadgeColor(callable $callback, int $ttl = 300): ?string
    {
        if (!Auth::check()) {
            return null;
        }

        try {
            $user = Auth::user();
            $cacheKey = static::getBadgeCacheKey($user, 'color');
            return Cache::remember($cacheKey, $ttl, $callback);
        } catch (\Exception $e) {
            // Gracefully handle missing tables/columns during database restoration
            \Log::warning('Navigation badge color error in ' . static::class . ': ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Generate multi-tenant safe cache key.
     * SECURITY: MUST include company_id!
     */
    protected static function getBadgeCacheKey($user, string $type = 'count'): string
    {
        $resourceName = class_basename(static::class);

        if ($user->hasRole('super_admin')) {
            return "badge:{$resourceName}:super_admin:{$type}";
        }

        $companyId = $user->company_id ?? 'no_company';
        $userId = $user->id;

        return "badge:{$resourceName}:company_{$companyId}:user_{$userId}:{$type}";
    }

    /**
     * Clear badge cache for current user.
     */
    public static function clearBadgeCache(): void
    {
        if (!Auth::check()) {
            return;
        }

        $user = Auth::user();
        Cache::forget(static::getBadgeCacheKey($user, 'count'));
        Cache::forget(static::getBadgeCacheKey($user, 'color'));
    }
}
