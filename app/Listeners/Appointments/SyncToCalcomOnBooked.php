<?php

namespace App\Listeners\Appointments;

use App\Events\Appointments\AppointmentBooked;
use App\Jobs\SyncAppointmentToCalcomJob;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

/**
 * Sync Appointment to Cal.com When Booked
 *
 * Listens to AppointmentBooked events and dispatches sync job to create
 * the booking in Cal.com (bidirectional sync for double-booking prevention).
 *
 * Flow:
 * 1. Appointment booked via Retell AI or Admin UI
 * 2. AppointmentBooked event fired
 * 3. This listener dispatches SyncAppointmentToCalcomJob
 * 4. Job syncs to Cal.com (if not already from Cal.com webhook)
 *
 * Loop Prevention:
 * - Checks sync_origin before dispatching
 * - Skip if origin is 'calcom' (already in Cal.com)
 * - Skip if already synced/pending (avoid duplicate jobs)
 *
 * @see SyncAppointmentToCalcomJob
 * @see AppointmentBooked
 */
class SyncToCalcomOnBooked implements ShouldQueue
{
    /**
     * Handle the event
     *
     * @param AppointmentBooked $event
     * @return void
     */
    public function handle(AppointmentBooked $event): void
    {
        $appointment = $event->appointment;

        // Eager load service to prevent N+1 query
        $appointment->loadMissing('service');

        Log::channel('calcom')->info('📨 AppointmentBooked event received', [
            'appointment_id' => $appointment->id,
            'sync_origin' => $appointment->sync_origin,
            'calcom_sync_status' => $appointment->calcom_sync_status,
            'service_id' => $appointment->service_id,
        ]);

        // ═══════════════════════════════════════════════════════════
        // CRITICAL: Loop Prevention Check
        // ═══════════════════════════════════════════════════════════
        if ($this->shouldSkipSync($appointment)) {
            Log::channel('calcom')->info('⏭️ Skipping Cal.com sync (loop prevention)', [
                'appointment_id' => $appointment->id,
                'sync_origin' => $appointment->sync_origin,
                'calcom_sync_status' => $appointment->calcom_sync_status,
                'reason' => $this->getSkipReason($appointment),
            ]);
            return;
        }

        // ═══════════════════════════════════════════════════════════
        // Dispatch Sync Job
        // ═══════════════════════════════════════════════════════════
        try {
            Log::channel('calcom')->info('🚀 Dispatching Cal.com sync job (CREATE)', [
                'appointment_id' => $appointment->id,
                'sync_origin' => $appointment->sync_origin,
            ]);

            SyncAppointmentToCalcomJob::dispatch($appointment, 'create');

        } catch (\Exception $e) {
            Log::channel('calcom')->error('❌ Failed to dispatch Cal.com sync job', [
                'appointment_id' => $appointment->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Flag appointment for manual review (calcom_sync_status column exists since 2025_10_11 migration)
            $appointment->update(['calcom_sync_status' => 'failed']);
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
        // Skip if origin is 'calcom' (already in Cal.com, prevent loop)
        if ($appointment->sync_origin === 'calcom') {
            return true;
        }

        // Skip if service has no Cal.com event type (can't sync)
        if (!$appointment->service || !$appointment->service->calcom_event_type_id) {
            return true;
        }

        // Skip if already synced
        if ($appointment->calcom_sync_status === 'synced') {
            return true;
        }

        // Skip if already pending (another job is processing)
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

        if (!$appointment->service || !$appointment->service->calcom_event_type_id) {
            return 'Service has no Cal.com event type';
        }

        if ($appointment->calcom_sync_status === 'synced') {
            return 'Already synced to Cal.com';
        }

        if ($appointment->calcom_sync_status === 'pending' && $appointment->sync_job_id) {
            return 'Sync job already pending';
        }

        return 'Unknown reason';
    }

    /**
     * Handle listener failure
     *
     * @param AppointmentBooked $event
     * @param \Throwable $exception
     * @return void
     */
    public function failed(AppointmentBooked $event, \Throwable $exception): void
    {
        Log::channel('calcom')->critical('💀 SyncToCalcomOnBooked listener failed', [
            'appointment_id' => $event->appointment->id,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);

        // Reload appointment from database before updating (avoid stale model)
        $appointment = \App\Models\Appointment::find($event->appointment->id);
        if ($appointment) {
            $appointment->update([
                'requires_manual_review' => true,
                'manual_review_flagged_at' => now(),
                'sync_error_message' => 'Listener failed: ' . $exception->getMessage(),
            ]);
        }
    }
}
