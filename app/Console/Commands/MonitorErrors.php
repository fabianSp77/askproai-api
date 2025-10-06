<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class MonitorErrors extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'monitor:errors 
                            {--minutes=5 : Time window to check}
                            {--threshold=10 : Error count threshold for alerts}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Monitor application errors and detect 500 error patterns';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $minutes = $this->option('minutes');
        $threshold = $this->option('threshold');
        $since = Carbon::now()->subMinutes($minutes);
        
        $errorPatterns = [];
        $criticalErrors = [];
        $errorsByType = [];
        
        // 1. Analyze Laravel log file
        $logFile = storage_path('logs/laravel.log');
        if (file_exists($logFile)) {
            // Get recent log entries
            $timeFilter = $since->format('Y-m-d H:i');
            $command = "grep -E '\\[{$timeFilter}' $logFile 2>/dev/null | head -1000";
            $recentLogs = shell_exec($command);
            
            if ($recentLogs) {
                // Parse for 500 errors and exceptions
                preg_match_all('/\[([\d-]+ [\d:]+)\].*?ERROR:(.+?)(?=\[\d{4}-|$)/s', $recentLogs, $matches);
                
                foreach ($matches[2] as $error) {
                    // Extract error type
                    if (preg_match('/(\w+Exception|\w+Error)/', $error, $typeMatch)) {
                        $errorType = $typeMatch[1];
                        $errorsByType[$errorType] = ($errorsByType[$errorType] ?? 0) + 1;
                    }
                    
                    // Check for critical patterns
                    if (strpos($error, 'SQLSTATE') !== false) {
                        $criticalErrors[] = 'Database connection errors detected';
                    }
                    if (strpos($error, '500 Internal Server Error') !== false) {
                        $criticalErrors[] = '500 errors detected in logs';
                    }
                    if (strpos($error, 'Class.*not found') !== false) {
                        $criticalErrors[] = 'Missing class errors detected';
                    }
                    if (strpos($error, 'Permission denied') !== false) {
                        $criticalErrors[] = 'Permission errors detected';
                    }
                }
            }
        }
        
        // 2. Check failed jobs for patterns
        try {
            $failedJobs = DB::table('failed_jobs')
                ->where('failed_at', '>=', $since)
                ->get();
            
            foreach ($failedJobs as $job) {
                $exception = json_decode($job->exception, true);
                if ($exception) {
                    $errorPatterns[] = substr($exception['message'] ?? 'Unknown error', 0, 100);
                }
            }
            
            if ($failedJobs->count() > $threshold) {
                $criticalErrors[] = "High failed job rate: {$failedJobs->count()} in {$minutes} minutes";
            }
        } catch (\Exception $e) {
            $this->warn('Could not check failed jobs');
        }
        
        // 3. Check error tracking cache
        $cachedErrors = Cache::get('application_errors', []);
        $recentCachedErrors = array_filter($cachedErrors, function($error) use ($since) {
            return isset($error['timestamp']) && 
                   Carbon::parse($error['timestamp'])->isAfter($since);
        });
        
        // 4. Generate report
        $totalErrors = count($errorPatterns) + array_sum($errorsByType);
        
        $this->info("=== Error Monitoring Report ===");
        $this->info("Time window: Last {$minutes} minutes");
        $this->info("Total errors found: {$totalErrors}");
        
        if (!empty($errorsByType)) {
            $this->info("\nError types:");
            foreach ($errorsByType as $type => $count) {
                $this->line("  - {$type}: {$count}");
            }
        }
        
        if (!empty($criticalErrors)) {
            $this->error("\n⚠️  Critical Issues:");
            foreach (array_unique($criticalErrors) as $critical) {
                $this->error("  • {$critical}");
            }
        }
        
        // 5. Store monitoring data
        $monitoringData = [
            'timestamp' => Carbon::now()->toIso8601String(),
            'window_minutes' => $minutes,
            'total_errors' => $totalErrors,
            'error_types' => $errorsByType,
            'critical_issues' => array_unique($criticalErrors),
            'threshold_exceeded' => $totalErrors > $threshold
        ];
        
        Cache::put('error_monitoring', $monitoringData, 3600);
        
        // 6. Alert if threshold exceeded
        if ($totalErrors > $threshold) {
            $this->error("\n🔴 ALERT: Error threshold exceeded! ({$totalErrors} > {$threshold})");
            
            Log::critical('Error monitoring alert', $monitoringData);
            
            // Store alert for dashboard/external monitoring
            Cache::put('error_alert_active', true, 300);
            
            return Command::FAILURE;
        }
        
        Cache::forget('error_alert_active');
        $this->info("\n✅ Error rate within acceptable limits");
        
        return Command::SUCCESS;
    }
}