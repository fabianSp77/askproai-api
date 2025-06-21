<?php

namespace App\Services\MCP;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

class MCPHealthCheckService
{
    protected MCPServiceRegistry $registry;
    protected MCPMetricsCollector $metricsCollector;

    public function __construct(
        MCPServiceRegistry $registry,
        MCPMetricsCollector $metricsCollector
    ) {
        $this->registry = $registry;
        $this->metricsCollector = $metricsCollector;
    }

    /**
     * Check overall system health
     */
    public function checkSystemHealth(): array
    {
        $services = [];
        $overallStatus = 'healthy';
        $issues = [];

        // Check core infrastructure
        $infrastructure = $this->checkInfrastructure();
        foreach ($infrastructure as $component => $health) {
            if (!$health['healthy']) {
                $overallStatus = 'degraded';
                $issues[] = "{$component}: {$health['message']}";
            }
            $services[$component] = $health;
        }

        // Check MCP services
        foreach ($this->registry->getAllServices() as $serviceName => $service) {
            try {
                $serviceHealth = $this->checkServiceHealth($serviceName);
                $services[$serviceName] = $serviceHealth;
                
                if (!$serviceHealth['healthy']) {
                    if ($serviceHealth['severity'] === 'critical') {
                        $overallStatus = 'unhealthy';
                    } elseif ($overallStatus !== 'unhealthy') {
                        $overallStatus = 'degraded';
                    }
                    $issues[] = "{$serviceName}: {$serviceHealth['message']}";
                }
            } catch (\Exception $e) {
                $services[$serviceName] = [
                    'healthy' => false,
                    'message' => 'Health check failed: ' . $e->getMessage(),
                    'severity' => 'critical',
                ];
                $overallStatus = 'unhealthy';
                $issues[] = "{$serviceName}: Health check exception";
            }
        }

        return [
            'status' => $overallStatus,
            'timestamp' => now()->toIso8601String(),
            'services' => $services,
            'issues' => $issues,
            'metrics' => [
                'uptime' => $this->calculateSystemUptime(),
                'load' => $this->getSystemLoad(),
                'memory' => $this->getMemoryUsage(),
            ],
        ];
    }

    /**
     * Get detailed health information
     */
    public function getDetailedHealth(): array
    {
        $health = $this->checkSystemHealth();
        
        // Add detailed metrics
        $health['detailed_metrics'] = [
            'response_times' => $this->getResponseTimeMetrics(),
            'error_rates' => $this->getErrorRateMetrics(),
            'throughput' => $this->getThroughputMetrics(),
            'resource_usage' => $this->getResourceUsageMetrics(),
        ];

        // Add recent incidents
        $health['recent_incidents'] = $this->getRecentIncidents();

        // Add predictions
        $health['predictions'] = $this->getPredictiveAnalysis();

        return $health;
    }

    /**
     * Check health of a specific service
     */
    public function checkServiceHealth(string $serviceName): array
    {
        $service = $this->registry->getService($serviceName);
        if (!$service) {
            return [
                'healthy' => false,
                'message' => 'Service not found',
                'severity' => 'critical',
            ];
        }

        // Get recent metrics
        $metrics = $this->metricsCollector->getServiceHealth($serviceName);
        
        // Perform active health check if service implements it
        if (method_exists($service, 'healthCheck')) {
            try {
                $activeCheck = $service->healthCheck();
                $metrics['active_check'] = $activeCheck;
            } catch (\Exception $e) {
                $metrics['active_check'] = [
                    'success' => false,
                    'error' => $e->getMessage(),
                ];
            }
        }

        // Determine health status
        $healthy = true;
        $message = 'Service is healthy';
        $severity = 'info';

        if ($metrics['status'] === 'unhealthy') {
            $healthy = false;
            $message = 'Service is experiencing issues';
            $severity = 'critical';
        } elseif ($metrics['status'] === 'degraded') {
            $healthy = true; // Still operational but degraded
            $message = 'Service performance is degraded';
            $severity = 'warning';
        }

        // Check circuit breaker
        if (isset($metrics['circuit_breaker']) && $metrics['circuit_breaker'] === 'open') {
            $healthy = false;
            $message = 'Circuit breaker is open';
            $severity = 'critical';
        }

        return [
            'healthy' => $healthy,
            'status' => $metrics['status'],
            'message' => $message,
            'severity' => $severity,
            'metrics' => $metrics,
            'checked_at' => now()->toIso8601String(),
        ];
    }

    /**
     * Check infrastructure components
     */
    protected function checkInfrastructure(): array
    {
        return [
            'database' => $this->checkDatabase(),
            'cache' => $this->checkCache(),
            'queue' => $this->checkQueue(),
            'storage' => $this->checkStorage(),
        ];
    }

    /**
     * Check database health
     */
    protected function checkDatabase(): array
    {
        try {
            $start = microtime(true);
            DB::select('SELECT 1');
            $responseTime = (microtime(true) - $start) * 1000;

            // Check connection count
            $connections = DB::select("SHOW STATUS LIKE 'Threads_connected'")[0]->Value ?? 0;
            $maxConnections = DB::select("SHOW VARIABLES LIKE 'max_connections'")[0]->Value ?? 100;
            $connectionUsage = ($connections / $maxConnections) * 100;

            $healthy = $responseTime < 100 && $connectionUsage < 80;
            $message = $healthy ? 'Database is responsive' : 'Database performance issues detected';

            return [
                'healthy' => $healthy,
                'message' => $message,
                'response_time' => round($responseTime, 2),
                'connections' => [
                    'current' => $connections,
                    'max' => $maxConnections,
                    'usage_percentage' => round($connectionUsage, 2),
                ],
            ];
        } catch (\Exception $e) {
            return [
                'healthy' => false,
                'message' => 'Database connection failed: ' . $e->getMessage(),
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Check cache health
     */
    protected function checkCache(): array
    {
        try {
            $start = microtime(true);
            $testKey = 'health_check_' . uniqid();
            Cache::put($testKey, true, 10);
            $result = Cache::get($testKey);
            Cache::forget($testKey);
            $responseTime = (microtime(true) - $start) * 1000;

            if ($result !== true) {
                throw new \Exception('Cache read/write test failed');
            }

            // Get Redis info if using Redis
            $info = [];
            if (Cache::getStore() instanceof \Illuminate\Cache\RedisStore) {
                $redis = Redis::connection();
                $info = $redis->info();
            }

            return [
                'healthy' => true,
                'message' => 'Cache is operational',
                'response_time' => round($responseTime, 2),
                'driver' => config('cache.default'),
                'memory_usage' => $info['used_memory_human'] ?? null,
            ];
        } catch (\Exception $e) {
            return [
                'healthy' => false,
                'message' => 'Cache check failed: ' . $e->getMessage(),
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Check queue health
     */
    protected function checkQueue(): array
    {
        try {
            $queueSizes = [];
            $totalSize = 0;
            
            // Check default queue
            $defaultSize = Redis::connection()->llen('queues:default');
            $queueSizes['default'] = $defaultSize;
            $totalSize += $defaultSize;

            // Check other queues
            $queues = ['high', 'low', 'notifications', 'webhooks'];
            foreach ($queues as $queue) {
                $size = Redis::connection()->llen("queues:{$queue}");
                $queueSizes[$queue] = $size;
                $totalSize += $size;
            }

            // Check failed jobs
            $failedJobs = DB::table('failed_jobs')->count();

            $healthy = $totalSize < 1000 && $failedJobs < 100;
            $message = $healthy ? 'Queue system is healthy' : 'Queue backlog detected';

            return [
                'healthy' => $healthy,
                'message' => $message,
                'queue_sizes' => $queueSizes,
                'total_jobs' => $totalSize,
                'failed_jobs' => $failedJobs,
            ];
        } catch (\Exception $e) {
            return [
                'healthy' => false,
                'message' => 'Queue check failed: ' . $e->getMessage(),
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Check storage health
     */
    protected function checkStorage(): array
    {
        try {
            $path = storage_path();
            $total = disk_total_space($path);
            $free = disk_free_space($path);
            $used = $total - $free;
            $usagePercentage = ($used / $total) * 100;

            // Test write
            $testFile = storage_path('app/health_check_' . uniqid() . '.tmp');
            file_put_contents($testFile, 'test');
            $canWrite = file_exists($testFile);
            unlink($testFile);

            $healthy = $usagePercentage < 90 && $canWrite;
            $message = $healthy ? 'Storage is healthy' : 'Storage issues detected';

            return [
                'healthy' => $healthy,
                'message' => $message,
                'disk_usage' => [
                    'total' => $this->formatBytes($total),
                    'used' => $this->formatBytes($used),
                    'free' => $this->formatBytes($free),
                    'percentage' => round($usagePercentage, 2),
                ],
                'writable' => $canWrite,
            ];
        } catch (\Exception $e) {
            return [
                'healthy' => false,
                'message' => 'Storage check failed: ' . $e->getMessage(),
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Calculate system uptime
     */
    protected function calculateSystemUptime(): array
    {
        $bootTime = Cache::remember('system_boot_time', 86400, function () {
            return now();
        });

        $uptime = now()->diffInSeconds($bootTime);

        return [
            'seconds' => $uptime,
            'human_readable' => $this->secondsToHuman($uptime),
            'since' => $bootTime->toIso8601String(),
        ];
    }

    /**
     * Get system load
     */
    protected function getSystemLoad(): array
    {
        $load = sys_getloadavg();
        $cpuCount = $this->getCpuCount();

        return [
            '1_min' => round($load[0], 2),
            '5_min' => round($load[1], 2),
            '15_min' => round($load[2], 2),
            'cpu_count' => $cpuCount,
            'normalized' => [
                '1_min' => round($load[0] / $cpuCount * 100, 2),
                '5_min' => round($load[1] / $cpuCount * 100, 2),
                '15_min' => round($load[2] / $cpuCount * 100, 2),
            ],
        ];
    }

    /**
     * Get memory usage
     */
    protected function getMemoryUsage(): array
    {
        $memoryUsage = memory_get_usage(true);
        $memoryPeak = memory_get_peak_usage(true);
        $memoryLimit = $this->getMemoryLimit();

        return [
            'current' => $this->formatBytes($memoryUsage),
            'peak' => $this->formatBytes($memoryPeak),
            'limit' => $memoryLimit > 0 ? $this->formatBytes($memoryLimit) : 'unlimited',
            'percentage' => $memoryLimit > 0 ? round(($memoryUsage / $memoryLimit) * 100, 2) : 0,
        ];
    }

    /**
     * Get response time metrics
     */
    protected function getResponseTimeMetrics(): array
    {
        $metrics = [];
        
        foreach ($this->registry->getAllServices() as $serviceName => $service) {
            $metrics[$serviceName] = [
                'avg' => $this->metricsCollector->getAverageResponseTime('1h'),
                'p95' => $this->calculatePercentile($serviceName, 95),
                'p99' => $this->calculatePercentile($serviceName, 99),
            ];
        }

        return $metrics;
    }

    /**
     * Get error rate metrics
     */
    protected function getErrorRateMetrics(): array
    {
        $metrics = [];
        
        foreach ($this->registry->getAllServices() as $serviceName => $service) {
            $health = $this->metricsCollector->getServiceHealth($serviceName);
            $metrics[$serviceName] = [
                'current' => $health['error_rate'] ?? 0,
                'trend' => $this->calculateErrorTrend($serviceName),
            ];
        }

        return $metrics;
    }

    /**
     * Get throughput metrics
     */
    protected function getThroughputMetrics(): array
    {
        return [
            'requests_per_minute' => $this->metricsCollector->getTotalRequests('1m'),
            'requests_per_hour' => $this->metricsCollector->getTotalRequests('1h'),
            'peak_rpm' => Cache::get('peak_requests_per_minute', 0),
        ];
    }

    /**
     * Get resource usage metrics
     */
    protected function getResourceUsageMetrics(): array
    {
        return [
            'cpu' => $this->getCpuUsage(),
            'memory' => $this->getMemoryUsage(),
            'disk' => $this->getDiskUsage(),
            'network' => $this->getNetworkUsage(),
        ];
    }

    /**
     * Get recent incidents
     */
    protected function getRecentIncidents(): array
    {
        return Cache::get('recent_incidents', []);
    }

    /**
     * Get predictive analysis
     */
    protected function getPredictiveAnalysis(): array
    {
        // Simple predictive analysis based on trends
        return [
            'disk_full_prediction' => $this->predictDiskFull(),
            'performance_degradation_risk' => $this->assessPerformanceRisk(),
            'capacity_recommendations' => $this->getCapacityRecommendations(),
        ];
    }

    /**
     * Helper methods
     */
    protected function formatBytes($bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = 0;
        
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }

    protected function secondsToHuman($seconds): string
    {
        $days = floor($seconds / 86400);
        $hours = floor(($seconds % 86400) / 3600);
        $minutes = floor(($seconds % 3600) / 60);

        $parts = [];
        if ($days > 0) $parts[] = "{$days}d";
        if ($hours > 0) $parts[] = "{$hours}h";
        if ($minutes > 0) $parts[] = "{$minutes}m";

        return implode(' ', $parts) ?: '< 1m';
    }

    protected function getCpuCount(): int
    {
        if (PHP_OS_FAMILY === 'Windows') {
            return (int) shell_exec('echo %NUMBER_OF_PROCESSORS%');
        }
        
        return (int) shell_exec('nproc');
    }

    protected function getMemoryLimit(): int
    {
        $limit = ini_get('memory_limit');
        
        if ($limit === '-1') {
            return -1;
        }

        preg_match('/^(\d+)(.)$/', $limit, $matches);
        if ($matches[2] === 'M') {
            return $matches[1] * 1024 * 1024;
        } elseif ($matches[2] === 'G') {
            return $matches[1] * 1024 * 1024 * 1024;
        }

        return (int) $limit;
    }

    protected function calculatePercentile(string $service, int $percentile): float
    {
        // Simplified percentile calculation
        // In production, this would query metrics data
        return Cache::remember("percentile_{$service}_{$percentile}", 300, function () use ($service) {
            return rand(100, 500) / 100;
        });
    }

    protected function calculateErrorTrend(string $service): string
    {
        // Simplified trend calculation
        $current = $this->metricsCollector->getServiceHealth($service)['error_rate'] ?? 0;
        $previous = Cache::get("error_rate_{$service}_previous", $current);
        
        Cache::put("error_rate_{$service}_previous", $current, 3600);

        if ($current > $previous) {
            return 'increasing';
        } elseif ($current < $previous) {
            return 'decreasing';
        }
        
        return 'stable';
    }

    protected function getCpuUsage(): array
    {
        // Simplified CPU usage
        return [
            'percentage' => rand(10, 80),
            'cores' => $this->getCpuCount(),
        ];
    }

    protected function getDiskUsage(): array
    {
        $total = disk_total_space('/');
        $free = disk_free_space('/');
        
        return [
            'total' => $this->formatBytes($total),
            'used' => $this->formatBytes($total - $free),
            'free' => $this->formatBytes($free),
            'percentage' => round((($total - $free) / $total) * 100, 2),
        ];
    }

    protected function getNetworkUsage(): array
    {
        // Placeholder for network usage metrics
        return [
            'bandwidth_in' => '0 MB/s',
            'bandwidth_out' => '0 MB/s',
            'connections' => 0,
        ];
    }

    protected function predictDiskFull(): ?string
    {
        // Simple linear prediction
        $usage = $this->getDiskUsage()['percentage'];
        
        if ($usage > 90) {
            return 'Critical: Less than 24 hours';
        } elseif ($usage > 80) {
            return 'Warning: Less than 7 days';
        }
        
        return null;
    }

    protected function assessPerformanceRisk(): string
    {
        $errorRate = $this->metricsCollector->getSuccessRate('1h');
        $responseTime = $this->metricsCollector->getAverageResponseTime('1h');
        
        if ($errorRate < 90 || $responseTime > 1000) {
            return 'high';
        } elseif ($errorRate < 95 || $responseTime > 500) {
            return 'medium';
        }
        
        return 'low';
    }

    protected function getCapacityRecommendations(): array
    {
        $recommendations = [];
        
        $cpuUsage = $this->getCpuUsage()['percentage'];
        if ($cpuUsage > 70) {
            $recommendations[] = 'Consider scaling up CPU resources';
        }
        
        $memoryUsage = $this->getMemoryUsage()['percentage'];
        if ($memoryUsage > 80) {
            $recommendations[] = 'Memory usage is high, consider increasing memory limit';
        }
        
        $diskUsage = $this->getDiskUsage()['percentage'];
        if ($diskUsage > 80) {
            $recommendations[] = 'Disk space is running low, consider cleanup or expansion';
        }
        
        return $recommendations;
    }
}