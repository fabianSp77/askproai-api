<?php

namespace App\Observers;

use App\Models\Appointment;
use App\Models\AppointmentAuditLog;
use App\Models\Call;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * AppointmentObserver
 *
 * RESPONSIBILITIES:
 * - Call sync: Synchronizes Call appointment flags when appointments are created/updated/deleted
 * - Optimistic locking: Validates version field to prevent concurrent modification conflicts
 * - Audit trail: Creates immutable audit logs for all appointment changes (Customer Portal)
 */
class AppointmentObserver
{
    /**
     * Handle the Appointment "creating" event.
     *
     * Customer Portal: Initialize optimistic locking fields
     */
    public function creating(Appointment $appointment): void
    {
        // Initialize version if not set
        if (!$appointment->version) {
            $appointment->version = 1;
        }

        // Initialize last_modified_at if not set
        if (!$appointment->last_modified_at) {
            $appointment->last_modified_at = now();
        }

        // Set last_modified_by if authenticated user exists
        if (!$appointment->last_modified_by && auth()->check()) {
            $appointment->last_modified_by = auth()->id();
        }
    }

    /**
     * Handle the Appointment "created" event.
     */
    public function created(Appointment $appointment): void
    {
        if ($appointment->call_id) {
            $this->syncCallFlags($appointment->call_id);

            Log::info('AppointmentObserver: Synced call flags after appointment creation', [
                'appointment_id' => $appointment->id,
                'call_id' => $appointment->call_id
            ]);
        }

        // Customer Portal: Create audit log for appointment creation
        AppointmentAuditLog::logAction(
            $appointment,
            AppointmentAuditLog::ACTION_CREATED,
            auth()->user(),
            null, // no old values
            $appointment->only([
                'starts_at', 'ends_at', 'status', 'customer_id',
                'staff_id', 'service_id', 'branch_id'
            ]),
            null // no reason needed for creation
        );

        // 🆕 CRITICAL FIX 2025-11-24: Fire AppointmentBooked event for Cal.com sync
        // This was removed during refactoring on 2025-11-20, causing 25 appointments
        // to remain stuck in 'pending' status (never synced to Cal.com)
        // The event triggers SyncToCalcomOnBooked listener which dispatches SyncAppointmentToCalcomJob
        if (in_array($appointment->status, ['scheduled', 'confirmed', 'booked'])) {
            // Only fire event if sync is needed (not already synced)
            if ($appointment->calcom_sync_status === 'pending') {
                Log::info('AppointmentObserver: Firing AppointmentBooked event for Cal.com sync', [
                    'appointment_id' => $appointment->id,
                    'status' => $appointment->status,
                    'sync_status' => $appointment->calcom_sync_status
                ]);

                event(new \App\Events\Appointments\AppointmentBooked($appointment));
            }
        }

        // 🔧 REMOVED 2025-11-20: Phase creation moved to AppointmentPhaseObserver
        // AppointmentPhaseObserver now handles BOTH Processing Time AND Composite phases
        // with full support for segment_name, segment_key, sequence_order fields
    }

    /**
     * Handle the Appointment "updating" event.
     *
     * Customer Portal: Optimistic locking validation
     * Prevents concurrent modification conflicts by validating version field
     */
    public function updating(Appointment $appointment): void
    {
        // Skip optimistic locking for new records or internal system updates
        if (!$appointment->exists || $appointment->isDirty('lock_token')) {
            return;
        }

        // Skip if no user context (system/background jobs)
        if (!auth()->check()) {
            return;
        }

        // Customer Portal: Optimistic locking validation
        // Check if critical fields are being modified
        $criticalFields = ['starts_at', 'ends_at', 'staff_id', 'service_id', 'status'];
        $hasCriticalChanges = collect($criticalFields)->some(fn($field) => $appointment->isDirty($field));

        if ($hasCriticalChanges) {
            // Get current version from database
            $currentVersion = Appointment::where('id', $appointment->id)
                ->value('version');

            // If version has changed since the user loaded the form, someone else modified it
            if ($currentVersion !== $appointment->getOriginal('version')) {
                throw new \Exception(
                    "This appointment has been modified by another user. " .
                    "Please reload the page to see the latest version before making changes. " .
                    "(Current version: {$currentVersion}, Your version: {$appointment->getOriginal('version')})"
                );
            }

            // Increment version for this update
            $appointment->version = $currentVersion + 1;
            $appointment->last_modified_at = now();
            $appointment->last_modified_by = auth()->id();

            Log::info('[AppointmentObserver] Optimistic locking: Version incremented', [
                'appointment_id' => $appointment->id,
                'old_version' => $currentVersion,
                'new_version' => $appointment->version,
                'modified_by' => auth()->id(),
            ]);
        }
    }

    /**
     * Handle the Appointment "updated" event.
     */
    public function updated(Appointment $appointment): void
    {
        // Check if call_id was changed
        if ($appointment->isDirty('call_id')) {
            $originalCallId = $appointment->getOriginal('call_id');
            $newCallId = $appointment->call_id;

            // Sync both old and new calls
            if ($originalCallId) {
                $this->syncCallFlags($originalCallId);
            }

            if ($newCallId) {
                $this->syncCallFlags($newCallId);
            }

            Log::info('AppointmentObserver: Synced call flags after call_id change', [
                'appointment_id' => $appointment->id,
                'original_call_id' => $originalCallId,
                'new_call_id' => $newCallId
            ]);
        }

        // Customer Portal: Create audit log for appointment updates
        // Track status changes (rescheduled, cancelled)
        if ($appointment->wasChanged('status')) {
            $action = match($appointment->status) {
                'cancelled' => AppointmentAuditLog::ACTION_CANCELLED,
                'scheduled', 'confirmed' => AppointmentAuditLog::ACTION_RESCHEDULED,
                default => AppointmentAuditLog::ACTION_UPDATED
            };

            AppointmentAuditLog::logAction(
                $appointment,
                $action,
                auth()->user(),
                $appointment->getOriginal(), // old values
                $appointment->getAttributes(), // new values
                request()->input('cancel_reason') ?? request()->input('reschedule_reason') // reason if provided
            );
        }
        // Track time/staff/service changes
        elseif ($appointment->wasChanged(['starts_at', 'ends_at', 'staff_id', 'service_id'])) {
            AppointmentAuditLog::logAction(
                $appointment,
                AppointmentAuditLog::ACTION_RESCHEDULED,
                auth()->user(),
                $appointment->getOriginal(),
                $appointment->getAttributes(),
                null
            );
        }
    }

    /**
     * Handle the Appointment "deleted" event.
     */
    public function deleted(Appointment $appointment): void
    {
        if ($appointment->call_id) {
            $this->syncCallFlags($appointment->call_id);

            Log::info('AppointmentObserver: Synced call flags after appointment deletion', [
                'appointment_id' => $appointment->id,
                'call_id' => $appointment->call_id
            ]);
        }

        // Customer Portal: Audit log for soft delete
        AppointmentAuditLog::logAction(
            $appointment,
            AppointmentAuditLog::ACTION_CANCELLED,
            auth()->user(),
            $appointment->getOriginal(),
            ['deleted_at' => now()],
            request()->input('delete_reason') ?? 'Appointment deleted'
        );
    }

    /**
     * Handle the Appointment "restored" event.
     */
    public function restored(Appointment $appointment): void
    {
        if ($appointment->call_id) {
            $this->syncCallFlags($appointment->call_id);

            Log::info('AppointmentObserver: Synced call flags after appointment restoration', [
                'appointment_id' => $appointment->id,
                'call_id' => $appointment->call_id
            ]);
        }

        // Customer Portal: Audit log for restoration
        AppointmentAuditLog::logAction(
            $appointment,
            AppointmentAuditLog::ACTION_RESTORED,
            auth()->user(),
            ['deleted_at' => $appointment->deleted_at],
            ['deleted_at' => null],
            request()->input('restore_reason') ?? 'Appointment restored'
        );
    }

    /**
     * Synchronize call appointment flags based on existing appointments
     */
    private function syncCallFlags(int $callId): void
    {
        $call = Call::find($callId);

        if (!$call) {
            Log::warning('AppointmentObserver: Call not found for sync', [
                'call_id' => $callId
            ]);
            return;
        }

        // Find the latest appointment for this call
        $latestAppointment = Appointment::where('call_id', $callId)
            ->orderBy('created_at', 'desc')
            ->first();

        if ($latestAppointment) {
            // 🔧 FIX 2025-11-12: Sync customer_id from appointment to call
            // This ensures customer names appear in the call list
            if ($latestAppointment->customer_id && !$call->customer_id) {
                $call->forceFill([
                    'appointment_made' => true,
                    'converted_appointment_id' => $latestAppointment->id,
                    'customer_id' => $latestAppointment->customer_id
                ]);

                Log::info('✅ AppointmentObserver: Synced customer_id from appointment to call', [
                    'call_id' => $call->id,
                    'customer_id' => $latestAppointment->customer_id,
                    'appointment_id' => $latestAppointment->id
                ]);
            } else {
                // Has appointment(s) - set flags only
                $call->forceFill([
                    'appointment_made' => true,
                    'converted_appointment_id' => $latestAppointment->id
                ]);
            }
        } else {
            // No appointments - clear flags
            $call->forceFill([
                'appointment_made' => false,
                'converted_appointment_id' => null
            ]);
        }

        $call->saveQuietly(); // Save without triggering events
    }

    /**
     * DEPRECATED 2025-11-20: Moved to AppointmentPhaseObserver
     *
     * Phase creation is now centralized in AppointmentPhaseObserver which supports:
     * - Processing Time (3-phase model)
     * - Composite Services (N-segment model)
     * - Full segment metadata (segment_name, segment_key, sequence_order)
     *
     * See: app/Observers/AppointmentPhaseObserver.php
     * See: app/Services/AppointmentPhaseCreationService.php
     */
}
