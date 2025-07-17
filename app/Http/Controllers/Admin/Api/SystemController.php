<?php

namespace App\Http\Controllers\Admin\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Artisan;

class SystemController extends Controller
{
    public function health(): JsonResponse
    {
        $health = [
            'status' => 'operational',
            'timestamp' => now()->toIso8601String(),
            'components' => [
                'database' => $this->checkDatabase(),
                'cache' => $this->checkCache(),
                'queue' => $this->checkQueue(),
                'storage' => $this->checkStorage(),
            ],
            'metrics' => [
                'cpu_usage' => $this->getCpuUsage(),
                'memory_usage' => $this->getMemoryUsage(),
                'disk_usage' => $this->getDiskUsage(),
                'response_time' => $this->getAverageResponseTime(),
            ],
        ];

        // Determine overall status
        $hasIssues = collect($health['components'])->contains('status', '!=', 'operational');
        if ($hasIssues) {
            $health['status'] = 'degraded';
        }

        return response()->json($health);
    }

    public function logs(Request $request): JsonResponse
    {
        $type = $request->get('type', 'laravel');
        $lines = $request->get('lines', 100);
        
        $logFile = storage_path("logs/{$type}.log");
        
        if (!file_exists($logFile)) {
            return response()->json(['logs' => []]);
        }

        $logs = [];
        $file = new \SplFileObject($logFile);
        $file->seek($file->getSize());
        $lineCount = 0;

        while ($lineCount < $lines && $file->key() > 0) {
            $file->seek($file->key() - 1);
            $line = $file->current();
            if (trim($line) !== '') {
                array_unshift($logs, trim($line));
                $lineCount++;
            }
        }

        return response()->json(['logs' => $logs]);
    }

    public function queueStatus(): JsonResponse
    {
        $status = [
            'jobs' => [
                'pending' => DB::table('jobs')->count(),
                'failed' => DB::table('failed_jobs')->count(),
                'delayed' => DB::table('jobs')->where('available_at', '>', now()->timestamp)->count(),
            ],
            'workers' => $this->getQueueWorkerStatus(),
            'horizon' => $this->getHorizonStatus(),
        ];

        return response()->json($status);
    }

    public function clearCache(Request $request): JsonResponse
    {
        $type = $request->get('type', 'all');

        switch ($type) {
            case 'application':
                Artisan::call('cache:clear');
                $message = 'Application cache cleared';
                break;
            case 'config':
                Artisan::call('config:clear');
                $message = 'Configuration cache cleared';
                break;
            case 'route':
                Artisan::call('route:clear');
                $message = 'Route cache cleared';
                break;
            case 'view':
                Artisan::call('view:clear');
                $message = 'View cache cleared';
                break;
            case 'all':
            default:
                Artisan::call('optimize:clear');
                $message = 'All caches cleared';
                break;
        }

        return response()->json(['message' => $message]);
    }

    private function checkDatabase(): array
    {
        try {
            $start = microtime(true);
            DB::connection()->getPdo();
            $responseTime = round((microtime(true) - $start) * 1000, 2);
            
            return [
                'status' => 'operational',
                'response_time_ms' => $responseTime,
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'down',
                'error' => $e->getMessage(),
            ];
        }
    }

    private function checkCache(): array
    {
        try {
            $start = microtime(true);
            Cache::put('health_check', true, 1);
            $result = Cache::get('health_check');
            $responseTime = round((microtime(true) - $start) * 1000, 2);
            
            return [
                'status' => $result === true ? 'operational' : 'degraded',
                'response_time_ms' => $responseTime,
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'down',
                'error' => $e->getMessage(),
            ];
        }
    }

    private function checkQueue(): array
    {
        try {
            $pendingJobs = DB::table('jobs')->count();
            $failedJobs = DB::table('failed_jobs')->count();
            
            $status = 'operational';
            if ($failedJobs > 100) {
                $status = 'degraded';
            } elseif ($pendingJobs > 1000) {
                $status = 'degraded';
            }
            
            return [
                'status' => $status,
                'pending_jobs' => $pendingJobs,
                'failed_jobs' => $failedJobs,
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'unknown',
                'error' => $e->getMessage(),
            ];
        }
    }

    private function checkStorage(): array
    {
        try {
            $diskUsage = $this->getDiskUsage();
            
            $status = 'operational';
            if ($diskUsage > 90) {
                $status = 'critical';
            } elseif ($diskUsage > 80) {
                $status = 'degraded';
            }
            
            return [
                'status' => $status,
                'disk_usage_percent' => $diskUsage,
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'unknown',
                'error' => $e->getMessage(),
            ];
        }
    }

    private function getCpuUsage(): float
    {
        if (PHP_OS_FAMILY === 'Linux') {
            $load = sys_getloadavg();
            $cpuCount = shell_exec('nproc');
            return round(($load[0] / intval($cpuCount)) * 100, 2);
        }
        return 0;
    }

    private function getMemoryUsage(): float
    {
        $memoryUsage = memory_get_usage(true);
        $memoryLimit = $this->getMemoryLimit();
        
        if ($memoryLimit > 0) {
            return round(($memoryUsage / $memoryLimit) * 100, 2);
        }
        
        return 0;
    }

    private function getMemoryLimit(): int
    {
        $limit = ini_get('memory_limit');
        
        if ($limit === '-1') {
            return PHP_INT_MAX;
        }
        
        preg_match('/^(\d+)(.)$/', $limit, $matches);
        if ($matches) {
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

    private function getDiskUsage(): float
    {
        $free = disk_free_space('/');
        $total = disk_total_space('/');
        
        if ($total > 0) {
            return round((($total - $free) / $total) * 100, 2);
        }
        
        return 0;
    }

    private function getAverageResponseTime(): float
    {
        // This would typically query your APM or logging system
        // For now, return a placeholder
        return 142.5;
    }

    private function getQueueWorkerStatus(): array
    {
        // Check if Horizon is running
        $horizonStatus = $this->getHorizonStatus();
        
        return [
            'active' => $horizonStatus['is_running'] ?? false,
            'processes' => $horizonStatus['processes'] ?? 0,
        ];
    }

    private function getHorizonStatus(): array
    {
        try {
            // Check if Horizon is running by checking for the process
            $output = shell_exec('ps aux | grep -c "[h]orizon"');
            $processCount = intval(trim($output));
            
            return [
                'is_running' => $processCount > 0,
                'processes' => $processCount,
            ];
        } catch (\Exception $e) {
            return [
                'is_running' => false,
                'error' => $e->getMessage(),
            ];
        }
    }
}