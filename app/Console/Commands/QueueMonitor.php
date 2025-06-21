<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class QueueMonitor extends Command
{
    protected $signature = 'queue:monitor';
    protected $description = 'Monitor queue health and alert on issues';

    public function handle()
    {
        $issues = [];
        
        // Check failed jobs
        $failedJobs = DB::table('failed_jobs')->count();
        if ($failedJobs > 10) {
            $issues[] = "High number of failed jobs: $failedJobs";
        }
        
        // Check queue size
        $queueSize = DB::table('jobs')->count();
        if ($queueSize > 1000) {
            $issues[] = "Large queue backlog: $queueSize jobs";
        }
        
        // Check old jobs
        $oldJobs = DB::table('jobs')
            ->where('created_at', '<', now()->subHours(2))
            ->count();
        if ($oldJobs > 0) {
            $issues[] = "Stuck jobs detected: $oldJobs jobs older than 2 hours";
        }
        
        if (empty($issues)) {
            $this->info('Queue health check passed');
            Log::info('Queue monitor: All checks passed');
        } else {
            $this->error('Queue issues detected:');
            foreach ($issues as $issue) {
                $this->error("- $issue");
                Log::error("Queue monitor: $issue");
            }
            
            // Send alerts (implement your alerting)
        }
        
        return empty($issues) ? 0 : 1;
    }
}
