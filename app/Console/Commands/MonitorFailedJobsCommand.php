<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

/**
 * Monitor Failed Jobs Command
 *
 * Checks for failed queue jobs and logs warnings if failures are detected.
 * Can be scheduled to run periodically for proactive monitoring.
 *
 * Usage:
 *   php artisan queue:monitor-failed
 *   php artisan queue:monitor-failed --hours=24  # Check last 24 hours
 */
class MonitorFailedJobsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'queue:monitor-failed
                            {--hours=1 : Number of hours to look back}
                            {--alert-threshold=5 : Number of failures to trigger alert}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Monitor failed queue jobs and log warnings';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $hours = (int) $this->option('hours');
        $threshold = (int) $this->option('alert-threshold');

        $cutoffTime = Carbon::now()->subHours($hours);

        // Count failed jobs in the time window
        $failedCount = DB::table('failed_jobs')
            ->where('failed_at', '>=', $cutoffTime)
            ->count();

        if ($failedCount === 0) {
            $this->info("✅ No failed jobs in the last {$hours} hour(s)");
            Log::info('[Queue Monitor] No failed jobs detected', [
                'hours' => $hours,
            ]);
            return self::SUCCESS;
        }

        // Get details about failed jobs
        $failedJobs = DB::table('failed_jobs')
            ->where('failed_at', '>=', $cutoffTime)
            ->orderBy('failed_at', 'desc')
            ->limit(10)
            ->get();

        // Group by queue
        $byQueue = $failedJobs->groupBy('queue')->map(fn($jobs) => $jobs->count());

        // Group by exception type (extract from payload)
        $exceptionTypes = [];
        foreach ($failedJobs as $job) {
            $payload = json_decode($job->payload, true);
            $displayName = $payload['displayName'] ?? 'Unknown';
            $exceptionTypes[$displayName] = ($exceptionTypes[$displayName] ?? 0) + 1;
        }

        // Determine severity
        $severity = $failedCount >= $threshold ? 'error' : 'warning';
        $emoji = $failedCount >= $threshold ? '🚨' : '⚠️';

        // Log the issue
        $logData = [
            'failed_count' => $failedCount,
            'hours' => $hours,
            'threshold' => $threshold,
            'by_queue' => $byQueue->toArray(),
            'by_job_type' => $exceptionTypes,
            'recent_failures' => $failedJobs->take(3)->map(function ($job) {
                return [
                    'uuid' => $job->uuid,
                    'queue' => $job->queue,
                    'failed_at' => $job->failed_at,
                    'exception' => substr($job->exception, 0, 200) . '...',
                ];
            })->toArray(),
        ];

        if ($severity === 'error') {
            Log::error("{$emoji} [Queue Monitor] High number of failed jobs detected", $logData);
            $this->error("{$emoji} Found {$failedCount} failed jobs in the last {$hours} hour(s) (threshold: {$threshold})");
        } else {
            Log::warning("{$emoji} [Queue Monitor] Failed jobs detected", $logData);
            $this->warn("{$emoji} Found {$failedCount} failed jobs in the last {$hours} hour(s)");
        }

        // Display summary
        $this->newLine();
        $this->line('<fg=cyan>Failed Jobs by Queue:</>');
        foreach ($byQueue as $queue => $count) {
            $this->line("  {$queue}: {$count}");
        }

        $this->newLine();
        $this->line('<fg=cyan>Failed Jobs by Type:</>');
        foreach ($exceptionTypes as $jobType => $count) {
            $this->line("  {$jobType}: {$count}");
        }

        $this->newLine();
        $this->line('<fg=yellow>Run `php artisan queue:failed` to see all failed jobs</>');
        $this->line('<fg=yellow>Run `php artisan queue:retry {uuid}` to retry a specific job</>');

        return $failedCount >= $threshold ? self::FAILURE : self::SUCCESS;
    }
}
