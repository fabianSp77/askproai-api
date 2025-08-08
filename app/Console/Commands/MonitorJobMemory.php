<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class MonitorJobMemory extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'jobs:monitor-memory 
                            {--interval=60 : Monitoring interval in seconds}
                            {--threshold=80 : Memory usage threshold percentage for alerts}
                            {--duration=3600 : Total monitoring duration in seconds}
                            {--output-file= : File to write memory statistics}';

    /**
     * The console command description.
     */
    protected $description = 'Monitor memory usage of running jobs';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $interval = (int) $this->option('interval');
        $threshold = (int) $this->option('threshold');
        $duration = (int) $this->option('duration');
        $outputFile = $this->option('output-file');
        
        $this->info("Starting job memory monitoring...");
        $this->info("Interval: {$interval}s, Threshold: {$threshold}%, Duration: {$duration}s");
        
        $startTime = time();
        $endTime = $startTime + $duration;
        $memoryStats = [];
        
        while (time() < $endTime) {
            $stats = $this->collectMemoryStats();
            $memoryStats[] = $stats;
            
            // Display current stats
            $this->displayStats($stats, $threshold);
            
            // Check for high memory usage
            $this->checkForAlerts($stats, $threshold);
            
            // Wait for next interval
            if (time() < $endTime) {
                sleep($interval);
            }
        }
        
        // Generate summary report
        $this->generateSummaryReport($memoryStats, $outputFile);
        
        $this->info("Memory monitoring completed.");
    }
    
    /**
     * Collect current memory statistics
     */
    protected function collectMemoryStats(): array
    {
        $stats = [
            'timestamp' => Carbon::now()->toISOString(),
            'system_memory' => $this->getSystemMemoryStats(),
            'php_memory' => $this->getPHPMemoryStats(),
            'jobs' => $this->getJobStats(),
        ];
        
        return $stats;
    }
    
    /**
     * Get system memory statistics
     */
    protected function getSystemMemoryStats(): array
    {
        // Linux specific - adjust for other systems as needed
        $meminfo = [];
        if (file_exists('/proc/meminfo')) {
            $lines = file('/proc/meminfo');
            foreach ($lines as $line) {
                if (preg_match('/^(\w+):\s*(\d+)\s*kB/', $line, $matches)) {
                    $meminfo[strtolower($matches[1])] = (int) $matches[2] * 1024; // Convert to bytes
                }
            }
        }
        
        $total = $meminfo['memtotal'] ?? 0;
        $free = $meminfo['memfree'] ?? 0;
        $available = $meminfo['memavailable'] ?? $free;
        $used = $total - $available;
        
        return [
            'total_bytes' => $total,
            'used_bytes' => $used,
            'free_bytes' => $free,
            'available_bytes' => $available,
            'usage_percentage' => $total > 0 ? ($used / $total) * 100 : 0,
            'total_mb' => $this->bytesToMb($total),
            'used_mb' => $this->bytesToMb($used),
            'available_mb' => $this->bytesToMb($available),
        ];
    }
    
    /**
     * Get PHP memory statistics
     */
    protected function getPHPMemoryStats(): array
    {
        $current = memory_get_usage(true);
        $peak = memory_get_peak_usage(true);
        $limit = $this->parseMemoryLimit(ini_get('memory_limit'));
        
        return [
            'current_bytes' => $current,
            'peak_bytes' => $peak,
            'limit_bytes' => $limit,
            'usage_percentage' => $limit > 0 ? ($current / $limit) * 100 : 0,
            'current_mb' => $this->bytesToMb($current),
            'peak_mb' => $this->bytesToMb($peak),
            'limit_mb' => $this->bytesToMb($limit),
        ];
    }
    
    /**
     * Get job queue statistics
     */
    protected function getJobStats(): array
    {
        try {
            // Get job counts from different queues
            $stats = [
                'pending_jobs' => 0,
                'failed_jobs' => 0,
                'processing_jobs' => 0,
                'queues' => []
            ];
            
            // Count pending jobs by queue
            $pendingJobs = DB::table('jobs')
                ->select('queue', DB::raw('COUNT(*) as count'))
                ->groupBy('queue')
                ->get();
            
            foreach ($pendingJobs as $job) {
                $stats['queues'][$job->queue]['pending'] = $job->count;
                $stats['pending_jobs'] += $job->count;
            }
            
            // Count failed jobs
            $stats['failed_jobs'] = DB::table('failed_jobs')->count();
            
            // Try to get processing jobs (if using Horizon)
            if (class_exists(\Laravel\Horizon\Contracts\MasterSupervisorRepository::class)) {
                try {
                    $stats['processing_jobs'] = collect(app(\Laravel\Horizon\Contracts\WorkloadRepository::class)->get())
                        ->sum('processes');
                } catch (\Exception $e) {
                    // Horizon not available or not running
                    $stats['processing_jobs'] = 0;
                }
            }
            
            return $stats;
        } catch (\Exception $e) {
            Log::warning('Failed to collect job stats', ['error' => $e->getMessage()]);
            return ['error' => $e->getMessage()];
        }
    }
    
    /**
     * Display current statistics
     */
    protected function displayStats(array $stats, int $threshold): void
    {
        $this->line("--- Memory Status at {$stats['timestamp']} ---");
        
        // System memory
        $systemMem = $stats['system_memory'];
        $systemUsage = $systemMem['usage_percentage'];
        $systemColor = $systemUsage > $threshold ? 'red' : ($systemUsage > $threshold * 0.7 ? 'yellow' : 'green');
        
        $this->line(sprintf(
            "System Memory: <fg=%s>%s/%s MB (%.1f%%)</fg>",
            $systemColor,
            number_format($systemMem['used_mb'], 1),
            number_format($systemMem['total_mb'], 1),
            $systemUsage
        ));
        
        // PHP memory
        $phpMem = $stats['php_memory'];
        $phpUsage = $phpMem['usage_percentage'];
        $phpColor = $phpUsage > $threshold ? 'red' : ($phpUsage > $threshold * 0.7 ? 'yellow' : 'green');
        
        $this->line(sprintf(
            "PHP Memory: <fg=%s>%s/%s MB (%.1f%%) | Peak: %s MB</fg>",
            $phpColor,
            number_format($phpMem['current_mb'], 1),
            number_format($phpMem['limit_mb'], 1),
            $phpUsage,
            number_format($phpMem['peak_mb'], 1)
        ));
        
        // Job stats
        $jobs = $stats['jobs'];
        if (!isset($jobs['error'])) {
            $this->line(sprintf(
                "Jobs: Pending: %d | Processing: %d | Failed: %d",
                $jobs['pending_jobs'],
                $jobs['processing_jobs'],
                $jobs['failed_jobs']
            ));
        }
        
        $this->line("");
    }
    
    /**
     * Check for memory alerts
     */
    protected function checkForAlerts(array $stats, int $threshold): void
    {
        $alerts = [];
        
        // Check system memory
        if ($stats['system_memory']['usage_percentage'] > $threshold) {
            $alerts[] = sprintf(
                "High system memory usage: %.1f%% (threshold: %d%%)",
                $stats['system_memory']['usage_percentage'],
                $threshold
            );
        }
        
        // Check PHP memory
        if ($stats['php_memory']['usage_percentage'] > $threshold) {
            $alerts[] = sprintf(
                "High PHP memory usage: %.1f%% (threshold: %d%%)",
                $stats['php_memory']['usage_percentage'],
                $threshold
            );
        }
        
        // Check for too many pending jobs
        if (isset($stats['jobs']['pending_jobs']) && $stats['jobs']['pending_jobs'] > 100) {
            $alerts[] = sprintf(
                "High number of pending jobs: %d",
                $stats['jobs']['pending_jobs']
            );
        }
        
        // Log alerts
        foreach ($alerts as $alert) {
            Log::warning('Memory monitoring alert', [
                'alert' => $alert,
                'timestamp' => $stats['timestamp']
            ]);
            $this->warn("ALERT: " . $alert);
        }
    }
    
    /**
     * Generate summary report
     */
    protected function generateSummaryReport(array $memoryStats, ?string $outputFile): void
    {
        if (empty($memoryStats)) {
            return;
        }
        
        $summary = [
            'monitoring_period' => [
                'start' => $memoryStats[0]['timestamp'],
                'end' => end($memoryStats)['timestamp'],
                'duration_minutes' => count($memoryStats) * ($this->option('interval') / 60),
                'samples' => count($memoryStats)
            ],
            'system_memory' => $this->calculateMemorySummary($memoryStats, 'system_memory'),
            'php_memory' => $this->calculateMemorySummary($memoryStats, 'php_memory'),
            'alerts_triggered' => $this->countAlerts($memoryStats),
        ];
        
        $reportContent = "Job Memory Monitoring Report\n";
        $reportContent .= "==============================\n\n";
        $reportContent .= "Monitoring Period: {$summary['monitoring_period']['start']} to {$summary['monitoring_period']['end']}\n";
        $reportContent .= "Duration: {$summary['monitoring_period']['duration_minutes']} minutes ({$summary['monitoring_period']['samples']} samples)\n\n";
        
        $reportContent .= "System Memory Summary:\n";
        $reportContent .= sprintf("- Average Usage: %.1f%%\n", $summary['system_memory']['avg_usage']);
        $reportContent .= sprintf("- Peak Usage: %.1f%%\n", $summary['system_memory']['peak_usage']);
        $reportContent .= sprintf("- Min Usage: %.1f%%\n\n", $summary['system_memory']['min_usage']);
        
        $reportContent .= "PHP Memory Summary:\n";
        $reportContent .= sprintf("- Average Usage: %.1f%%\n", $summary['php_memory']['avg_usage']);
        $reportContent .= sprintf("- Peak Usage: %.1f%%\n", $summary['php_memory']['peak_usage']);
        $reportContent .= sprintf("- Min Usage: %.1f%%\n\n", $summary['php_memory']['min_usage']);
        
        $reportContent .= "Alerts: {$summary['alerts_triggered']} triggered\n";
        
        // Output to file if specified
        if ($outputFile) {
            file_put_contents($outputFile, $reportContent);
            file_put_contents($outputFile . '.json', json_encode($summary, JSON_PRETTY_PRINT));
            $this->info("Detailed report saved to: {$outputFile}");
            $this->info("JSON data saved to: {$outputFile}.json");
        }
        
        // Display summary
        $this->info("\n" . $reportContent);
    }
    
    /**
     * Calculate memory usage summary
     */
    protected function calculateMemorySummary(array $stats, string $type): array
    {
        $usages = array_column(array_column($stats, $type), 'usage_percentage');
        
        return [
            'avg_usage' => array_sum($usages) / count($usages),
            'peak_usage' => max($usages),
            'min_usage' => min($usages),
        ];
    }
    
    /**
     * Count alerts in monitoring period
     */
    protected function countAlerts(array $stats): int
    {
        $threshold = (int) $this->option('threshold');
        $alerts = 0;
        
        foreach ($stats as $stat) {
            if ($stat['system_memory']['usage_percentage'] > $threshold ||
                $stat['php_memory']['usage_percentage'] > $threshold) {
                $alerts++;
            }
        }
        
        return $alerts;
    }
    
    /**
     * Convert bytes to megabytes
     */
    protected function bytesToMb(int $bytes): float
    {
        return $bytes / (1024 * 1024);
    }
    
    /**
     * Parse memory limit string to bytes
     */
    protected function parseMemoryLimit(string $limit): int
    {
        $limit = trim($limit);
        $last = strtolower($limit[strlen($limit) - 1]);
        $value = (int) $limit;
        
        switch ($last) {
            case 'g':
                $value *= 1024 * 1024 * 1024;
                break;
            case 'm':
                $value *= 1024 * 1024;
                break;
            case 'k':
                $value *= 1024;
                break;
        }
        
        return $value;
    }
}