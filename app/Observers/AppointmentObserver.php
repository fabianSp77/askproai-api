<?php

namespace App\Observers;

use App\Models\Appointment;
use App\Models\Call;
use Illuminate\Support\Facades\Log;

/**
 * AppointmentObserver
 *
 * Automatically synchronizes Call appointment flags when appointments are created/updated/deleted
 * to maintain consistency between appointments.call_id and calls.converted_appointment_id
 */
class AppointmentObserver
{
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
            // Has appointment(s) - set flags
            $call->appointment_made = true;
            $call->converted_appointment_id = $latestAppointment->id;
        } else {
            // No appointments - clear flags
            $call->appointment_made = false;
            $call->converted_appointment_id = null;
        }

        $call->saveQuietly(); // Save without triggering events
    }
}
