<?php

declare(strict_types=1);

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;

class OptimizedCacheService
{
    public const TTL_LIVE_DATA = 60;        // 1 minute for live data
    public const TTL_STATISTICS = 300;     // 5 minutes for statistics
    public const TTL_HEAVY_COMPUTATION = 900; // 15 minutes for heavy computations
    
    public const TAG_WIDGETS = 'widgets';
    public const TAG_LIVE_DATA = 'live_data';
    public const TAG_STATISTICS = 'statistics';
    public const TAG_COMPANY_DATA = 'company_data';
    
    /**
     * Generate optimized cache key with company and time granularity
     */
    public function generateKey(string $widget, ?int $companyId = null, string $granularity = 'hour'): string
    {
        $companyId = $companyId ?? auth()->user()?->company_id ?? 'global';
        
        $timeSegment = match ($granularity) {
            'minute' => Carbon::now()->format('Y-m-d-H-i'),
            'hour' => Carbon::now()->format('Y-m-d-H'),
            'day' => Carbon::now()->format('Y-m-d'),
            default => Carbon::now()->format('Y-m-d-H')
        };
        
        return "widget:{$widget}:{$companyId}:{$timeSegment}";
    }
    
    /**
     * Remember with optimized TTL and tags
     */
    public function remember(
        string $widget,
        callable $callback,
        ?int $companyId = null,
        string $dataType = 'statistics',
        string $granularity = 'hour',
        ?int $customTtl = null
    ) {
        $key = $this->generateKey($widget, $companyId, $granularity);
        $ttl = $customTtl ?? $this->getTtlForDataType($dataType);
        $tags = $this->getTagsForDataType($dataType, $companyId);
        
        // Log cache access for monitoring
        Log::debug('Cache access', [
            'key' => $key,
            'ttl' => $ttl,
            'tags' => $tags,
            'widget' => $widget
        ]);
        
        return Cache::tags($tags)->remember($key, $ttl, function () use ($callback, $widget, $key) {
            Log::debug('Cache miss, executing callback', ['key' => $key, 'widget' => $widget]);
            
            $start = microtime(true);
            $result = $callback();
            $duration = microtime(true) - $start;
            
            // Log slow queries for optimization
            if ($duration > 1.0) {
                Log::warning('Slow widget query detected', [
                    'widget' => $widget,
                    'duration' => $duration,
                    'key' => $key
                ]);
            }
            
            return $result;
        });
    }
    
    /**
     * Remember with background refresh for heavy computations
     */
    public function rememberWithRefresh(
        string $widget,
        callable $callback,
        ?int $companyId = null,
        string $granularity = 'hour'
    ) {
        $key = $this->generateKey($widget, $companyId, $granularity);
        $refreshKey = $key . ':refresh';
        $ttl = self::TTL_HEAVY_COMPUTATION;
        $tags = $this->getTagsForDataType('heavy', $companyId);
        
        // Check if we need to refresh in background
        $shouldRefresh = !Cache::has($refreshKey);
        
        if ($shouldRefresh) {
            // Set refresh marker to prevent multiple refreshes
            Cache::put($refreshKey, true, $ttl / 2);
            
            // Dispatch background refresh job
            Queue::push(new \App\Jobs\RefreshWidgetCacheJob($widget, $companyId, $callback));
        }
        
        // Return cached data or compute if not available
        return Cache::tags($tags)->remember($key, $ttl, $callback);
    }
    
    /**
     * Invalidate cache by widget
     */
    public function invalidateWidget(string $widget, ?int $companyId = null): void
    {
        $pattern = $this->generateKey($widget, $companyId, '*');
        
        // For Redis, use pattern matching
        if (config('cache.default') === 'redis') {
            $redis = Cache::getRedis();
            $keys = $redis->keys($pattern);
            if (!empty($keys)) {
                $redis->del($keys);
                Log::info('Cache invalidated by pattern', ['pattern' => $pattern, 'keys_deleted' => count($keys)]);
            }
        } else {
            // For other drivers, use tags
            $tags = $this->getTagsForDataType('all', $companyId);
            Cache::tags($tags)->flush();
        }
    }
    
    /**
     * Invalidate all widgets for a company
     */
    public function invalidateCompany(int $companyId): void
    {
        $tags = [self::TAG_WIDGETS, self::TAG_COMPANY_DATA . ":{$companyId}"];
        Cache::tags($tags)->flush();
        
        Log::info('All company cache invalidated', ['company_id' => $companyId]);
    }
    
    /**
     * Invalidate live data across all companies
     */
    public function invalidateLiveData(): void
    {
        Cache::tags([self::TAG_LIVE_DATA])->flush();
        Log::info('Live data cache invalidated globally');
    }
    
    /**
     * Warm cache for critical widgets
     */
    public function warmCriticalWidgets(?int $companyId = null): void
    {
        $criticalWidgets = [
            'dashboard_stats',
            'live_calls',
            'recent_calls',
            'stats_overview'
        ];
        
        foreach ($criticalWidgets as $widget) {
            Queue::push(new \App\Jobs\WarmWidgetCacheJob($widget, $companyId));
        }
        
        Log::info('Cache warming initiated', ['widgets' => $criticalWidgets, 'company_id' => $companyId]);
    }
    
    /**
     * Get cache statistics for monitoring
     */
    public function getStats(): array
    {
        $stats = [
            'total_widgets' => 0,
            'cache_hits' => 0,
            'cache_misses' => 0,
            'avg_response_time' => 0,
            'slow_queries' => 0
        ];
        
        // This would be populated by a monitoring service
        // For now, return basic structure
        return $stats;
    }
    
    /**
     * Get TTL based on data type
     */
    private function getTtlForDataType(string $dataType): int
    {
        return match ($dataType) {
            'live' => self::TTL_LIVE_DATA,
            'statistics' => self::TTL_STATISTICS,
            'heavy' => self::TTL_HEAVY_COMPUTATION,
            default => self::TTL_STATISTICS
        };
    }
    
    /**
     * Get cache tags based on data type and company
     */
    private function getTagsForDataType(string $dataType, ?int $companyId): array
    {
        $tags = [self::TAG_WIDGETS];
        
        if ($companyId) {
            $tags[] = self::TAG_COMPANY_DATA . ":{$companyId}";
        }
        
        $tags[] = match ($dataType) {
            'live' => self::TAG_LIVE_DATA,
            'statistics' => self::TAG_STATISTICS,
            'heavy' => self::TAG_STATISTICS, // Heavy computations are still statistics
            'all' => self::TAG_WIDGETS,
            default => self::TAG_STATISTICS
        };
        
        return $tags;
    }
}