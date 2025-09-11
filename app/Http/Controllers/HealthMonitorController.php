<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Artisan;

class HealthMonitorController extends Controller
{
    /**
     * Display the health monitoring dashboard
     */
    public function dashboard()
    {
        // Get latest health data from cache
        $latestHealth = Cache::get('health_monitor_latest', [
            'status' => 'unknown',
            'timestamp' => now()->toIso8601String(),
            'issues' => 0,
            'results' => [],
            'alerts' => []
        ]);

        // Get historical data
        $history = [];
        $historyFile = storage_path('logs/health-monitor.json');
        if (File::exists($historyFile)) {
            $history = json_decode(File::get($historyFile), true) ?: [];
            $history = array_slice($history, 0, 20); // Last 20 checks
        }

        // Get monitoring logs
        $monitoringLog = '';
        $logFile = storage_path('logs/monitoring.log');
        if (File::exists($logFile)) {
            $monitoringLog = File::get($logFile, false, null, max(0, File::size($logFile) - 10000));
        }

        // Get system stats
        $systemStats = $this->getSystemStats();

        return view('health-monitor.dashboard', compact(
            'latestHealth',
            'history',
            'monitoringLog',
            'systemStats'
        ));
    }

    /**
     * Run health check via AJAX
     */
    public function check(Request $request)
    {
        $autoFix = $request->boolean('autoFix', false);
        
        // Run health check
        $exitCode = Artisan::call('health:monitor', [
            '--auto-fix' => $autoFix
        ]);

        // Get updated health data
        $health = Cache::get('health_monitor_latest', [
            'status' => 'unknown',
            'timestamp' => now()->toIso8601String(),
            'issues' => 0,
            'results' => [],
            'alerts' => []
        ]);

        return response()->json([
            'success' => $exitCode === 0,
            'health' => $health,
            'output' => Artisan::output()
        ]);
    }

    /**
     * Clear all caches
     */
    public function clearCache()
    {
        Artisan::call('cache:clear');
        Artisan::call('config:clear');
        Artisan::call('view:clear');
        Artisan::call('route:clear');
        Artisan::call('optimize');

        return response()->json([
            'success' => true,
            'message' => 'All caches cleared and optimized'
        ]);
    }

    /**
     * Get system statistics
     */
    protected function getSystemStats()
    {
        $disk = disk_free_space('/');
        $total = disk_total_space('/');
        
        return [
            'disk' => [
                'used' => round((($total - $disk) / $total) * 100, 2),
                'free_gb' => round($disk / 1024 / 1024 / 1024, 2),
                'total_gb' => round($total / 1024 / 1024 / 1024, 2)
            ],
            'memory' => $this->getMemoryUsage(),
            'uptime' => $this->getUptime(),
            'php_version' => PHP_VERSION,
            'laravel_version' => app()->version()
        ];
    }

    /**
     * Get memory usage
     */
    protected function getMemoryUsage()
    {
        if (PHP_OS_FAMILY === 'Linux') {
            $free = shell_exec('free -m');
            if ($free) {
                $lines = explode("\n", $free);
                if (isset($lines[1])) {
                    $parts = preg_split('/\s+/', $lines[1]);
                    if (count($parts) >= 3) {
                        $total = (int)$parts[1];
                        $used = (int)$parts[2];
                        return [
                            'used_percent' => round(($used / $total) * 100, 2),
                            'used_mb' => $used,
                            'total_mb' => $total
                        ];
                    }
                }
            }
        }
        
        return [
            'used_percent' => 0,
            'used_mb' => 0,
            'total_mb' => 0
        ];
    }

    /**
     * Get system uptime
     */
    protected function getUptime()
    {
        if (PHP_OS_FAMILY === 'Linux') {
            $uptime = shell_exec('uptime -p');
            if ($uptime) {
                return trim($uptime);
            }
        }
        
        return 'Unknown';
    }
}