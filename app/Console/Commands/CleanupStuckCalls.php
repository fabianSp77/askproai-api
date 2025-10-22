<?php

namespace App\Console\Commands;

use App\Models\Call;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

/**
 * Cleanup Stuck Calls Command
 *
 * Automatically marks calls as "completed" if they've been stuck in
 * "ongoing/in_progress/active" status for more than a threshold time.
 *
 * Problem: If call_ended webhook fails, calls remain "ongoing" forever
 * Solution: Auto-complete calls older than X hours
 *
 * Usage:
 *   php artisan calls:cleanup-stuck              # Default 2 hours threshold
 *   php artisan calls:cleanup-stuck --hours=4    # Custom threshold
 *   php artisan calls:cleanup-stuck --dry-run    # Preview without changes
 */
class CleanupStuckCalls extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'calls:cleanup-stuck
                          {--hours=2 : Number of hours after which a call is considered stuck}
                          {--dry-run : Show what would be cleaned up without making changes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean up calls stuck in ongoing/active status due to missed webhooks';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $hoursThreshold = (int) $this->option('hours');
        $dryRun = $this->option('dry-run');

        $this->info("🔍 Searching for stuck calls (older than {$hoursThreshold} hours)...");

        // Find stuck calls using the model scope
        // This uses created_at instead of updated_at to avoid false positives
        $stuckCalls = Call::stuck($hoursThreshold)->get();

        if ($stuckCalls->isEmpty()) {
            $this->info('✅ No stuck calls found. System is healthy!');
            return Command::SUCCESS;
        }

        $this->warn("⚠️  Found {$stuckCalls->count()} stuck call(s):");

        // Display table of stuck calls
        $this->table(
            ['ID', 'Retell Call ID', 'Status', 'Created', 'Hours Stuck', 'From Number'],
            $stuckCalls->map(function ($call) {
                return [
                    $call->id,
                    $call->retell_call_id ?? 'N/A',
                    $call->status,
                    $call->created_at->format('Y-m-d H:i:s'),
                    $call->created_at->diffInHours(now()),
                    $call->from_number ?? 'Unknown'
                ];
            })
        );

        if ($dryRun) {
            $this->comment('🔬 DRY RUN MODE - No changes will be made');
            return Command::SUCCESS;
        }

        // Confirm before proceeding (skip in production cron)
        if ($this->input->isInteractive()) {
            if (!$this->confirm('Do you want to mark these calls as completed?', true)) {
                $this->comment('Aborted by user.');
                return Command::SUCCESS;
            }
        }

        $cleaned = 0;
        $errors = 0;

        foreach ($stuckCalls as $call) {
            try {
                $oldStatus = $call->status;

                // Calculate duration from created_at if timestamps not available
                // For stuck calls without proper end timestamp, we set duration to NULL
                // to avoid negative or incorrect values
                $duration = null;
                if ($call->start_timestamp && $call->end_timestamp) {
                    // If we have both timestamps, use them
                    $duration = $call->end_timestamp->diffInSeconds($call->start_timestamp);
                } elseif ($call->start_timestamp) {
                    // If only start, calculate from start to now
                    $duration = $call->start_timestamp->diffInSeconds(now());
                } elseif ($call->created_at) {
                    // Fallback: use created_at as start time
                    $duration = $call->created_at->diffInSeconds(now());
                }

                // Sanity check: duration should be reasonable (< 24 hours = 86400 seconds)
                // If stuck call is old, better to set duration to NULL than wrong value
                if ($duration && $duration > 86400) {
                    $duration = null;
                }

                // Mark as completed
                $call->update([
                    'status' => 'completed',
                    'call_status' => 'ended',
                    'end_timestamp' => $call->end_timestamp ?? now(),
                    'duration_sec' => $duration,
                    'disconnection_reason' => $call->disconnection_reason ?? 'system_timeout',
                ]);

                Log::info('🧹 Cleaned up stuck call', [
                    'call_id' => $call->id,
                    'retell_call_id' => $call->retell_call_id,
                    'old_status' => $oldStatus,
                    'new_status' => 'completed',
                    'hours_stuck' => $call->created_at->diffInHours(now()),
                    'calculated_duration_sec' => $duration,
                    'source' => 'cleanup_command'
                ]);

                $cleaned++;

            } catch (\Exception $e) {
                $this->error("❌ Failed to cleanup call {$call->id}: {$e->getMessage()}");

                Log::error('Failed to cleanup stuck call', [
                    'call_id' => $call->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);

                $errors++;
            }
        }

        // Summary
        $this->newLine();
        $this->info("✅ Successfully cleaned up {$cleaned} call(s)");

        if ($errors > 0) {
            $this->error("❌ {$errors} error(s) occurred");
        }

        return Command::SUCCESS;
    }
}