<?php

namespace App\Listeners\Appointments;

use App\Events\Appointments\AppointmentRescheduled;
use App\Jobs\SyncAppointmentToCalcomJob;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

/**
 * Sync Appointment Reschedule to Cal.com
 *
 * Listens to AppointmentRescheduled events and dispatches sync job to update
 * the booking time in Cal.com (bidirectional sync for calendar consistency).
 *
 * Flow:
 * 1. Appointment rescheduled via Admin UI
 * 2. AppointmentRescheduled event fired
 * 3. This listener dispatches SyncAppointmentToCalcomJob with 'reschedule' action
 * 4. Job updates booking time in Cal.com (if not already updated from Cal.com)
 *
 * Loop Prevention:
 * - Checks sync_origin before dispatching
 * - Skip if origin is 'calcom' (already rescheduled in Cal.com)
 * - Validates calcom_v2_booking_id exists (can't reschedule without it)
 *
 * @see SyncAppointmentToCalcomJob
 * @see AppointmentRescheduled
 */
class SyncToCalcomOnRescheduled implements ShouldQueue
{
    /**
     * Handle the event
     *
     * ⚠️ TEMPORARILY DISABLED - Missing database columns for Cal.com sync
     *
     * @param AppointmentRescheduled $event
     * @return void
     */
    public function handle(AppointmentRescheduled $event): void
    {
        // ⚠️ TEMPORARILY DISABLED - Migration pending
        // Required columns (sync_job_id, calcom_sync_status, etc.) don't exist yet
        Log::channel('calcom')->info('⏭️ Cal.com reschedule sync DISABLED (migration pending)', [
            'appointment_id' => $event->appointment->id,
        ]);
        return;

        $appointment = $event->appointment;

        Log::channel('calcom')->info('📨 AppointmentRescheduled event received', [
            'appointment_id' => $appointment->id,
            'sync_origin' => $appointment->sync_origin,
            'calcom_v2_booking_id' => $appointment->calcom_v2_booking_id,
            'old_start' => $event->oldStartTime->toIso8601String(),
            'new_start' => $event->newStartTime->toIso8601String(),
            'time_diff_hours' => $event->getTimeDiffHours(),
            'reason' => $event->reason,
        ]);

        // ═══════════════════════════════════════════════════════════
        // CRITICAL: Loop Prevention Check
        // ═══════════════════════════════════════════════════════════
        if ($this->shouldSkipSync($appointment)) {
            Log::channel('calcom')->info('⏭️ Skipping Cal.com reschedule sync (loop prevention)', [
                'appointment_id' => $appointment->id,
                'sync_origin' => $appointment->sync_origin,
                'calcom_v2_booking_id' => $appointment->calcom_v2_booking_id,
                'reason' => $this->getSkipReason($appointment),
            ]);
            return;
        }

        // ═══════════════════════════════════════════════════════════
        // Dispatch Sync Job
        // ═══════════════════════════════════════════════════════════
        try {
            Log::channel('calcom')->info('🚀 Dispatching Cal.com sync job (RESCHEDULE)', [
                'appointment_id' => $appointment->id,
                'calcom_v2_booking_id' => $appointment->calcom_v2_booking_id,
                'new_start' => $event->newStartTime->toIso8601String(),
                'new_end' => $appointment->ends_at->toIso8601String(),
            ]);

            SyncAppointmentToCalcomJob::dispatch($appointment, 'reschedule');

        } catch (\Exception $e) {
            Log::channel('calcom')->error('❌ Failed to dispatch Cal.com reschedule sync job', [
                'appointment_id' => $appointment->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    /**
     * Determine if sync should be skipped
     *
     * @param \App\Models\Appointment $appointment
     * @return bool
     */
    protected function shouldSkipSync($appointment): bool
    {
        // Skip if origin is 'calcom' (already rescheduled in Cal.com, prevent loop)
        if ($appointment->sync_origin === 'calcom') {
            return true;
        }

        // Skip if no Cal.com booking ID (can't reschedule what doesn't exist)
        if (!$appointment->calcom_v2_booking_id) {
            return true;
        }

        // Skip if service has no Cal.com event type
        if (!$appointment->service || !$appointment->service->calcom_event_type_id) {
            return true;
        }

        // Skip if already pending reschedule (another job is processing)
        if ($appointment->calcom_sync_status === 'pending' && $appointment->sync_job_id) {
            return true;
        }

        return false;
    }

    /**
     * Get human-readable reason for skipping sync
     *
     * @param \App\Models\Appointment $appointment
     * @return string
     */
    protected function getSkipReason($appointment): string
    {
        if ($appointment->sync_origin === 'calcom') {
            return 'Origin is Cal.com (loop prevention)';
        }

        if (!$appointment->calcom_v2_booking_id) {
            return 'No Cal.com booking ID to reschedule';
        }

        if (!$appointment->service || !$appointment->service->calcom_event_type_id) {
            return 'Service has no Cal.com event type';
        }

        if ($appointment->calcom_sync_status === 'pending' && $appointment->sync_job_id) {
            return 'Sync job already pending';
        }

        return 'Unknown reason';
    }

    /**
     * Handle listener failure
     *
     * @param AppointmentRescheduled $event
     * @param \Throwable $exception
     * @return void
     */
    public function failed(AppointmentRescheduled $event, \Throwable $exception): void
    {
        Log::channel('calcom')->critical('💀 SyncToCalcomOnRescheduled listener failed', [
            'appointment_id' => $event->appointment->id,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);

        // Flag appointment for manual review
        $event->appointment->update([
            'requires_manual_review' => true,
            'manual_review_flagged_at' => now(),
            'sync_error_message' => 'Reschedule listener failed: ' . $exception->getMessage(),
        ]);
    }
}
