<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ErrorMonitoringService;
use App\Http\Middleware\PerformanceMonitoringMiddleware;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Redis;

class HealthCheckController extends Controller
{
    private ErrorMonitoringService $errorService;

    public function __construct(ErrorMonitoringService $errorService)
    {
        $this->errorService = $errorService;
    }

    /**
     * Basic health check endpoint
     * GET /api/health
     */
    public function basic(): JsonResponse
    {
        return response()->json([
            'status' => 'healthy',
            'timestamp' => now()->toIso8601String(),
            'environment' => app()->environment(),
            'version' => config('app.version', '1.0.0'),
        ]);
    }

    /**
     * Detailed health check with all system components
     * GET /api/health/detailed
     */
    public function detailed(): JsonResponse
    {
        $startTime = microtime(true);
        $checks = [];

        // Database check
        $checks['database'] = $this->checkDatabase();

        // Redis/Cache check
        $checks['cache'] = $this->checkCache();

        // File system check
        $checks['filesystem'] = $this->checkFilesystem();

        // External services check
        $checks['external_services'] = $this->checkExternalServices();

        // System resources
        $checks['system'] = $this->checkSystemResources();

        // Application metrics
        $checks['application'] = $this->checkApplicationMetrics();

        // Determine overall health
        $overallHealth = $this->determineOverallHealth($checks);

        $response = [
            'status' => $overallHealth['status'],
            'healthy' => $overallHealth['healthy'],
            'timestamp' => now()->toIso8601String(),
            'environment' => app()->environment(),
            'version' => config('app.version', '1.0.0'),
            'response_time_ms' => round((microtime(true) - $startTime) * 1000, 2),
            'checks' => $checks,
        ];

        $statusCode = $overallHealth['healthy'] ? 200 : 503;

        return response()->json($response, $statusCode);
    }

    /**
     * Performance metrics endpoint
     * GET /api/health/metrics
     */
    public function metrics(): JsonResponse
    {
        $metrics = [
            'timestamp' => now()->toIso8601String(),
            'performance' => PerformanceMonitoringMiddleware::getStatistics(24),
            'errors' => $this->errorService->getStatistics(),
            'system' => $this->getSystemMetrics(),
            'application' => $this->getApplicationMetrics(),
        ];

        return response()->json($metrics);
    }

    /**
     * Check database connectivity and performance
     */
    private function checkDatabase(): array
    {
        $status = 'healthy';
        $details = [];
        $startTime = microtime(true);

        try {
            // Check main connection
            $pdo = DB::connection()->getPdo();
            $details['connected'] = true;
            $details['driver'] = DB::connection()->getDriverName();

            // Check database response time
            $queryStart = microtime(true);
            DB::select('SELECT 1');
            $queryTime = (microtime(true) - $queryStart) * 1000;
            $details['response_time_ms'] = round($queryTime, 2);

            // Check for slow queries
            if ($queryTime > 100) {
                $status = 'degraded';
                $details['warning'] = 'Slow database response time';
            }

            // Get connection stats
            $details['stats'] = [
                'open_connections' => DB::connection()->select("SHOW STATUS LIKE 'Threads_connected'")[0]->Value ?? 0,
                'max_connections' => DB::connection()->select("SHOW VARIABLES LIKE 'max_connections'")[0]->Value ?? 0,
            ];

            // Check migration status
            $pendingMigrations = $this->checkPendingMigrations();
            if ($pendingMigrations > 0) {
                $status = 'degraded';
                $details['pending_migrations'] = $pendingMigrations;
            }

        } catch (\Exception $e) {
            $status = 'unhealthy';
            $details['connected'] = false;
            $details['error'] = $e->getMessage();
        }

        return [
            'status' => $status,
            'response_time_ms' => round((microtime(true) - $startTime) * 1000, 2),
            'details' => $details,
        ];
    }

    /**
     * Check cache/Redis connectivity
     */
    private function checkCache(): array
    {
        $status = 'healthy';
        $details = [];
        $startTime = microtime(true);

        try {
            // Test cache write
            $testKey = 'health_check_' . now()->timestamp;
            Cache::put($testKey, 'test', 10);

            // Test cache read
            $value = Cache::get($testKey);
            $details['read_write'] = $value === 'test';

            // Clean up
            Cache::forget($testKey);

            // Check Redis if used
            if (config('cache.default') === 'redis') {
                try {
                    $redis = Redis::connection();
                    $info = $redis->info();
                    $details['redis'] = [
                        'connected' => true,
                        'version' => $info['redis_version'] ?? 'unknown',
                        'used_memory' => $info['used_memory_human'] ?? 'unknown',
                        'connected_clients' => $info['connected_clients'] ?? 0,
                    ];
                } catch (\Exception $e) {
                    $status = 'degraded';
                    $details['redis'] = [
                        'connected' => false,
                        'error' => $e->getMessage(),
                    ];
                }
            }

        } catch (\Exception $e) {
            $status = 'unhealthy';
            $details['error'] = $e->getMessage();
        }

        return [
            'status' => $status,
            'response_time_ms' => round((microtime(true) - $startTime) * 1000, 2),
            'details' => $details,
        ];
    }

    /**
     * Check filesystem accessibility
     */
    private function checkFilesystem(): array
    {
        $status = 'healthy';
        $details = [];
        $startTime = microtime(true);

        try {
            $directories = [
                'storage' => storage_path(),
                'logs' => storage_path('logs'),
                'cache' => storage_path('framework/cache'),
                'sessions' => storage_path('framework/sessions'),
            ];

            foreach ($directories as $name => $path) {
                $details[$name] = [
                    'exists' => file_exists($path),
                    'writable' => is_writable($path),
                ];

                if (!$details[$name]['exists'] || !$details[$name]['writable']) {
                    $status = 'degraded';
                }
            }

            // Check disk space
            $diskFree = disk_free_space('/');
            $diskTotal = disk_total_space('/');
            $diskUsedPercent = round((($diskTotal - $diskFree) / $diskTotal) * 100, 2);

            $details['disk'] = [
                'free_gb' => round($diskFree / 1073741824, 2),
                'total_gb' => round($diskTotal / 1073741824, 2),
                'used_percent' => $diskUsedPercent,
            ];

            if ($diskUsedPercent > 90) {
                $status = 'unhealthy';
                $details['disk']['warning'] = 'Critical disk space';
            } elseif ($diskUsedPercent > 80) {
                $status = 'degraded';
                $details['disk']['warning'] = 'Low disk space';
            }

        } catch (\Exception $e) {
            $status = 'unhealthy';
            $details['error'] = $e->getMessage();
        }

        return [
            'status' => $status,
            'response_time_ms' => round((microtime(true) - $startTime) * 1000, 2),
            'details' => $details,
        ];
    }

    /**
     * Check external service connectivity
     */
    private function checkExternalServices(): array
    {
        $status = 'healthy';
        $services = [];

        // Check Cal.com API
        if (config('services.calcom.api_key')) {
            $services['calcom'] = $this->checkExternalService(
                'Cal.com API',
                config('services.calcom.api_url', 'https://api.cal.com') . '/health'
            );
        }

        // Check Stripe API
        if (config('services.stripe.key')) {
            $services['stripe'] = $this->checkExternalService(
                'Stripe API',
                'https://api.stripe.com',
                ['Authorization' => 'Bearer ' . config('services.stripe.key')]
            );
        }

        // Check Retell AI
        if (config('services.retellai.api_key')) {
            $services['retellai'] = $this->checkExternalService(
                'Retell AI',
                'https://api.retell.ai/health'
            );
        }

        // Determine overall status
        foreach ($services as $service) {
            if ($service['status'] === 'unhealthy') {
                $status = 'degraded';
            }
        }

        return [
            'status' => $status,
            'services' => $services,
        ];
    }

    /**
     * Check individual external service
     */
    private function checkExternalService(string $name, string $url, array $headers = []): array
    {
        $startTime = microtime(true);

        try {
            $response = Http::timeout(5)
                ->withHeaders($headers)
                ->get($url);

            return [
                'name' => $name,
                'status' => $response->successful() ? 'healthy' : 'unhealthy',
                'response_time_ms' => round((microtime(true) - $startTime) * 1000, 2),
                'status_code' => $response->status(),
            ];
        } catch (\Exception $e) {
            return [
                'name' => $name,
                'status' => 'unhealthy',
                'response_time_ms' => round((microtime(true) - $startTime) * 1000, 2),
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Check system resources
     */
    private function checkSystemResources(): array
    {
        $status = 'healthy';
        $details = [];

        // Memory usage
        $memoryUsage = memory_get_usage(true);
        $memoryPeak = memory_get_peak_usage(true);
        $memoryLimit = $this->getMemoryLimit();

        $details['memory'] = [
            'current_mb' => round($memoryUsage / 1048576, 2),
            'peak_mb' => round($memoryPeak / 1048576, 2),
            'limit_mb' => round($memoryLimit / 1048576, 2),
            'usage_percent' => round(($memoryUsage / $memoryLimit) * 100, 2),
        ];

        if ($details['memory']['usage_percent'] > 80) {
            $status = 'degraded';
            $details['memory']['warning'] = 'High memory usage';
        }

        // CPU load
        $loadAverage = sys_getloadavg();
        $cpuCount = $this->getCpuCount();

        $details['cpu'] = [
            'load_1min' => $loadAverage[0] ?? 0,
            'load_5min' => $loadAverage[1] ?? 0,
            'load_15min' => $loadAverage[2] ?? 0,
            'cores' => $cpuCount,
        ];

        if ($loadAverage[0] > $cpuCount * 2) {
            $status = 'degraded';
            $details['cpu']['warning'] = 'High CPU load';
        }

        // Process info
        $details['process'] = [
            'pid' => getmypid(),
            'uid' => getmyuid(),
            'gid' => getmygid(),
        ];

        return [
            'status' => $status,
            'details' => $details,
        ];
    }

    /**
     * Check application-specific metrics
     */
    private function checkApplicationMetrics(): array
    {
        $details = [];

        // Queue status
        try {
            $details['queue'] = [
                'default' => DB::table('jobs')->count(),
                'failed' => DB::table('failed_jobs')->count(),
            ];
        } catch (\Exception $e) {
            $details['queue'] = ['error' => 'Unable to check queue status'];
        }

        // Active sessions
        try {
            $details['sessions'] = [
                'active' => DB::table('sessions')->where('last_activity', '>', now()->subMinutes(30))->count(),
                'total' => DB::table('sessions')->count(),
            ];
        } catch (\Exception $e) {
            $details['sessions'] = ['error' => 'Unable to check sessions'];
        }

        // Recent errors
        $errorStats = $this->errorService->getStatistics();
        $details['errors'] = [
            'total_24h' => $errorStats['summary']['total_errors'] ?? 0,
            'unique_24h' => $errorStats['summary']['unique_errors'] ?? 0,
        ];

        return [
            'status' => 'healthy',
            'details' => $details,
        ];
    }

    /**
     * Determine overall health status
     */
    private function determineOverallHealth(array $checks): array
    {
        $unhealthyCount = 0;
        $degradedCount = 0;

        foreach ($checks as $check) {
            if ($check['status'] === 'unhealthy') {
                $unhealthyCount++;
            } elseif ($check['status'] === 'degraded') {
                $degradedCount++;
            }
        }

        if ($unhealthyCount > 0) {
            return [
                'status' => 'unhealthy',
                'healthy' => false,
            ];
        }

        if ($degradedCount > 0) {
            return [
                'status' => 'degraded',
                'healthy' => true,
            ];
        }

        return [
            'status' => 'healthy',
            'healthy' => true,
        ];
    }

    /**
     * Get system metrics
     */
    private function getSystemMetrics(): array
    {
        return [
            'uptime' => $this->getUptime(),
            'memory' => $this->getMemoryStats(),
            'cpu' => $this->getCpuStats(),
            'disk' => $this->getDiskStats(),
        ];
    }

    /**
     * Get application metrics
     */
    private function getApplicationMetrics(): array
    {
        return [
            'requests_per_minute' => $this->getRequestRate(),
            'average_response_time_ms' => $this->getAverageResponseTime(),
            'error_rate_percent' => $this->getErrorRate(),
            'active_users' => $this->getActiveUsers(),
        ];
    }

    /**
     * Helper methods
     */
    private function checkPendingMigrations(): int
    {
        try {
            $pending = app('migrator')->getMigrationFiles(database_path('migrations'));
            $ran = app('migrator')->getRepository()->getRan();
            return count(array_diff(array_keys($pending), $ran));
        } catch (\Exception $e) {
            return 0;
        }
    }

    private function getMemoryLimit(): int
    {
        $limit = ini_get('memory_limit');
        if ($limit === '-1') {
            return PHP_INT_MAX;
        }

        preg_match('/^(\d+)(.)$/', $limit, $matches);
        if ($matches[2] === 'M') {
            return $matches[1] * 1048576;
        } elseif ($matches[2] === 'G') {
            return $matches[1] * 1073741824;
        }

        return (int) $limit;
    }

    private function getCpuCount(): int
    {
        if (PHP_OS_FAMILY === 'Windows') {
            return (int) getenv('NUMBER_OF_PROCESSORS');
        }

        $cpuinfo = file_get_contents('/proc/cpuinfo');
        preg_match_all('/^processor/m', $cpuinfo, $matches);
        return count($matches[0]);
    }

    private function getUptime(): string
    {
        if (PHP_OS_FAMILY === 'Windows') {
            return 'N/A';
        }

        $uptime = shell_exec('uptime -p');
        return trim($uptime);
    }

    private function getMemoryStats(): array
    {
        return [
            'used_mb' => round(memory_get_usage(true) / 1048576, 2),
            'peak_mb' => round(memory_get_peak_usage(true) / 1048576, 2),
            'limit_mb' => round($this->getMemoryLimit() / 1048576, 2),
        ];
    }

    private function getCpuStats(): array
    {
        $load = sys_getloadavg();
        return [
            '1min' => $load[0] ?? 0,
            '5min' => $load[1] ?? 0,
            '15min' => $load[2] ?? 0,
        ];
    }

    private function getDiskStats(): array
    {
        $free = disk_free_space('/');
        $total = disk_total_space('/');
        return [
            'free_gb' => round($free / 1073741824, 2),
            'total_gb' => round($total / 1073741824, 2),
            'used_percent' => round((($total - $free) / $total) * 100, 2),
        ];
    }

    private function getRequestRate(): float
    {
        $stats = PerformanceMonitoringMiddleware::getStatistics(1);
        $total = 0;
        foreach ($stats as $hourStats) {
            $total += $hourStats['request_count'] ?? 0;
        }
        return round($total / 60, 2);
    }

    private function getAverageResponseTime(): float
    {
        $stats = PerformanceMonitoringMiddleware::getStatistics(1);
        $totalTime = 0;
        $totalRequests = 0;

        foreach ($stats as $hourStats) {
            $totalTime += $hourStats['total_duration'] ?? 0;
            $totalRequests += $hourStats['request_count'] ?? 0;
        }

        if ($totalRequests === 0) {
            return 0;
        }

        return round($totalTime / $totalRequests, 2);
    }

    private function getErrorRate(): float
    {
        $stats = PerformanceMonitoringMiddleware::getStatistics(1);
        $totalRequests = 0;
        $errorRequests = 0;

        foreach ($stats as $hourStats) {
            $totalRequests += $hourStats['request_count'] ?? 0;
            $errorRequests += ($hourStats['status_codes']['5xx'] ?? 0);
        }

        if ($totalRequests === 0) {
            return 0;
        }

        return round(($errorRequests / $totalRequests) * 100, 2);
    }

    private function getActiveUsers(): int
    {
        try {
            return DB::table('sessions')
                ->where('last_activity', '>', now()->subMinutes(30)->timestamp)
                ->count();
        } catch (\Exception $e) {
            return 0;
        }
    }
}