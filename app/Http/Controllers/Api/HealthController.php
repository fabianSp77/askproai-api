<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Cache;

class HealthController extends Controller
{
    /**
     * Health check endpoint for monitoring
     */
    public function index(): JsonResponse
    {
        $health = [
            'status' => 'healthy',
            'timestamp' => now()->toIso8601String(),
            'environment' => config('app.env'),
            'version' => config('app.version', '1.0.0'),
            'services' => []
        ];

        // Check database
        try {
            DB::select('SELECT 1');
            $health['services']['database'] = [
                'status' => 'up',
                'response_time' => $this->measureTime(fn() => DB::select('SELECT 1'))
            ];
        } catch (\Exception $e) {
            $health['services']['database'] = [
                'status' => 'down',
                'error' => $e->getMessage()
            ];
            $health['status'] = 'unhealthy';
        }

        // Check Redis
        try {
            Redis::ping();
            $health['services']['redis'] = [
                'status' => 'up',
                'response_time' => $this->measureTime(fn() => Redis::ping())
            ];
        } catch (\Exception $e) {
            $health['services']['redis'] = [
                'status' => 'down',
                'error' => $e->getMessage()
            ];
            $health['status'] = 'degraded';
        }

        // Check cache
        try {
            $key = 'health_check_' . time();
            Cache::put($key, true, 1);
            Cache::forget($key);
            $health['services']['cache'] = [
                'status' => 'up',
                'driver' => config('cache.default')
            ];
        } catch (\Exception $e) {
            $health['services']['cache'] = [
                'status' => 'down',
                'error' => $e->getMessage()
            ];
            $health['status'] = 'degraded';
        }

        // Add system metrics
        $health['metrics'] = [
            'memory_usage' => round(memory_get_usage() / 1024 / 1024, 2) . ' MB',
            'memory_peak' => round(memory_get_peak_usage() / 1024 / 1024, 2) . ' MB',
            'uptime' => $this->getUptime()
        ];

        $statusCode = $health['status'] === 'healthy' ? 200 :
                      ($health['status'] === 'degraded' ? 206 : 503);

        return response()->json($health, $statusCode);
    }

    /**
     * Detailed health check with extended metrics
     */
    public function detailed(): JsonResponse
    {
        $health = $this->index()->getData(true);

        // Add detailed database metrics
        try {
            $dbStats = DB::select("
                SELECT
                    COUNT(*) as total_connections,
                    (SELECT COUNT(*) FROM customers) as total_customers,
                    (SELECT COUNT(*) FROM calls) as total_calls,
                    (SELECT COUNT(*) FROM appointments) as total_appointments
            ")[0];

            $health['services']['database']['stats'] = [
                'total_customers' => $dbStats->total_customers,
                'total_calls' => $dbStats->total_calls,
                'total_appointments' => $dbStats->total_appointments
            ];
        } catch (\Exception $e) {
            // Ignore errors for detailed stats
        }

        // Add Redis metrics
        try {
            $redisInfo = Redis::info();
            if (isset($redisInfo['Stats'])) {
                $health['services']['redis']['stats'] = [
                    'connected_clients' => $redisInfo['Stats']['connected_clients'] ?? 0,
                    'used_memory' => $redisInfo['Stats']['used_memory_human'] ?? 'N/A',
                    'total_connections_received' => $redisInfo['Stats']['total_connections_received'] ?? 0
                ];
            }
        } catch (\Exception $e) {
            // Ignore errors for detailed stats
        }

        return response()->json($health);
    }

    /**
     * Measure execution time in milliseconds
     */
    private function measureTime(callable $callback): float
    {
        $start = microtime(true);
        $callback();
        return round((microtime(true) - $start) * 1000, 2);
    }

    /**
     * Get system uptime
     */
    private function getUptime(): string
    {
        if (PHP_OS_FAMILY === 'Linux') {
            $uptime = shell_exec('uptime -p');
            return trim($uptime) ?: 'Unknown';
        }
        return 'Unknown';
    }
}