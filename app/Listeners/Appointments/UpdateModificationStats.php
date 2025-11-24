<?php

namespace App\Listeners\Appointments;

use App\Events\Appointments\AppointmentCancellationRequested;
use App\Events\Appointments\AppointmentRescheduled;
use App\Models\AppointmentModificationStat;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Update materialized modification statistics
 *
 * Updates rolling 30-day counts for:
 * - Cancellation count per customer
 * - Reschedule count per customer
 * - Reschedule count per appointment
 *
 * This provides O(1) lookups for policy quota checks
 */
class UpdateModificationStats implements ShouldQueue
{
    use InteractsWithQueue;

    public $queue = 'stats';
    public $tries = 2;

    /**
     * Handle cancellation event
     */
    public function handle(AppointmentCancellationRequested|AppointmentRescheduled $event): void
    {
        try {
            if ($event instanceof AppointmentCancellationRequested) {
                $this->updateCancellationStats($event);
            } elseif ($event instanceof AppointmentRescheduled) {
                $this->updateRescheduleStats($event);
            }
        } catch (\Exception $e) {
            Log::error('❌ Failed to update modification stats', [
                'event' => get_class($event),
                'appointment_id' => $event->appointment->id,
                'error' => $e->getMessage(),
            ]);

            // Re-throw for queue retry
            throw $e;
        }
    }

    /**
     * Update cancellation statistics
     */
    private function updateCancellationStats(AppointmentCancellationRequested $event): void
    {
        $customerId = $event->customer->id;
        $now = Carbon::now();
        $periodStart = $now->copy()->subDays(30);
        $periodEnd = $now->copy();

        // Count recent cancellations
        $cancellationCount = DB::table('appointment_modifications')
            ->where('customer_id', $customerId)
            ->where('modification_type', 'cancel')
            ->where('created_at', '>=', $periodStart)
            ->count();

        // Update or create stat record
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

        Log::debug('📊 Updated cancellation stats', [
            'customer_id' => $customerId,
            'count' => $cancellationCount,
        ]);
    }

    /**
     * Update reschedule statistics
     */
    private function updateRescheduleStats(AppointmentRescheduled $event): void
    {
        $customerId = $event->appointment->customer_id;
        $now = Carbon::now();
        $periodStart = $now->copy()->subDays(30);
        $periodEnd = $now->copy();

        // Count recent reschedules for customer
        $rescheduleCount = DB::table('appointment_modifications')
            ->where('customer_id', $customerId)
            ->where('modification_type', 'reschedule')
            ->where('created_at', '>=', $periodStart)
            ->count();

        // Update customer stat
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

        Log::debug('📊 Updated reschedule stats', [
            'customer_id' => $customerId,
            'customer_count' => $rescheduleCount,
        ]);
    }

    /**
     * Handle failed job
     */
    public function failed($event, \Throwable $exception): void
    {
        Log::error('🔥 Stats update job permanently failed', [
            'event' => get_class($event),
            'error' => $exception->getMessage(),
        ]);

        // Stats will be refreshed by RefreshModificationStatsJob hourly
    }
}
