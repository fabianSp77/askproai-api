<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;

class HealthMonitor extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'health:monitor 
                            {--continuous : Run continuously}
                            {--interval=60 : Check interval in seconds}
                            {--auto-fix : Automatically fix issues}
                            {--silent : Suppress output except errors}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Monitor application health and auto-fix common issues';

    /**
     * Health check results
     */
    protected array $results = [];
    protected int $issueCount = 0;
    protected array $alerts = [];

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $continuous = $this->option('continuous');
        $interval = (int) $this->option('interval');
        $autoFix = $this->option('auto-fix');
        $quiet = $this->option('silent');

        if ($continuous) {
            $this->info('🔄 Starting continuous monitoring (Ctrl+C to stop)...');
            while (true) {
                $this->runHealthChecks($autoFix, $quiet);
                sleep($interval);
            }
        } else {
            return $this->runHealthChecks($autoFix, $quiet);
        }
    }

    /**
     * Run all health checks
     */
    protected function runHealthChecks(bool $autoFix = false, bool $quiet = false): int
    {
        $this->results = [];
        $this->issueCount = 0;
        $this->alerts = [];

        if (!$quiet) {
            $this->info(str_repeat('═', 60));
            $this->info('🚀 Health Check - ' . now()->format('Y-m-d H:i:s'));
            $this->info(str_repeat('═', 60));
        }

        // Run all checks
        $this->checkHttpEndpoints();
        $this->checkViewCache();
        $this->checkDatabase();
        $this->checkErrorLogs();
        $this->checkDiskSpace();
        $this->checkQueueStatus();
        $this->checkCacheStatus();
        $this->checkSessionStatus();

        // Display results
        if (!$quiet) {
            $this->displayResults();
        }

        // Auto-fix if enabled and issues found
        if ($autoFix && $this->issueCount > 0) {
            $this->performAutoFix();
        }

        // Save monitoring data
        $this->saveMonitoringData();

        return $this->issueCount > 0 ? 1 : 0;
    }

    /**
     * Check HTTP endpoints
     */
    protected function checkHttpEndpoints(): void
    {
        $endpoints = [
            config('app.url') . '/up' => 'Health Endpoint',
            config('app.url') . '/admin' => 'Admin Panel',
        ];

        foreach ($endpoints as $url => $name) {
            try {
                $response = Http::timeout(10)->get($url);
                $status = $response->status();
                
                if ($status === 200) {
                    $this->addResult($name, 'OK', "HTTP $status");
                } else {
                    $this->addResult($name, 'ERROR', "HTTP $status", true);
                    $this->addAlert('error', "$name returned HTTP $status", $url);
                }
            } catch (\Exception $e) {
                $this->addResult($name, 'ERROR', 'Connection failed', true);
                $this->addAlert('critical', "$name unreachable", $e->getMessage());
            }
        }
    }

    /**
     * Check view cache integrity
     */
    protected function checkViewCache(): void
    {
        $viewPath = storage_path('framework/views');
        
        // Check if directory exists
        if (!File::exists($viewPath)) {
            $this->addResult('View Cache', 'ERROR', 'Directory missing', true);
            $this->addAlert('critical', 'View cache directory missing', $viewPath);
            return;
        }

        // Check for recent filemtime errors
        $logPath = storage_path('logs/laravel.log');
        if (File::exists($logPath)) {
            $recentLog = File::get($logPath, false, null, max(0, File::size($logPath) - 50000));
            $errorCount = substr_count($recentLog, 'filemtime(): stat failed');
            
            if ($errorCount > 0) {
                $this->addResult('View Cache', 'WARNING', "$errorCount errors found", true);
                $this->addAlert('warning', 'View cache errors detected', "Count: $errorCount");
                return;
            }
        }

        // Check permissions
        $files = File::files($viewPath);
        $permissionIssues = 0;
        foreach ($files as $file) {
            if (!is_readable($file) || !is_writable($file)) {
                $permissionIssues++;
            }
        }

        if ($permissionIssues > 0) {
            $this->addResult('View Cache', 'WARNING', "$permissionIssues permission issues", true);
        } else {
            $this->addResult('View Cache', 'OK', 'Healthy');
        }
    }

    /**
     * Check database connection
     */
    protected function checkDatabase(): void
    {
        try {
            DB::select('SELECT 1');
            
            // Check response time
            $start = microtime(true);
            DB::select('SELECT 1');
            $time = round((microtime(true) - $start) * 1000, 2);
            
            if ($time > 100) {
                $this->addResult('Database', 'WARNING', "{$time}ms response", true);
                $this->addAlert('warning', 'Database slow', "Response time: {$time}ms");
            } else {
                $this->addResult('Database', 'OK', "{$time}ms response");
            }
        } catch (\Exception $e) {
            $this->addResult('Database', 'ERROR', 'Connection failed', true);
            $this->addAlert('critical', 'Database connection failed', $e->getMessage());
        }
    }

    /**
     * Check error logs for recent issues
     */
    protected function checkErrorLogs(): void
    {
        $logPath = storage_path('logs/laravel.log');
        
        if (!File::exists($logPath)) {
            $this->addResult('Error Logs', 'OK', 'No log file');
            return;
        }

        // Check last 5 minutes
        $fiveMinutesAgo = now()->subMinutes(5);
        $recentLog = File::get($logPath, false, null, max(0, File::size($logPath) - 100000));
        
        $criticalCount = 0;
        $errorCount = 0;
        
        foreach (explode("\n", $recentLog) as $line) {
            if (str_contains($line, '.CRITICAL:') || str_contains($line, '.EMERGENCY:')) {
                if (str_contains($line, $fiveMinutesAgo->format('Y-m-d H:'))) {
                    $criticalCount++;
                }
            } elseif (str_contains($line, '.ERROR:')) {
                if (str_contains($line, $fiveMinutesAgo->format('Y-m-d H:'))) {
                    $errorCount++;
                }
            }
        }

        if ($criticalCount > 0) {
            $this->addResult('Error Logs', 'ERROR', "$criticalCount critical errors", true);
            $this->addAlert('critical', 'Critical errors in logs', "Count: $criticalCount");
        } elseif ($errorCount > 0) {
            $this->addResult('Error Logs', 'WARNING', "$errorCount errors", true);
            $this->addAlert('warning', 'Errors in logs', "Count: $errorCount");
        } else {
            $this->addResult('Error Logs', 'OK', 'No recent errors');
        }
    }

    /**
     * Check disk space
     */
    protected function checkDiskSpace(): void
    {
        $disk = disk_free_space('/');
        $total = disk_total_space('/');
        $used = $total - $disk;
        $percentage = round(($used / $total) * 100, 2);

        if ($percentage > 90) {
            $this->addResult('Disk Space', 'ERROR', "{$percentage}% used", true);
            $this->addAlert('critical', 'Disk space critical', "{$percentage}% used");
        } elseif ($percentage > 80) {
            $this->addResult('Disk Space', 'WARNING', "{$percentage}% used", true);
            $this->addAlert('warning', 'Disk space warning', "{$percentage}% used");
        } else {
            $this->addResult('Disk Space', 'OK', "{$percentage}% used");
        }
    }

    /**
     * Check queue status
     */
    protected function checkQueueStatus(): void
    {
        try {
            $jobs = DB::table('jobs')->count();
            $failed = DB::table('failed_jobs')->count();
            
            if ($failed > 10) {
                $this->addResult('Queue', 'ERROR', "$failed failed jobs", true);
                $this->addAlert('error', 'High failed job count', "Failed: $failed");
            } elseif ($jobs > 100) {
                $this->addResult('Queue', 'WARNING', "$jobs pending jobs", true);
                $this->addAlert('warning', 'High pending job count', "Pending: $jobs");
            } else {
                $this->addResult('Queue', 'OK', "$jobs pending, $failed failed");
            }
        } catch (\Exception $e) {
            // Tables might not exist
            $this->addResult('Queue', 'SKIP', 'Not configured');
        }
    }

    /**
     * Check cache status
     */
    protected function checkCacheStatus(): void
    {
        try {
            // Test cache write/read
            $key = 'health_check_' . time();
            Cache::put($key, true, 10);
            $value = Cache::get($key);
            Cache::forget($key);
            
            if ($value === true) {
                $this->addResult('Cache', 'OK', 'Read/Write OK');
            } else {
                $this->addResult('Cache', 'ERROR', 'Read/Write failed', true);
            }
        } catch (\Exception $e) {
            $this->addResult('Cache', 'ERROR', 'Not working', true);
            $this->addAlert('error', 'Cache system failure', $e->getMessage());
        }
    }

    /**
     * Check session status
     */
    protected function checkSessionStatus(): void
    {
        try {
            $sessionPath = storage_path('framework/sessions');
            
            if (!File::exists($sessionPath)) {
                $this->addResult('Sessions', 'ERROR', 'Directory missing', true);
                return;
            }
            
            $sessionFiles = File::files($sessionPath);
            $activeCount = count($sessionFiles);
            
            // Clean old sessions (older than 24 hours)
            $oldCount = 0;
            foreach ($sessionFiles as $file) {
                if (File::lastModified($file) < now()->subDay()->timestamp) {
                    $oldCount++;
                }
            }
            
            if ($oldCount > 100) {
                $this->addResult('Sessions', 'WARNING', "$oldCount old sessions", true);
            } else {
                $this->addResult('Sessions', 'OK', "$activeCount active");
            }
        } catch (\Exception $e) {
            $this->addResult('Sessions', 'ERROR', 'Check failed', true);
        }
    }

    /**
     * Perform auto-fix for common issues
     */
    protected function performAutoFix(): void
    {
        $this->warn('🔧 Attempting auto-fix...');
        
        // Clear all caches
        $this->call('cache:clear', [], $this->output);
        $this->call('config:clear', [], $this->output);
        $this->call('view:clear', [], $this->output);
        $this->call('route:clear', [], $this->output);
        
        // Fix permissions
        $viewPath = storage_path('framework/views');
        if (File::exists($viewPath)) {
            exec("chmod -R 775 $viewPath");
            exec("chown -R www-data:www-data $viewPath");
        }
        
        // Clear OPcache
        if (function_exists('opcache_reset')) {
            opcache_reset();
        }
        
        // Optimize
        $this->call('optimize', [], $this->output);
        
        $this->info('✅ Auto-fix completed');
        
        // Log the fix
        Log::info('Health monitor auto-fix applied', [
            'issues' => $this->issueCount,
            'alerts' => $this->alerts
        ]);
    }

    /**
     * Add a result
     */
    protected function addResult(string $component, string $status, string $message, bool $isIssue = false): void
    {
        $this->results[] = [
            'component' => $component,
            'status' => $status,
            'message' => $message,
            'timestamp' => now()->toIso8601String()
        ];
        
        if ($isIssue) {
            $this->issueCount++;
        }
    }

    /**
     * Add an alert
     */
    protected function addAlert(string $severity, string $message, string $details): void
    {
        $this->alerts[] = [
            'severity' => $severity,
            'message' => $message,
            'details' => $details,
            'timestamp' => now()->toIso8601String()
        ];
    }

    /**
     * Display results
     */
    protected function displayResults(): void
    {
        $this->info(str_repeat('─', 60));
        
        foreach ($this->results as $result) {
            $icon = match($result['status']) {
                'OK' => '✅',
                'WARNING' => '⚠️ ',
                'ERROR' => '❌',
                'SKIP' => 'ℹ️ ',
                default => '❓'
            };
            
            $color = match($result['status']) {
                'OK' => 'info',
                'WARNING' => 'warn',
                'ERROR' => 'error',
                default => 'comment'
            };
            
            $this->{$color}(sprintf(
                "%s %-20s %s",
                $icon,
                $result['component'] . ':',
                $result['message']
            ));
        }
        
        $this->info(str_repeat('─', 60));
        
        if ($this->issueCount === 0) {
            $this->info('✅ ALL SYSTEMS OPERATIONAL');
        } elseif ($this->issueCount <= 2) {
            $this->warn("⚠️  MINOR ISSUES DETECTED ({$this->issueCount})");
        } else {
            $this->error("❌ CRITICAL ISSUES DETECTED ({$this->issueCount})");
        }
        
        $this->info(str_repeat('═', 60));
    }

    /**
     * Save monitoring data for dashboard
     */
    protected function saveMonitoringData(): void
    {
        $data = [
            'timestamp' => now()->toIso8601String(),
            'issues' => $this->issueCount,
            'results' => $this->results,
            'alerts' => $this->alerts,
            'status' => $this->issueCount === 0 ? 'healthy' : ($this->issueCount <= 2 ? 'warning' : 'critical')
        ];
        
        // Save to cache for quick access
        Cache::put('health_monitor_latest', $data, 300);
        
        // Save to file for history
        $file = storage_path('logs/health-monitor.json');
        $history = [];
        
        if (File::exists($file)) {
            $history = json_decode(File::get($file), true) ?: [];
        }
        
        // Keep only last 100 entries
        array_unshift($history, $data);
        $history = array_slice($history, 0, 100);
        
        File::put($file, json_encode($history, JSON_PRETTY_PRINT));
    }
}