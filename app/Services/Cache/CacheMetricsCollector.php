<?php

namespace App\Services\Cache;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Exception;

/**
 * Cache Metrics Collector - Collect and track cache performance
 *
 * Tracks:
 * - Hit rate (% cache hits vs misses)
 * - Miss rate (% cache misses)
 * - Eviction rate (% keys evicted due to memory)
 * - Average latency (p50, p95, p99)
 * - TTL violations (caches exceeding TTL)
 * - Staleness (time since last invalidation)
 *
 * Exposes metrics for monitoring dashboards and alerting
 */
class CacheMetricsCollector
{
    /**
     * Record cache hit
     *
     * @param string $key Cache key
     * @param int $latencyMs Latency in milliseconds
     */
    public function recordHit(string $key, int $latencyMs = 0): void
    {
        try {
            Cache::increment('metrics:cache:hits');
            Cache::increment('metrics:cache:total_requests');

            // Record latency for percentile calculation
            $this->recordLatency($latencyMs);

            Log::debug('Cache hit recorded', [
                'key' => $key,
                'latency_ms' => $latencyMs,
            ]);

        } catch (Exception $e) {
            Log::warning('Failed to record cache hit', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Record cache miss
     *
     * @param string $key Cache key
     * @param int $latencyMs Latency in milliseconds (time to compute)
     */
    public function recordMiss(string $key, int $latencyMs = 0): void
    {
        try {
            Cache::increment('metrics:cache:misses');
            Cache::increment('metrics:cache:total_requests');

            // Record latency for percentile calculation
            $this->recordLatency($latencyMs);

            Log::debug('Cache miss recorded', [
                'key' => $key,
                'latency_ms' => $latencyMs,
            ]);

        } catch (Exception $e) {
            Log::warning('Failed to record cache miss', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Record cache latency
     *
     * Store latency values for percentile calculation
     *
     * @param int $latencyMs Latency in milliseconds
     */
    private function recordLatency(int $latencyMs): void
    {
        try {
            // Store last 1000 latency samples
            $latencies = Cache::get('metrics:cache:latencies', []);
            $latencies[] = $latencyMs;

            if (count($latencies) > 1000) {
                $latencies = array_slice($latencies, -1000);
            }

            Cache::put('metrics:cache:latencies', $latencies, 3600);

        } catch (Exception $e) {
            Log::warning('Failed to record latency', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Record cache invalidation
     *
     * @param string $reason Reason for invalidation
     * @param int $keysInvalidated Number of keys invalidated
     */
    public function recordInvalidation(string $reason, int $keysInvalidated = 1): void
    {
        try {
            Cache::increment('metrics:cache:invalidations');
            Cache::put('metrics:cache:last_invalidation', [
                'reason' => $reason,
                'keys' => $keysInvalidated,
                'timestamp' => now()->toIso8601String(),
            ], 86400);

            Log::debug('Cache invalidation recorded', [
                'reason' => $reason,
                'keys' => $keysInvalidated,
            ]);

        } catch (Exception $e) {
            Log::warning('Failed to record invalidation', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Get current cache metrics
     *
     * Returns comprehensive metrics for monitoring
     *
     * @return array Metrics data
     */
    public function getMetrics(): array
    {
        try {
            $hits = Cache::get('metrics:cache:hits', 0);
            $misses = Cache::get('metrics:cache:misses', 0);
            $total = $hits + $misses;

            $metrics = [
                'timestamp' => now()->toIso8601String(),
                'requests' => [
                    'total' => $total,
                    'hits' => $hits,
                    'misses' => $misses,
                ],
                'rates' => [
                    'hit_rate' => $total > 0 ? round(($hits / $total) * 100, 2) : 0,
                    'miss_rate' => $total > 0 ? round(($misses / $total) * 100, 2) : 0,
                ],
                'redis' => $this->getRedisMetrics(),
                'latency' => $this->getLatencyMetrics(),
                'invalidations' => Cache::get('metrics:cache:invalidations', 0),
            ];

            return $metrics;

        } catch (Exception $e) {
            Log::warning('Failed to get metrics', ['error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Get Redis-specific metrics
     *
     * @return array Redis metrics
     */
    private function getRedisMetrics(): array
    {
        try {
            if (config('cache.default') !== 'redis') {
                return [];
            }

            $redis = Cache::getRedis();
            $info = $redis->info();

            return [
                'used_memory' => $info['used_memory_human'] ?? 'N/A',
                'connected_clients' => $info['connected_clients'] ?? 0,
                'total_commands' => $info['total_commands_processed'] ?? 0,
                'keyspace_hits' => $info['keyspace_hits'] ?? 0,
                'keyspace_misses' => $info['keyspace_misses'] ?? 0,
                'evicted_keys' => $info['evicted_keys'] ?? 0,
                'expired_keys' => $info['expired_keys'] ?? 0,
            ];

        } catch (Exception $e) {
            Log::debug('Failed to get Redis metrics', ['error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Get latency percentiles
     *
     * @return array Latency metrics (p50, p95, p99)
     */
    private function getLatencyMetrics(): array
    {
        try {
            $latencies = Cache::get('metrics:cache:latencies', []);

            if (empty($latencies)) {
                return [
                    'p50_ms' => 0,
                    'p95_ms' => 0,
                    'p99_ms' => 0,
                    'avg_ms' => 0,
                ];
            }

            sort($latencies);
            $count = count($latencies);

            return [
                'p50_ms' => round($latencies[floor($count * 0.50)] ?? 0, 2),
                'p95_ms' => round($latencies[floor($count * 0.95)] ?? 0, 2),
                'p99_ms' => round($latencies[floor($count * 0.99)] ?? 0, 2),
                'avg_ms' => round(array_sum($latencies) / $count, 2),
            ];

        } catch (Exception $e) {
            Log::debug('Failed to get latency metrics', ['error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Check if cache health is degraded
     *
     * Returns status and actionable alerts
     *
     * @return array Health status
     */
    public function getHealthStatus(): array
    {
        $metrics = $this->getMetrics();

        $health = [
            'status' => 'healthy',
            'alerts' => [],
            'metrics' => $metrics,
        ];

        // Check hit rate
        $hitRate = $metrics['rates']['hit_rate'] ?? 0;
        if ($hitRate < 80) {
            $health['status'] = 'degraded';
            $health['alerts'][] = [
                'level' => 'warning',
                'message' => "Low cache hit rate: {$hitRate}%",
                'threshold' => '80%',
            ];
        }

        // Check eviction rate
        $redisMetrics = $metrics['redis'] ?? [];
        $evictedKeys = $redisMetrics['evicted_keys'] ?? 0;
        if ($evictedKeys > 100) {
            $health['status'] = 'degraded';
            $health['alerts'][] = [
                'level' => 'warning',
                'message' => "High eviction rate: {$evictedKeys} keys evicted",
                'action' => 'Increase Redis memory or reduce TTL',
            ];
        }

        // Check latency
        $latency = $metrics['latency'] ?? [];
        $p99 = $latency['p99_ms'] ?? 0;
        if ($p99 > 100) {
            $health['status'] = 'degraded';
            $health['alerts'][] = [
                'level' => 'warning',
                'message' => "High cache latency p99: {$p99}ms",
                'threshold' => '<100ms',
            ];
        }

        if (empty($health['alerts'])) {
            $health['alerts'][] = [
                'level' => 'info',
                'message' => 'Cache performing well',
            ];
        }

        return $health;
    }

    /**
     * Reset metrics
     *
     * Clear all collected metrics (typically done daily or on deploy)
     */
    public function resetMetrics(): void
    {
        try {
            Cache::forget('metrics:cache:hits');
            Cache::forget('metrics:cache:misses');
            Cache::forget('metrics:cache:total_requests');
            Cache::forget('metrics:cache:latencies');
            Cache::forget('metrics:cache:invalidations');

            Log::info('Cache metrics reset');

        } catch (Exception $e) {
            Log::warning('Failed to reset metrics', ['error' => $e->getMessage()]);
        }
    }
}
