<?php

namespace App\Services\Dashboard;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class WidgetCacheService
{
    private const DEFAULT_TTL = 300; // 5 minutes
    private array $ttlMap = [
        'live_calls' => 30,       // 30 seconds for real-time data
        'daily_stats' => 300,     // 5 minutes for daily stats
        'monthly_stats' => 3600,  // 1 hour for monthly stats
        'company_list' => 600,    // 10 minutes for company lists
    ];

    /**
     * Get or cache widget data
     */
    public function remember(string $widgetName, callable $dataCallback, ?int $ttl = null): mixed
    {
        $cacheKey = $this->getCacheKey($widgetName);
        $ttl = $ttl ?? $this->getTtl($widgetName);

        return Cache::remember($cacheKey, $ttl, function () use ($dataCallback, $widgetName) {
            $startTime = microtime(true);
            
            $data = $dataCallback();
            
            $duration = microtime(true) - $startTime;
            
            // Log slow widget queries
            if ($duration > 1.0) {
                Log::warning("Slow widget query: {$widgetName}", [
                    'duration_seconds' => round($duration, 2)
                ]);
            }
            
            return $data;
        });
    }

    /**
     * Batch load data for multiple widgets
     */
    public function batchLoad(array $widgets): array
    {
        $results = [];
        $uncached = [];

        // Check cache first
        foreach ($widgets as $widget => $callback) {
            $cacheKey = $this->getCacheKey($widget);
            $cached = Cache::get($cacheKey);
            
            if ($cached !== null) {
                $results[$widget] = $cached;
            } else {
                $uncached[$widget] = $callback;
            }
        }

        // Load uncached data in a single query if possible
        if (!empty($uncached)) {
            $this->loadUncachedData($uncached, $results);
        }

        return $results;
    }

    /**
     * Invalidate widget cache
     */
    public function invalidate(string $widgetName): void
    {
        Cache::forget($this->getCacheKey($widgetName));
    }

    /**
     * Invalidate multiple widget caches
     */
    public function invalidateMany(array $widgetNames): void
    {
        foreach ($widgetNames as $widgetName) {
            $this->invalidate($widgetName);
        }
    }

    /**
     * Preload widget data during off-peak hours
     */
    public function preloadWidgets(array $widgetCallbacks): void
    {
        foreach ($widgetCallbacks as $widget => $callback) {
            try {
                $this->remember($widget, $callback);
                Log::info("Preloaded widget: {$widget}");
            } catch (\Exception $e) {
                Log::error("Failed to preload widget: {$widget}", [
                    'error' => $e->getMessage()
                ]);
            }
        }
    }

    private function getCacheKey(string $widgetName): string
    {
        $tenantId = auth()->user()?->company_id ?? 'global';
        return "widget.{$tenantId}.{$widgetName}";
    }

    private function getTtl(string $widgetName): int
    {
        // Extract base widget name without modifiers
        $baseName = explode('.', $widgetName)[0];
        
        return $this->ttlMap[$baseName] ?? self::DEFAULT_TTL;
    }

    private function loadUncachedData(array $uncached, array &$results): void
    {
        // Group similar queries for batch execution
        $batchQueries = [];
        
        foreach ($uncached as $widget => $callback) {
            // Detect query type from widget name
            if (str_contains($widget, 'stats') || str_contains($widget, 'count')) {
                $batchQueries['stats'][$widget] = $callback;
            } else {
                // Execute individually
                $results[$widget] = $callback();
                Cache::put($this->getCacheKey($widget), $results[$widget], $this->getTtl($widget));
            }
        }

        // Execute batch queries
        if (!empty($batchQueries['stats'])) {
            $this->executeBatchStats($batchQueries['stats'], $results);
        }
    }

    private function executeBatchStats(array $statsWidgets, array &$results): void
    {
        // Example: Batch load multiple stats in one query
        $stats = DB::table('appointments')
            ->selectRaw('
                COUNT(*) as total_appointments,
                COUNT(CASE WHEN status = "completed" THEN 1 END) as completed_appointments,
                COUNT(CASE WHEN created_at >= ? THEN 1 END) as today_appointments,
                COUNT(CASE WHEN created_at >= ? THEN 1 END) as week_appointments
            ', [now()->startOfDay(), now()->startOfWeek()])
            ->first();

        // Map results to widgets
        foreach ($statsWidgets as $widget => $callback) {
            if (str_contains($widget, 'total')) {
                $results[$widget] = $stats->total_appointments;
            } elseif (str_contains($widget, 'completed')) {
                $results[$widget] = $stats->completed_appointments;
            } elseif (str_contains($widget, 'today')) {
                $results[$widget] = $stats->today_appointments;
            } else {
                // Fallback to individual callback
                $results[$widget] = $callback();
            }
            
            Cache::put($this->getCacheKey($widget), $results[$widget], $this->getTtl($widget));
        }
    }
}