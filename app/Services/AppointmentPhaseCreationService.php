<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\AppointmentPhase;
use App\Models\Service;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * AppointmentPhaseCreationService
 *
 * Automatically creates AppointmentPhase records for appointments
 * with services that have processing time.
 */
class AppointmentPhaseCreationService
{
    /**
     * Create phases for an appointment if the service has processing time
     *
     * @param Appointment $appointment
     * @return array<AppointmentPhase>
     */
    public function createPhasesForAppointment(Appointment $appointment): array
    {
        // Load service if not already loaded
        if (!$appointment->relationLoaded('service')) {
            $appointment->load('service');
        }

        $service = $appointment->service;

        // If service doesn't have processing time, return empty array
        if (!$service || !$service->hasProcessingTime()) {
            return [];
        }

        // Validate that the appointment has required data
        if (!$appointment->starts_at) {
            Log::warning('Cannot create phases for appointment without starts_at', [
                'appointment_id' => $appointment->id,
            ]);
            return [];
        }

        // Generate phase data from service
        $phasesData = $service->generatePhases($appointment->starts_at);

        if (empty($phasesData)) {
            Log::warning('Service generatePhases returned empty array', [
                'service_id' => $service->id,
                'has_processing_time' => $service->has_processing_time,
            ]);
            return [];
        }

        // Create phases in database
        $createdPhases = [];

        DB::transaction(function () use ($appointment, $phasesData, &$createdPhases) {
            foreach ($phasesData as $phaseData) {
                $phase = AppointmentPhase::create(array_merge($phaseData, [
                    'appointment_id' => $appointment->id,
                ]));

                $createdPhases[] = $phase;
            }
        });

        Log::info('Created appointment phases', [
            'appointment_id' => $appointment->id,
            'service_id' => $service->id,
            'phases_count' => count($createdPhases),
        ]);

        return $createdPhases;
    }

    /**
     * Update phases for an appointment when rescheduled
     *
     * @param Appointment $appointment
     * @return array<AppointmentPhase>
     */
    public function updatePhasesForRescheduledAppointment(Appointment $appointment): array
    {
        // Delete existing phases
        $this->deletePhases($appointment);

        // Create new phases with updated times
        return $this->createPhasesForAppointment($appointment);
    }

    /**
     * Delete all phases for an appointment
     *
     * @param Appointment $appointment
     * @return int Number of deleted phases
     */
    public function deletePhases(Appointment $appointment): int
    {
        $count = AppointmentPhase::where('appointment_id', $appointment->id)->count();

        AppointmentPhase::where('appointment_id', $appointment->id)->delete();

        Log::info('Deleted appointment phases', [
            'appointment_id' => $appointment->id,
            'deleted_count' => $count,
        ]);

        return $count;
    }

    /**
     * Recreate phases if service changed (e.g., processing time was added/removed)
     *
     * @param Appointment $appointment
     * @return array<AppointmentPhase>
     */
    public function recreatePhasesIfNeeded(Appointment $appointment): array
    {
        // Check if service has processing time
        if (!$appointment->service || !$appointment->service->hasProcessingTime()) {
            // Delete phases if they exist (service no longer has processing time)
            $this->deletePhases($appointment);
            return [];
        }

        // Check if phases already exist
        $existingPhasesCount = AppointmentPhase::where('appointment_id', $appointment->id)->count();

        if ($existingPhasesCount === 0) {
            // No phases exist, create them
            return $this->createPhasesForAppointment($appointment);
        }

        // Phases exist - check if they need updating
        $expectedPhases = $appointment->service->generatePhases($appointment->starts_at);

        if (count($expectedPhases) !== $existingPhasesCount) {
            // Phase count changed, recreate
            return $this->updatePhasesForRescheduledAppointment($appointment);
        }

        // Return existing phases
        return AppointmentPhase::where('appointment_id', $appointment->id)
            ->orderBy('start_offset_minutes')
            ->get()
            ->all();
    }

    /**
     * Bulk create phases for multiple appointments
     *
     * @param array<Appointment> $appointments
     * @return array<int, array<AppointmentPhase>> Keyed by appointment ID
     */
    public function bulkCreatePhases(array $appointments): array
    {
        $results = [];

        DB::transaction(function () use ($appointments, &$results) {
            foreach ($appointments as $appointment) {
                $results[$appointment->id] = $this->createPhasesForAppointment($appointment);
            }
        });

        return $results;
    }

    /**
     * Check if appointment has phases created
     *
     * @param Appointment $appointment
     * @return bool
     */
    public function hasPhases(Appointment $appointment): bool
    {
        return AppointmentPhase::where('appointment_id', $appointment->id)->exists();
    }

    /**
     * Get phase statistics for an appointment
     *
     * @param Appointment $appointment
     * @return array{total: int, busy: int, available: int, total_duration: int, busy_duration: int, available_duration: int}
     */
    public function getPhaseStats(Appointment $appointment): array
    {
        $phases = AppointmentPhase::where('appointment_id', $appointment->id)->get();

        $stats = [
            'total' => $phases->count(),
            'busy' => $phases->where('staff_required', true)->count(),
            'available' => $phases->where('staff_required', false)->count(),
            'total_duration' => $phases->sum('duration_minutes'),
            'busy_duration' => $phases->where('staff_required', true)->sum('duration_minutes'),
            'available_duration' => $phases->where('staff_required', false)->sum('duration_minutes'),
        ];

        return $stats;
    }

    /**
     * Create phases from COMPOSITE service segments
     *
     * Converts service.segments JSON array to AppointmentPhase records
     * with full 6-phase granularity (vs Processing Time 3-phase model).
     *
     * @param Appointment $appointment
     * @return array<AppointmentPhase>
     */
    public function createPhasesFromSegments(Appointment $appointment): array
    {
        // Load service if not already loaded
        if (!$appointment->relationLoaded('service')) {
            $appointment->load('service');
        }

        $service = $appointment->service;

        // Validate service has segments
        if (!$service || !$service->isComposite() || empty($service->segments)) {
            Log::warning('Cannot create composite phases: Service has no segments', [
                'appointment_id' => $appointment->id,
                'service_id' => $service?->id,
                'is_composite' => $service?->isComposite(),
            ]);
            return [];
        }

        // Validate appointment has start time
        if (!$appointment->starts_at) {
            Log::warning('Cannot create composite phases: Appointment has no start time', [
                'appointment_id' => $appointment->id,
            ]);
            return [];
        }

        $startTime = $appointment->starts_at;
        $segments = $service->segments;

        // Sort segments by 'order' field
        usort($segments, fn($a, $b) => ($a['order'] ?? 0) <=> ($b['order'] ?? 0));

        $createdPhases = [];

        DB::transaction(function () use ($appointment, $segments, $startTime, &$createdPhases) {
            $offset = 0;

            foreach ($segments as $index => $segment) {
                // Determine phase type from segment type
                $phaseType = match ($segment['type'] ?? 'active') {
                    'processing' => 'processing',  // Gap/Einwirkzeit
                    'active' => $index === 0 ? 'initial' : 'final',  // First = initial, rest = final
                    default => 'final',
                };

                $duration = $segment['durationMin'] ?? $segment['duration_minutes'] ?? 0;

                if ($duration <= 0) {
                    Log::warning('Skipping segment with zero duration', [
                        'segment_key' => $segment['key'] ?? 'unknown',
                        'segment_name' => $segment['name'] ?? 'unknown',
                    ]);
                    continue;
                }

                // Create phase record
                $phase = AppointmentPhase::create([
                    'appointment_id' => $appointment->id,
                    'phase_type' => $phaseType,
                    'segment_name' => $segment['name'] ?? null,
                    'segment_key' => $segment['key'] ?? null,
                    'sequence_order' => $index + 1,
                    'start_offset_minutes' => $offset,
                    'duration_minutes' => $duration,
                    'staff_required' => $segment['staff_required'] ?? true,
                    'start_time' => $startTime->copy()->addMinutes($offset),
                    'end_time' => $startTime->copy()->addMinutes($offset + $duration),
                ]);

                $createdPhases[] = $phase;
                $offset += $duration;
            }
        });

        $totalDuration = collect($createdPhases)->sum('duration_minutes');

        Log::info('Created composite phases from segments', [
            'appointment_id' => $appointment->id,
            'service_id' => $service->id,
            'phases_count' => count($createdPhases),
            'total_duration' => $totalDuration,
        ]);

        return $createdPhases;
    }
}
