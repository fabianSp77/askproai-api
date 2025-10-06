<?php

namespace App\Console\Commands;

use App\Models\Call;
use Illuminate\Console\Command;
use Carbon\Carbon;

class CleanupStuckCalls extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'calls:cleanup-stuck';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean up stuck calls that are still marked as ongoing after 30 minutes';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $threshold = Carbon::now()->subMinutes(30);

        // Find stuck calls
        $stuckCalls = Call::whereIn('status', ['ongoing', 'in-progress', 'active'])
            ->where('updated_at', '<', $threshold)
            ->get();

        if ($stuckCalls->count() === 0) {
            $this->info('No stuck calls found.');
            return Command::SUCCESS;
        }

        $this->info("Found {$stuckCalls->count()} stuck calls. Cleaning up...");

        foreach ($stuckCalls as $call) {
            // Calculate duration if not set
            $duration = 0;
            if ($call->start_timestamp && $call->end_timestamp) {
                $duration = ($call->end_timestamp - $call->start_timestamp) / 1000; // Convert ms to seconds
            } elseif ($call->created_at) {
                $duration = $call->created_at->diffInSeconds($threshold);
            }

            $call->update([
                'status' => 'completed',
                'duration' => $duration,
                'duration_sec' => $duration,
                'disconnection_reason' => 'system_timeout',
                'notes' => ($call->notes ?? '') . "\n[System] Auto-completed after 30 minutes of inactivity."
            ]);

            $this->line("  - Cleaned call ID: {$call->id} (Duration: {$duration}s)");
        }

        $this->info('Cleanup completed successfully.');
        return Command::SUCCESS;
    }
}