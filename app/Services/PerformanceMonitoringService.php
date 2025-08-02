<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Carbon\Carbon;

class PerformanceMonitoringService
{
    protected $metricsBuffer = [];
    protected $bufferSize = 100;
    protected $flushInterval = 60; // seconds
    protected $lastFlush;

    public function __construct()
    {
        $this->lastFlush = time();
    }

    /**
     * Record a performance metric.
     */
    public function record(string $metric, float $value, array $tags = []): void
    {
        $dataPoint = [
            'metric' => $metric,
            'value' => $value,
            'tags' => $tags,
            'timestamp' => microtime(true),
        ];

        $this->metricsBuffer[] = $dataPoint;

        // Flush if buffer is full or interval has passed
        if (count($this->metricsBuffer) >= $this->bufferSize || 
            (time() - $this->lastFlush) >= $this->flushInterval) {
            $this->flush();
        }
    }

    /**
     * Record request performance.
     */
    public function recordRequest(string $endpoint, float $duration, int $statusCode, array $metadata = []): void
    {
        $tags = [
            'endpoint' => $endpoint,
            'status' => $statusCode,
            'method' => request()->method(),
            'branch_id' => request()->get('branch_id'),
        ];

        // Record duration
        $this->record('http.request.duration', $duration, $tags);

        // Record status code distribution
        $this->record('http.request.status', 1, $tags);

        // Record slow requests
        if ($duration > config('performance.monitoring.alert_thresholds.response_time', 1000)) {
            $this->record('http.request.slow', 1, array_merge($tags, $metadata));
        }

        // Update real-time metrics
        $this->updateRealTimeMetrics('request', $endpoint, $duration);
    }

    /**
     * Record database query performance.
     */
    public function recordQuery(string $query, float $duration, string $connection = 'default'): void
    {
        $queryType = $this->getQueryType($query);
        
        $tags = [
            'type' => $queryType,
            'connection' => $connection,
        ];

        $this->record('database.query.duration', $duration, $tags);

        if ($duration > config('performance.query_optimization.slow_query_threshold', 100)) {
            $this->record('database.query.slow', 1, array_merge($tags, [
                'query' => $this->sanitizeQuery($query),
            ]));
        }
    }

    /**
     * Record cache performance.
     */
    public function recordCache(string $operation, string $key, float $duration, bool $hit): void
    {
        $tags = [
            'operation' => $operation,
            'hit' => $hit ? 'true' : 'false',
        ];

        $this->record('cache.operation.duration', $duration, $tags);
        $this->record('cache.hit.rate', $hit ? 1 : 0, ['operation' => $operation]);
    }

    /**
     * Record queue job performance.
     */
    public function recordJob(string $job, float $duration, bool $success, string $queue = 'default'): void
    {
        $tags = [
            'job' => class_basename($job),
            'queue' => $queue,
            'status' => $success ? 'success' : 'failed',
        ];

        $this->record('queue.job.duration', $duration, $tags);
        $this->record('queue.job.processed', 1, $tags);

        if (!$success) {
            $this->record('queue.job.failed', 1, $tags);
        }
    }

    /**
     * Record memory usage.
     */
    public function recordMemory(): void
    {
        $usage = memory_get_usage(true);
        $peak = memory_get_peak_usage(true);
        
        $this->record('system.memory.usage', $usage / 1024 / 1024); // Convert to MB
        $this->record('system.memory.peak', $peak / 1024 / 1024);
        
        // Check memory threshold
        $limit = $this->getMemoryLimit();
        if ($limit > 0) {
            $percentage = ($usage / $limit) * 100;
            $this->record('system.memory.percentage', $percentage);
            
            if ($percentage > config('performance.monitoring.alert_thresholds.memory_percent', 80)) {
                $this->alert('High memory usage', [
                    'usage' => round($usage / 1024 / 1024, 2) . 'MB',
                    'percentage' => round($percentage, 2) . '%',
                ]);
            }
        }
    }

    /**
     * Get current performance statistics.
     */
    public function getStats(string $period = 'last_5_minutes'): array
    {
        $stats = [];
        $periodMinutes = $this->getPeriodMinutes($period);
        $since = Carbon::now()->subMinutes($periodMinutes);

        // Request stats
        $stats['requests'] = $this->getRequestStats($since);
        
        // Database stats
        $stats['database'] = $this->getDatabaseStats($since);
        
        // Cache stats
        $stats['cache'] = $this->getCacheStats($since);
        
        // Queue stats
        $stats['queue'] = $this->getQueueStats($since);
        
        // System stats
        $stats['system'] = $this->getSystemStats();
        
        // Error stats
        $stats['errors'] = $this->getErrorStats($since);

        return $stats;
    }

    /**
     * Get real-time metrics.
     */
    public function getRealTimeMetrics(): array
    {
        $metrics = [];
        
        // Get from Redis if available
        if (Redis::connection()) {
            $keys = Redis::keys('realtime:*');
            foreach ($keys as $key) {
                $data = Redis::hgetall($key);
                $metricName = str_replace('realtime:', '', $key);
                $metrics[$metricName] = $data;
            }
        }

        return $metrics;
    }

    /**
     * Get performance trends.
     */
    public function getTrends(string $metric, string $period = 'last_hour', int $resolution = 60): array
    {
        $data = [];
        $now = Carbon::now();
        $periodMinutes = $this->getPeriodMinutes($period);
        $start = $now->copy()->subMinutes($periodMinutes);
        
        // Calculate bucket size
        $buckets = ceil($periodMinutes * 60 / $resolution);
        $bucketSize = $periodMinutes / $buckets;
        
        for ($i = 0; $i < $buckets; $i++) {
            $bucketStart = $start->copy()->addMinutes($i * $bucketSize);
            $bucketEnd = $bucketStart->copy()->addMinutes($bucketSize);
            
            $value = $this->getMetricValue($metric, $bucketStart, $bucketEnd);
            
            $data[] = [
                'timestamp' => $bucketStart->toIso8601String(),
                'value' => $value,
            ];
        }

        return $data;
    }

    /**
     * Analyze performance and provide recommendations.
     */
    public function analyze(): array
    {
        $analysis = [
            'issues' => [],
            'recommendations' => [],
            'score' => 100,
        ];

        // Analyze response times
        $avgResponseTime = $this->getAverageResponseTime();
        if ($avgResponseTime > 500) {
            $analysis['issues'][] = 'High average response time: ' . round($avgResponseTime, 2) . 'ms';
            $analysis['recommendations'][] = 'Enable response caching for frequently accessed endpoints';
            $analysis['score'] -= 10;
        }

        // Analyze database performance
        $slowQueries = $this->getSlowQueryCount();
        if ($slowQueries > 10) {
            $analysis['issues'][] = 'High number of slow queries: ' . $slowQueries;
            $analysis['recommendations'][] = 'Review and optimize slow database queries';
            $analysis['recommendations'][] = 'Consider adding database indexes';
            $analysis['score'] -= 15;
        }

        // Analyze cache hit rate
        $cacheHitRate = $this->getCacheHitRate();
        if ($cacheHitRate < 0.8) {
            $analysis['issues'][] = 'Low cache hit rate: ' . round($cacheHitRate * 100, 2) . '%';
            $analysis['recommendations'][] = 'Review cache key generation strategy';
            $analysis['recommendations'][] = 'Increase cache TTL for stable data';
            $analysis['score'] -= 10;
        }

        // Analyze memory usage
        $memoryUsage = $this->getMemoryUsagePercentage();
        if ($memoryUsage > 80) {
            $analysis['issues'][] = 'High memory usage: ' . round($memoryUsage, 2) . '%';
            $analysis['recommendations'][] = 'Optimize memory-intensive operations';
            $analysis['recommendations'][] = 'Consider increasing memory allocation';
            $analysis['score'] -= 20;
        }

        // Analyze error rate
        $errorRate = $this->getErrorRate();
        if ($errorRate > 0.01) {
            $analysis['issues'][] = 'High error rate: ' . round($errorRate * 100, 2) . '%';
            $analysis['recommendations'][] = 'Investigate and fix recurring errors';
            $analysis['score'] -= 15;
        }

        return $analysis;
    }

    /**
     * Send performance alert.
     */
    protected function alert(string $message, array $context = []): void
    {
        Log::channel('performance')->warning($message, $context);
        
        // Send notification if configured
        if (config('performance.monitoring.alerts.enabled')) {
            // Implement notification logic (email, Slack, etc.)
        }
    }

    /**
     * Flush metrics buffer.
     */
    protected function flush(): void
    {
        if (empty($this->metricsBuffer)) {
            return;
        }

        try {
            // Store in time-series format
            foreach ($this->metricsBuffer as $metric) {
                $this->storeMetric($metric);
            }

            // Clear buffer
            $this->metricsBuffer = [];
            $this->lastFlush = time();
        } catch (\Exception $e) {
            Log::error('Failed to flush performance metrics', [
                'error' => $e->getMessage(),
                'metrics_count' => count($this->metricsBuffer),
            ]);
        }
    }

    /**
     * Store metric in time-series database or cache.
     */
    protected function storeMetric(array $metric): void
    {
        // Store in Redis with expiry
        if (Redis::connection()) {
            $key = "metrics:{$metric['metric']}:" . date('YmdH');
            $score = $metric['timestamp'];
            $value = json_encode([
                'value' => $metric['value'],
                'tags' => $metric['tags'],
            ]);
            
            Redis::zadd($key, $score, $value);
            Redis::expire($key, 86400); // Keep for 24 hours
        }
        
        // Also store aggregated values
        $this->updateAggregates($metric);
    }

    /**
     * Update aggregated metrics.
     */
    protected function updateAggregates(array $metric): void
    {
        $hourKey = "aggregate:{$metric['metric']}:" . date('YmdH');
        $dayKey = "aggregate:{$metric['metric']}:" . date('Ymd');
        
        // Update hourly aggregates
        Cache::remember($hourKey, 3600, function () {
            return [
                'count' => 0,
                'sum' => 0,
                'min' => PHP_FLOAT_MAX,
                'max' => PHP_FLOAT_MIN,
            ];
        });
        
        $hourData = Cache::get($hourKey);
        $hourData['count']++;
        $hourData['sum'] += $metric['value'];
        $hourData['min'] = min($hourData['min'], $metric['value']);
        $hourData['max'] = max($hourData['max'], $metric['value']);
        Cache::put($hourKey, $hourData, 3600);
    }

    /**
     * Update real-time metrics.
     */
    protected function updateRealTimeMetrics(string $type, string $identifier, float $value): void
    {
        if (!Redis::connection()) {
            return;
        }

        $key = "realtime:{$type}:{$identifier}";
        $now = time();
        
        // Update moving averages
        Redis::hset($key, 'last_value', $value);
        Redis::hset($key, 'last_update', $now);
        
        // Update 1-minute average
        $this->updateMovingAverage($key, 'avg_1m', $value, 60);
        
        // Update 5-minute average
        $this->updateMovingAverage($key, 'avg_5m', $value, 300);
        
        // Update 15-minute average
        $this->updateMovingAverage($key, 'avg_15m', $value, 900);
        
        // Set expiry
        Redis::expire($key, 3600); // Keep for 1 hour
    }

    /**
     * Update moving average.
     */
    protected function updateMovingAverage(string $key, string $field, float $value, int $windowSeconds): void
    {
        $current = Redis::hget($key, $field) ?: $value;
        $alpha = 2 / ($windowSeconds + 1); // Exponential moving average factor
        $newAverage = $alpha * $value + (1 - $alpha) * $current;
        Redis::hset($key, $field, $newAverage);
    }

    /**
     * Helper methods
     */
    protected function getQueryType(string $query): string
    {
        $query = strtolower(trim($query));
        
        if (strpos($query, 'select') === 0) return 'select';
        if (strpos($query, 'insert') === 0) return 'insert';
        if (strpos($query, 'update') === 0) return 'update';
        if (strpos($query, 'delete') === 0) return 'delete';
        
        return 'other';
    }

    protected function sanitizeQuery(string $query): string
    {
        // Remove sensitive data from queries
        return preg_replace('/\b\d{4,}\b/', 'XXXX', $query);
    }

    protected function getMemoryLimit(): int
    {
        $limit = ini_get('memory_limit');
        if ($limit == -1) return 0;
        
        $unit = strtolower(substr($limit, -1));
        $value = (int) substr($limit, 0, -1);
        
        switch ($unit) {
            case 'g': return $value * 1024 * 1024 * 1024;
            case 'm': return $value * 1024 * 1024;
            case 'k': return $value * 1024;
            default: return (int) $limit;
        }
    }

    protected function getPeriodMinutes(string $period): int
    {
        switch ($period) {
            case 'last_5_minutes': return 5;
            case 'last_15_minutes': return 15;
            case 'last_hour': return 60;
            case 'last_6_hours': return 360;
            case 'last_day': return 1440;
            default: return 60;
        }
    }

    // Statistics calculation methods would go here...
    protected function getRequestStats($since) { return []; }
    protected function getDatabaseStats($since) { return []; }
    protected function getCacheStats($since) { return []; }
    protected function getQueueStats($since) { return []; }
    protected function getSystemStats() { return []; }
    protected function getErrorStats($since) { return []; }
    protected function getMetricValue($metric, $start, $end) { return 0; }
    protected function getAverageResponseTime() { return 0; }
    protected function getSlowQueryCount() { return 0; }
    protected function getCacheHitRate() { return 0.9; }
    protected function getMemoryUsagePercentage() { return 50; }
    protected function getErrorRate() { return 0.001; }
}