<?php

namespace App\Services\Monitoring;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;

/**
 * Collects system metrics for monitoring
 */
class MetricsCollectorService
{
    /**
     * Collect all metrics
     */
    public function collect(): array
    {
        return [
            $this->collectHttpMetrics(),
            $this->collectDatabaseMetrics(),
            $this->collectQueueMetrics(),
            $this->collectCacheMetrics(),
            $this->collectBusinessMetrics(),
            $this->collectWebhookMetrics(),
        ];
    }
    
    /**
     * HTTP request metrics
     */
    private function collectHttpMetrics(): array
    {
        $stats = Cache::get('http_metrics:' . date('Y-m-d:H'), []);
        
        return [
            'name' => 'http_requests_total',
            'type' => 'counter',
            'help' => 'Total HTTP requests',
            'values' => [
                [
                    'labels' => ['status' => '2xx'],
                    'value' => $stats['2xx'] ?? 0,
                ],
                [
                    'labels' => ['status' => '4xx'],
                    'value' => $stats['4xx'] ?? 0,
                ],
                [
                    'labels' => ['status' => '5xx'],
                    'value' => $stats['5xx'] ?? 0,
                ],
            ],
        ];
    }
    
    /**
     * Database metrics
     */
    private function collectDatabaseMetrics(): array
    {
        $connections = DB::select("SHOW STATUS LIKE 'Threads_connected'")[0]->Value ?? 0;
        $slowQueries = DB::select("SHOW STATUS LIKE 'Slow_queries'")[0]->Value ?? 0;
        
        return [
            'name' => 'database_connections',
            'type' => 'gauge',
            'help' => 'Current database connections',
            'values' => [
                [
                    'labels' => [],
                    'value' => $connections,
                ],
                [
                    'labels' => ['type' => 'slow_queries'],
                    'value' => $slowQueries,
                ],
            ],
        ];
    }
    
    /**
     * Queue metrics
     */
    private function collectQueueMetrics(): array
    {
        $queues = ['default', 'webhooks-high-priority', 'webhooks-medium-priority'];
        $values = [];
        
        foreach ($queues as $queue) {
            $size = Redis::llen("queues:{$queue}");
            $values[] = [
                'labels' => ['queue' => $queue],
                'value' => $size,
            ];
        }
        
        // Failed jobs
        $failedJobs = DB::table('failed_jobs')->count();
        $values[] = [
            'labels' => ['queue' => 'failed'],
            'value' => $failedJobs,
        ];
        
        return [
            'name' => 'queue_size',
            'type' => 'gauge',
            'help' => 'Current queue sizes',
            'values' => $values,
        ];
    }
    
    /**
     * Cache metrics
     */
    private function collectCacheMetrics(): array
    {
        $info = Redis::info();
        $hits = $info['keyspace_hits'] ?? 0;
        $misses = $info['keyspace_misses'] ?? 0;
        $total = $hits + $misses;
        $hitRate = $total > 0 ? ($hits / $total) : 0;
        
        return [
            'name' => 'cache_hit_rate',
            'type' => 'gauge',
            'help' => 'Cache hit rate',
            'values' => [
                [
                    'labels' => ['cache' => 'redis'],
                    'value' => round($hitRate, 4),
                ],
            ],
        ];
    }
    
    /**
     * Business metrics
     */
    private function collectBusinessMetrics(): array
    {
        // Today's appointments
        $appointments = DB::table('appointments')
            ->whereDate('start_time', today())
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();
        
        $values = [];
        foreach ($appointments as $status => $count) {
            $values[] = [
                'labels' => ['status' => $status],
                'value' => $count,
            ];
        }
        
        return [
            'name' => 'appointments_today',
            'type' => 'gauge',
            'help' => 'Appointments scheduled for today',
            'values' => $values,
        ];
    }
    
    /**
     * Webhook processing metrics
     */
    private function collectWebhookMetrics(): array
    {
        $key = "webhook_metrics:" . date('Y-m-d:H');
        $total = Cache::get("{$key}:total", 0);
        $success = Cache::get("{$key}:success", 0);
        $failure = Cache::get("{$key}:failure", 0);
        
        return [
            'name' => 'webhook_processed_total',
            'type' => 'counter',
            'help' => 'Total webhooks processed',
            'values' => [
                [
                    'labels' => ['status' => 'success'],
                    'value' => $success,
                ],
                [
                    'labels' => ['status' => 'failure'],
                    'value' => $failure,
                ],
                [
                    'labels' => ['status' => 'total'],
                    'value' => $total,
                ],
            ],
        ];
    }
    
    /**
     * Track custom metric
     */
    public function track(string $metric, float $value, array $labels = []): void
    {
        $key = "metric:{$metric}:" . md5(json_encode($labels));
        
        Cache::increment($key, $value);
        
        // Store metric metadata
        Cache::put("metric_meta:{$metric}", [
            'labels' => array_keys($labels),
            'last_updated' => now(),
        ], 3600);
    }
    
    /**
     * Track timing
     */
    public function timing(string $metric, float $milliseconds, array $labels = []): void
    {
        $bucket = $this->getTimingBucket($milliseconds);
        $labels['bucket'] = $bucket;
        
        $this->track("{$metric}_histogram", 1, $labels);
        $this->track("{$metric}_sum", $milliseconds, $labels);
    }
    
    /**
     * Get timing bucket for histogram
     */
    private function getTimingBucket(float $ms): string
    {
        $buckets = [10, 50, 100, 250, 500, 1000, 2500, 5000, 10000];
        
        foreach ($buckets as $bucket) {
            if ($ms <= $bucket) {
                return "le_{$bucket}";
            }
        }
        
        return "le_inf";
    }
}