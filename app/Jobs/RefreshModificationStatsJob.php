<?php

namespace App\Jobs;

use App\Models\AppointmentModification;
use App\Models\AppointmentModificationStat;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RefreshModificationStatsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public $tries = 3;

    /**
     * The number of seconds the job can run before timing out.
     */
    public $timeout = 120;

    /**
     * The number of seconds to wait before retrying the job.
     */
    public $backoff = [30, 120];

    /**
     * Execute the job.
     *
     * This job materializes modification statistics for faster policy quota checks.
     * Runs hourly to update rolling 30-day counts for cancellations and reschedules.
     */
    public function handle(): void
    {
        $startTime = microtime(true);
        Log::info('🔄 Starting modification stats refresh');

        try {
            $now = Carbon::now();
            $periodStart = $now->copy()->subDays(30);
            $periodEnd = $now->copy();

            // Get all customers with modifications in the last 30 days
            $customersWithModifications = AppointmentModification::where('created_at', '>=', $periodStart)
                ->select('customer_id')
                ->distinct()
                ->pluck('customer_id');

            $statsUpdated = 0;
            $statsCreated = 0;

            foreach ($customersWithModifications as $customerId) {
                // Count cancellations in rolling 30-day window
                $cancellationCount = AppointmentModification::where('customer_id', $customerId)
                    ->where('modification_type', 'cancel')
                    ->where('created_at', '>=', $periodStart)
                    ->count();

                // Update or create cancellation stat
                AppointmentModificationStat::updateOrCreate(
                    [
                        'customer_id' => $customerId,
                        'stat_type' => 'cancellation_count',
                        'period_start' => $periodStart->toDateString(),
                        'period_end' => $periodEnd->toDateString(),
                    ],
                    [
                        'count' => $cancellationCount,
                        'updated_at' => $now,
                    ]
                );

                // Count reschedules in rolling 30-day window
                $rescheduleCount = AppointmentModification::where('customer_id', $customerId)
                    ->where('modification_type', 'reschedule')
                    ->where('created_at', '>=', $periodStart)
                    ->count();

                // Update or create reschedule stat
                AppointmentModificationStat::updateOrCreate(
                    [
                        'customer_id' => $customerId,
                        'stat_type' => 'reschedule_count',
                        'period_start' => $periodStart->toDateString(),
                        'period_end' => $periodEnd->toDateString(),
                    ],
                    [
                        'count' => $rescheduleCount,
                        'updated_at' => $now,
                    ]
                );

                $statsUpdated += 2;
            }

            // Clean up old stats (older than 60 days)
            $deleted = AppointmentModificationStat::where('period_end', '<', $now->copy()->subDays(60)->toDateString())
                ->delete();

            $duration = round((microtime(true) - $startTime) * 1000, 2);

            Log::info('✅ Modification stats refresh complete', [
                'customers_processed' => $customersWithModifications->count(),
                'stats_updated' => $statsUpdated,
                'old_stats_deleted' => $deleted,
                'duration_ms' => $duration
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Failed to refresh modification stats', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            throw $e; // Re-throw for queue retry logic
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('[RefreshModificationStatsJob] permanently failed', [
            'exception' => $exception->getMessage(),
        ]);
    }
}
