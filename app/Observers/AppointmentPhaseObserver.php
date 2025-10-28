<?php

namespace App\Observers;

use App\Models\Appointment;
use App\Services\AppointmentPhaseCreationService;
use Illuminate\Support\Facades\Log;

/**
 * AppointmentPhaseObserver
 *
 * Automatically creates/updates/deletes AppointmentPhase records
 * when appointments are created/updated/deleted.
 */
class AppointmentPhaseObserver
{
    protected AppointmentPhaseCreationService $phaseService;

    public function __construct(AppointmentPhaseCreationService $phaseService)
    {
        $this->phaseService = $phaseService;
    }

    /**
     * Handle the Appointment "created" event.
     *
     * @param Appointment $appointment
     * @return void
     */
    public function created(Appointment $appointment): void
    {
        // Check feature flag: Auto-create phases
        if (!config('features.processing_time_auto_create_phases', true)) {
            return;
        }

        // Only create phases if service has processing time
        if (!$appointment->service || !$appointment->service->hasProcessingTime()) {
            return;
        }

        try {
            $phases = $this->phaseService->createPhasesForAppointment($appointment);

            Log::info('AppointmentPhaseObserver: Phases created on appointment creation', [
                'appointment_id' => $appointment->id,
                'phases_count' => count($phases),
            ]);
        } catch (\Exception $e) {
            Log::error('AppointmentPhaseObserver: Failed to create phases', [
                'appointment_id' => $appointment->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Handle the Appointment "updated" event.
     *
     * @param Appointment $appointment
     * @return void
     */
    public function updated(Appointment $appointment): void
    {
        // Check feature flag: Auto-create phases
        if (!config('features.processing_time_auto_create_phases', true)) {
            return;
        }

        // Check if starts_at was changed (reschedule)
        if ($appointment->wasChanged('starts_at')) {
            try {
                $phases = $this->phaseService->updatePhasesForRescheduledAppointment($appointment);

                Log::info('AppointmentPhaseObserver: Phases updated on reschedule', [
                    'appointment_id' => $appointment->id,
                    'phases_count' => count($phases),
                ]);
            } catch (\Exception $e) {
                Log::error('AppointmentPhaseObserver: Failed to update phases on reschedule', [
                    'appointment_id' => $appointment->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Check if service_id was changed
        if ($appointment->wasChanged('service_id')) {
            try {
                $appointment->load('service');
                $phases = $this->phaseService->recreatePhasesIfNeeded($appointment);

                Log::info('AppointmentPhaseObserver: Phases recreated on service change', [
                    'appointment_id' => $appointment->id,
                    'phases_count' => count($phases),
                ]);
            } catch (\Exception $e) {
                Log::error('AppointmentPhaseObserver: Failed to recreate phases on service change', [
                    'appointment_id' => $appointment->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Handle the Appointment "deleting" event.
     *
     * Note: Phases are automatically deleted via CASCADE foreign key,
     * but we log it for tracking.
     *
     * @param Appointment $appointment
     * @return void
     */
    public function deleting(Appointment $appointment): void
    {
        $phasesCount = \App\Models\AppointmentPhase::where('appointment_id', $appointment->id)->count();

        if ($phasesCount > 0) {
            Log::info('AppointmentPhaseObserver: Phases will be cascade deleted', [
                'appointment_id' => $appointment->id,
                'phases_count' => $phasesCount,
            ]);
        }
    }
}
