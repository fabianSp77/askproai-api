<?php

namespace App\Listeners\Appointments;

use App\Events\Appointments\AppointmentCancelled;
use App\Jobs\SyncAppointmentToCalcomJob;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

/**
 * Sync Appointment Cancellation to Cal.com
 *
 * Listens to AppointmentCancelled events and dispatches sync job to cancel
 * the booking in Cal.com (bidirectional sync for availability restoration).
 *
 * Flow:
 * 1. Appointment cancelled via Admin UI or automated policy
 * 2. AppointmentCancelled event fired
 * 3. This listener dispatches SyncAppointmentToCalcomJob with 'cancel' action
 * 4. Job cancels booking in Cal.com (if not already cancelled from Cal.com)
 *
 * Loop Prevention:
 * - Checks sync_origin before dispatching
 * - Skip if origin is 'calcom' (already cancelled in Cal.com)
 * - Validates calcom_v2_booking_id exists (can't cancel without it)
 *
 * @see SyncAppointmentToCalcomJob
 * @see AppointmentCancelled
 */
class SyncToCalcomOnCancelled implements ShouldQueue
{
    /**
     * Handle the event
     *
     * @param AppointmentCancelled $event
     * @return void
     */
    public function handle(AppointmentCancelled $event): void
    {
        $appointment = $event->appointment;

        Log::channel('calcom')->info('📨 AppointmentCancelled event received', [
            'appointment_id' => $appointment->id,
            'sync_origin' => $appointment->sync_origin,
            'calcom_v2_booking_id' => $appointment->calcom_v2_booking_id,
            'reason' => $event->reason,
            'cancelled_by' => $event->cancelledBy,
        ]);

        // ═══════════════════════════════════════════════════════════
        // CRITICAL: Loop Prevention Check
        // ═══════════════════════════════════════════════════════════
        if ($this->shouldSkipSync($appointment)) {
            Log::channel('calcom')->info('⏭️ Skipping Cal.com cancellation sync (loop prevention)', [
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
            Log::channel('calcom')->info('🚀 Dispatching Cal.com sync job (CANCEL)', [
                'appointment_id' => $appointment->id,
                'calcom_v2_booking_id' => $appointment->calcom_v2_booking_id,
                'reason' => $event->reason,
            ]);

            SyncAppointmentToCalcomJob::dispatch($appointment, 'cancel');

        } catch (\Exception $e) {
            Log::channel('calcom')->error('❌ Failed to dispatch Cal.com cancel sync job', [
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
        // Skip if origin is 'calcom' (already cancelled in Cal.com, prevent loop)
        if ($appointment->sync_origin === 'calcom') {
            return true;
        }

        // Skip if no Cal.com booking ID (can't cancel what doesn't exist)
        if (!$appointment->calcom_v2_booking_id) {
            return true;
        }

        // Skip if service has no Cal.com event type
        if (!$appointment->service || !$appointment->service->calcom_event_type_id) {
            return true;
        }

        // Skip if already pending cancellation (another job is processing)
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
            return 'No Cal.com booking ID to cancel';
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
     * @param AppointmentCancelled $event
     * @param \Throwable $exception
     * @return void
     */
    public function failed(AppointmentCancelled $event, \Throwable $exception): void
    {
        Log::channel('calcom')->critical('💀 SyncToCalcomOnCancelled listener failed', [
            'appointment_id' => $event->appointment->id,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);

        // Flag appointment for manual review
        $event->appointment->update([
            'requires_manual_review' => true,
            'manual_review_flagged_at' => now(),
            'sync_error_message' => 'Cancel listener failed: ' . $exception->getMessage(),
        ]);
    }
}
