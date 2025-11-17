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

        // 🔧 NEW 2025-11-12: Auto-create AppointmentPhase records for composite services
        $this->createPhasesForCompositeService($appointment);
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
     * Create AppointmentPhase records for composite services
     *
     * Composite services have multiple segments with gaps (e.g., Dauerwelle):
     * - Active segments: staff_required = true (staff is BUSY)
     * - Gap segments: staff_required = false (staff is AVAILABLE for other customers)
     *
     * This enables optimal staff utilization during processing/waiting times.
     *
     * @param Appointment $appointment
     * @return void
     */
    private function createPhasesForCompositeService(Appointment $appointment): void
    {
        // Load service relationship if not loaded
        if (!$appointment->relationLoaded('service')) {
            $appointment->load('service');
        }

        $service = $appointment->service;

        // Skip if not composite or no segments defined
        if (!$service || !$service->composite || empty($service->segments)) {
            return;
        }

        Log::info('🎨 Creating AppointmentPhase records for composite service', [
            'appointment_id' => $appointment->id,
            'service_id' => $service->id,
            'service_name' => $service->name,
            'segments_count' => count($service->segments)
        ]);

        $startTime = $appointment->starts_at;
        $offset = 0;

        foreach ($service->segments as $segment) {
            $phaseStart = $startTime->copy()->addMinutes($offset);
            $duration = $segment['durationMin'] ?? 0;
            $phaseEnd = $phaseStart->copy()->addMinutes($duration);

            // Map segment type to phase_type enum
            $phaseType = match($segment['type'] ?? 'active') {
                'active' => $offset === 0 ? 'initial' : 'final',
                'processing' => 'processing',
                default => 'initial'
            };

            // Create phase record
            \App\Models\AppointmentPhase::create([
                'appointment_id' => $appointment->id,
                'phase_type' => $phaseType,
                'start_offset_minutes' => $offset,
                'duration_minutes' => $duration,
                'staff_required' => $segment['staff_required'] ?? true,
                'start_time' => $phaseStart,
                'end_time' => $phaseEnd,
            ]);

            Log::debug('  • Phase created', [
                'key' => $segment['key'] ?? '?',
                'name' => $segment['name'] ?? 'Unknown',
                'type' => $segment['type'] ?? 'active',
                'staff_required' => $segment['staff_required'] ?? true,
                'duration' => $duration,
                'time' => $phaseStart->format('H:i') . ' - ' . $phaseEnd->format('H:i')
            ]);

            $offset += $duration;
        }

        Log::info('✅ AppointmentPhase records created', [
            'appointment_id' => $appointment->id,
            'phases_created' => count($service->segments),
            'total_duration' => $offset . ' minutes'
        ]);
    }
}
