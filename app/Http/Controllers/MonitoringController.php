<?php

namespace App\Http\Controllers;

use App\Services\ErrorMonitoringService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;

class MonitoringController extends Controller
{
    private ErrorMonitoringService $errorMonitor;

    public function __construct(ErrorMonitoringService $errorMonitor)
    {
        $this->errorMonitor = $errorMonitor;
    }

    /**
     * Health check endpoint for load balancers
     */
    public function health()
    {
        $checks = [
            'database' => $this->checkDatabase(),
            'cache' => $this->checkCache(),
            'redis' => $this->checkRedis(),
            'storage' => $this->checkStorage(),
            'queue' => $this->checkQueue(),
        ];

        $healthy = !in_array(false, $checks, true);
        $status = $healthy ? 200 : 503;

        return response()->json([
            'status' => $healthy ? 'healthy' : 'unhealthy',
            'timestamp' => now()->toIso8601String(),
            'checks' => $checks,
            'metrics' => $this->getMetrics(),
        ], $status);
    }

    /**
     * Detailed metrics endpoint
     */
    public function metrics()
    {
        return response()->json([
            'timestamp' => now()->toIso8601String(),
            'system' => $this->getSystemMetrics(),
            'application' => $this->getApplicationMetrics(),
            'errors' => $this->errorMonitor->getStatistics(),
            'performance' => $this->getPerformanceMetrics(),
        ]);
    }

    /**
     * Live dashboard data
     */
    public function dashboard()
    {
        return response()->json([
            'realtime' => [
                'active_users' => $this->getActiveUsers(),
                'requests_per_minute' => $this->getRequestsPerMinute(),
                'error_rate' => $this->getErrorRate(),
                'response_time' => $this->getAverageResponseTime(),
            ],
            'alerts' => $this->getActiveAlerts(),
            'system_status' => $this->getSystemStatus(),
        ]);
    }

    /**
     * Check database connectivity
     */
    private function checkDatabase(): bool
    {
        try {
            DB::connection()->getPdo();
            DB::select('SELECT 1');
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Check cache functionality
     */
    private function checkCache(): bool
    {
        try {
            $key = 'health_check_' . time();
            Cache::put($key, true, 10);
            $result = Cache::get($key) === true;
            Cache::forget($key);
            return $result;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Check Redis connectivity
     */
    private function checkRedis(): bool
    {
        try {
            Redis::ping();
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Check storage writability
     */
    private function checkStorage(): bool
    {
        try {
            $path = storage_path('app/health_check_' . time() . '.tmp');
            file_put_contents($path, 'test');
            $result = file_exists($path);
            unlink($path);
            return $result;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Check queue connectivity
     */
    private function checkQueue(): bool
    {
        try {
            // Check if queue connection is working
            $size = \Queue::size();
            return is_numeric($size);
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get system metrics
     */
    private function getSystemMetrics(): array
    {
        return [
            'memory' => [
                'usage' => memory_get_usage(true),
                'peak' => memory_get_peak_usage(true),
                'limit' => $this->getMemoryLimit(),
            ],
            'cpu' => [
                'load_average' => sys_getloadavg(),
            ],
            'disk' => [
                'free' => disk_free_space('/'),
                'total' => disk_total_space('/'),
            ],
            'php' => [
                'version' => PHP_VERSION,
                'sapi' => PHP_SAPI,
            ],
        ];
    }

    /**
     * Get application metrics
     */
    private function getApplicationMetrics(): array
    {
        return [
            'laravel_version' => app()->version(),
            'environment' => app()->environment(),
            'debug_mode' => config('app.debug'),
            'maintenance_mode' => app()->isDownForMaintenance(),
            'cached_config' => file_exists(base_path('bootstrap/cache/config.php')),
            'cached_routes' => file_exists(base_path('bootstrap/cache/routes-v7.php')),
        ];
    }

    /**
     * Get performance metrics
     */
    private function getPerformanceMetrics(): array
    {
        $metrics = Cache::get('performance_metrics', []);

        return [
            'average_response_time' => $metrics['avg_response_time'] ?? 0,
            'requests_per_minute' => $metrics['requests_per_minute'] ?? 0,
            'slow_requests' => $metrics['slow_requests'] ?? 0,
            'database_queries' => $metrics['database_queries'] ?? 0,
        ];
    }

    /**
     * Get basic metrics
     */
    private function getMetrics(): array
    {
        return [
            'uptime' => $this->getUptime(),
            'memory_usage_mb' => round(memory_get_usage(true) / 1024 / 1024, 2),
            'connections' => DB::connection()->selectOne('SHOW STATUS LIKE "Threads_connected"')->Value ?? 0,
        ];
    }

    /**
     * Get system uptime
     */
    private function getUptime(): string
    {
        if (PHP_OS_FAMILY === 'Linux') {
            $uptime = shell_exec('uptime -p');
            return trim($uptime ?: 'unknown');
        }
        return 'unknown';
    }

    /**
     * Get memory limit
     */
    private function getMemoryLimit(): int
    {
        $limit = ini_get('memory_limit');
        if ($limit == -1) {
            return PHP_INT_MAX;
        }

        preg_match('/^(\d+)(.)$/', $limit, $matches);
        if (isset($matches[2])) {
            $value = (int) $matches[1];
            switch (strtoupper($matches[2])) {
                case 'G':
                    return $value * 1024 * 1024 * 1024;
                case 'M':
                    return $value * 1024 * 1024;
                case 'K':
                    return $value * 1024;
            }
        }
        return (int) $limit;
    }

    /**
     * Get active users count
     */
    private function getActiveUsers(): int
    {
        return Cache::get('active_users_count', 0);
    }

    /**
     * Get requests per minute
     */
    private function getRequestsPerMinute(): int
    {
        return Cache::get('requests_per_minute', 0);
    }

    /**
     * Get error rate
     */
    private function getErrorRate(): float
    {
        $total = Cache::get('total_requests', 1);
        $errors = Cache::get('error_requests', 0);
        return round(($errors / $total) * 100, 2);
    }

    /**
     * Get average response time
     */
    private function getAverageResponseTime(): float
    {
        return Cache::get('average_response_time', 0.0);
    }

    /**
     * Get active alerts
     */
    private function getActiveAlerts(): array
    {
        return Cache::get('active_alerts', []);
    }

    /**
     * Get system status
     */
    private function getSystemStatus(): array
    {
        return [
            'database' => $this->checkDatabase() ? 'online' : 'offline',
            'cache' => $this->checkCache() ? 'online' : 'offline',
            'redis' => $this->checkRedis() ? 'online' : 'offline',
            'queue' => $this->checkQueue() ? 'online' : 'offline',
        ];
    }
}