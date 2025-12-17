<?php

namespace App\Services;

use App\Models\Staff;
use App\Models\Service;
use App\Models\AppointmentPhase;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * ProcessingTimeAvailabilityService
 *
 * Handles staff availability calculations for services with processing time.
 * Key concept: Staff can serve other customers during processing/gap phases.
 *
 * Example:
 * 10:00-10:15 | Customer A: Apply color (BUSY)
 * 10:15-10:40 | Customer A: Processing (AVAILABLE) ← Staff can serve Customer B here!
 * 10:40-11:00 | Customer A: Rinse & style (BUSY)
 */
class ProcessingTimeAvailabilityService
{
    /**
     * Check if staff is available for a service at a specific time
     *
     * @param string $staffId UUID
     * @param Carbon $startTime
     * @param Service $service
     * @return bool
     */
    public function isStaffAvailable(string $staffId, Carbon $startTime, Service $service): bool
    {
        // Safe method call: getTotalDuration() may not exist on all service types
        $duration = method_exists($service, 'getTotalDuration')
            ? $service->getTotalDuration()
            : ($service->duration_minutes ?? 60);
        $endTime = $startTime->copy()->addMinutes($duration);

        // 🔧 FIX 2025-11-23: ALWAYS check for overlapping appointments first
        // BUG: Processing-time services were only checking busy phases, missing regular appointments
        // This caused false positives when a processing-time service was requested during a regular appointment
        // Example: Dauerwelle (processing-time) 10:45-13:00 vs Herrenhaarschnitt (regular) 10:00-12:15
        //          The phase check would pass (no phases in regular appointments), but durations overlap!
        if ($this->hasOverlappingAppointments($staffId, $startTime, $endTime)) {
            return false;
        }

        // For processing time services, ADDITIONALLY check phase-aware conflicts
        // This handles interleaving: staff can serve customer B during customer A's processing phase
        // Safe method call: hasProcessingTime() and generatePhases() may not exist on all service types
        $hasProcessingTime = method_exists($service, 'hasProcessingTime') && $service->hasProcessingTime();
        if ($hasProcessingTime && method_exists($service, 'generatePhases')) {
            $proposedPhases = $service->generatePhases($startTime);

            foreach ($proposedPhases as $phase) {
                // Only check phases where staff is required (busy phases)
                if ($phase['staff_required']) {
                    $hasConflict = $this->hasOverlappingBusyPhases(
                        $staffId,
                        $phase['start_time'],
                        $phase['end_time']
                    );

                    if ($hasConflict) {
                        return false;
                    }
                }
            }
        }

        return true;
    }

    /**
     * Check if there are any appointments (regular services) overlapping with the given time range
     *
     * @param string $staffId UUID
     * @param Carbon $startTime
     * @param Carbon $endTime
     * @return bool
     */
    private function hasOverlappingAppointments(string $staffId, Carbon $startTime, Carbon $endTime): bool
    {
        // 🔧 FIX 2025-11-26: Get staff's company_id to check company-level appointments
        // BUG: Appointments with staff_id=NULL (company-level) were not detected!
        // Example: Appointment #782 had staff_id=NULL, so isStaffAvailable() returned TRUE
        //          even though the 08:00-10:15 slot was taken!
        $staff = \App\Models\Staff::find($staffId);
        $companyId = $staff?->company_id;

        // Get all appointments for this staff OR company-level appointments (NULL staff_id)
        $appointments = \App\Models\Appointment::query()
            ->where(function ($query) use ($staffId, $companyId) {
                // Check appointments assigned to this specific staff
                $query->where('staff_id', $staffId);

                // 🔧 FIX: Also check company-level appointments (no staff assigned)
                // These appointments block ALL staff in the company
                if ($companyId) {
                    $query->orWhere(function ($q) use ($companyId) {
                        $q->whereNull('staff_id')
                          ->where('company_id', $companyId);
                    });
                }
            })
            ->whereIn('status', ['scheduled', 'confirmed'])
            ->where(function ($query) use ($startTime, $endTime) {
                $query->where(function ($q) use ($startTime, $endTime) {
                    // Use < and > for exclusive range check (not <=/>= which is inclusive)
                    $q->where('starts_at', '<', $endTime)
                      ->where('ends_at', '>', $startTime);
                });
            })
            ->with('service')
            ->get();

        foreach ($appointments as $appointment) {
            // If the existing appointment has processing time, check busy phases only
            if ($appointment->service && $appointment->service->hasProcessingTime()) {
                // Check if any busy phases of this appointment overlap
                $hasBusyOverlap = AppointmentPhase::query()
                    ->where('appointment_id', $appointment->id)
                    ->where('staff_required', true)
                    ->where('start_time', '<', $endTime)
                    ->where('end_time', '>', $startTime)
                    ->exists();

                if ($hasBusyOverlap) {
                    return true;
                }
            } else {
                // Regular appointment (no processing time) - already overlaps
                return true;
            }
        }

        return false;
    }

    /**
     * Check if there are any busy phases overlapping with the given time range
     *
     * @param string $staffId UUID
     * @param Carbon $startTime
     * @param Carbon $endTime
     * @return bool
     */
    public function hasOverlappingBusyPhases(string $staffId, Carbon $startTime, Carbon $endTime): bool
    {
        // 🔧 FIX 2025-11-26: Get staff's company_id to check company-level appointments
        $staff = \App\Models\Staff::find($staffId);
        $companyId = $staff?->company_id;

        return AppointmentPhase::query()
            ->whereHas('appointment', function ($query) use ($staffId, $companyId) {
                $query->where(function ($q) use ($staffId, $companyId) {
                    // Check appointments assigned to this specific staff
                    $q->where('staff_id', $staffId);

                    // 🔧 FIX: Also check company-level appointments (no staff assigned)
                    if ($companyId) {
                        $q->orWhere(function ($q2) use ($companyId) {
                            $q2->whereNull('staff_id')
                               ->where('company_id', $companyId);
                        });
                    }
                })->whereIn('status', ['scheduled', 'confirmed']);
            })
            ->where('staff_required', true) // Only check BUSY phases
            ->where(function ($query) use ($startTime, $endTime) {
                // Check for any overlap
                $query->where(function ($q) use ($startTime, $endTime) {
                    // Phase starts within the range
                    $q->whereBetween('start_time', [$startTime, $endTime]);
                })->orWhere(function ($q) use ($startTime, $endTime) {
                    // Phase ends within the range
                    $q->whereBetween('end_time', [$startTime, $endTime]);
                })->orWhere(function ($q) use ($startTime, $endTime) {
                    // Phase spans the entire range
                    $q->where('start_time', '<=', $startTime)
                      ->where('end_time', '>=', $endTime);
                });
            })
            ->exists();
    }

    /**
     * Get all busy phases for a staff member in a time range
     *
     * @param string $staffId UUID
     * @param Carbon $startTime
     * @param Carbon $endTime
     * @return Collection<AppointmentPhase>
     */
    public function getStaffBusyPhases(string $staffId, Carbon $startTime, Carbon $endTime): Collection
    {
        return AppointmentPhase::query()
            ->whereHas('appointment', function ($query) use ($staffId) {
                $query->where('staff_id', $staffId)
                      ->whereIn('status', ['scheduled', 'confirmed']);
            })
            ->where('staff_required', true)
            ->where(function ($query) use ($startTime, $endTime) {
                $query->where(function ($q) use ($startTime, $endTime) {
                    $q->whereBetween('start_time', [$startTime, $endTime]);
                })->orWhere(function ($q) use ($startTime, $endTime) {
                    $q->whereBetween('end_time', [$startTime, $endTime]);
                })->orWhere(function ($q) use ($startTime, $endTime) {
                    $q->where('start_time', '<=', $startTime)
                      ->where('end_time', '>=', $endTime);
                });
            })
            ->orderBy('start_time')
            ->get();
    }

    /**
     * Get all available (processing) phases where staff is free
     *
     * @param string $staffId UUID
     * @param Carbon $startTime
     * @param Carbon $endTime
     * @return Collection<AppointmentPhase>
     */
    public function getStaffAvailablePhases(string $staffId, Carbon $startTime, Carbon $endTime): Collection
    {
        return AppointmentPhase::query()
            ->whereHas('appointment', function ($query) use ($staffId) {
                $query->where('staff_id', $staffId)
                      ->whereIn('status', ['scheduled', 'confirmed']);
            })
            ->where('staff_required', false) // Only AVAILABLE phases
            ->where(function ($query) use ($startTime, $endTime) {
                $query->where(function ($q) use ($startTime, $endTime) {
                    $q->whereBetween('start_time', [$startTime, $endTime]);
                })->orWhere(function ($q) use ($startTime, $endTime) {
                    $q->whereBetween('end_time', [$startTime, $endTime]);
                })->orWhere(function ($q) use ($startTime, $endTime) {
                    $q->where('start_time', '<=', $startTime)
                      ->where('end_time', '>=', $endTime);
                });
            })
            ->orderBy('start_time')
            ->get();
    }

    /**
     * Find available time slots for a service on a specific date
     *
     * @param string $staffId UUID
     * @param Carbon $date
     * @param Service $service
     * @param int $intervalMinutes Slot interval (default: 15)
     * @return array<array{start: Carbon, end: Carbon, available: bool}>
     */
    public function findAvailableSlots(
        string $staffId,
        Carbon $date,
        Service $service,
        int $intervalMinutes = 15
    ): array {
        $slots = [];
        $dayStart = $date->copy()->setTime(8, 0); // 8:00 AM
        $dayEnd = $date->copy()->setTime(20, 0);   // 8:00 PM
        $serviceDuration = $service->getTotalDuration();

        $currentTime = $dayStart->copy();

        while ($currentTime->copy()->addMinutes($serviceDuration) <= $dayEnd) {
            $slotEnd = $currentTime->copy()->addMinutes($serviceDuration);

            $available = $this->isStaffAvailable($staffId, $currentTime, $service);

            $slots[] = [
                'start' => $currentTime->copy(),
                'end' => $slotEnd->copy(),
                'available' => $available,
            ];

            $currentTime->addMinutes($intervalMinutes);
        }

        return $slots;
    }

    /**
     * Calculate staff utilization rate (considering processing time availability)
     *
     * @param string $staffId UUID
     * @param Carbon $startTime
     * @param Carbon $endTime
     * @return array{busy_minutes: int, available_minutes: int, total_minutes: int, utilization_rate: float}
     */
    public function calculateStaffUtilization(string $staffId, Carbon $startTime, Carbon $endTime): array
    {
        $busyPhases = $this->getStaffBusyPhases($staffId, $startTime, $endTime);
        $availablePhases = $this->getStaffAvailablePhases($staffId, $startTime, $endTime);

        $busyMinutes = $busyPhases->sum('duration_minutes');
        $availableMinutes = $availablePhases->sum('duration_minutes');
        $totalMinutes = $startTime->diffInMinutes($endTime);

        $utilizationRate = $totalMinutes > 0
            ? ($busyMinutes / $totalMinutes) * 100
            : 0;

        return [
            'busy_minutes' => $busyMinutes,
            'available_minutes' => $availableMinutes,
            'total_minutes' => $totalMinutes,
            'utilization_rate' => round($utilizationRate, 2),
        ];
    }

    /**
     * Get detailed availability breakdown for debugging/reporting
     *
     * @param string $staffId UUID
     * @param Carbon $startTime
     * @param Carbon $endTime
     * @return array
     */
    public function getAvailabilityBreakdown(string $staffId, Carbon $startTime, Carbon $endTime): array
    {
        $busyPhases = $this->getStaffBusyPhases($staffId, $startTime, $endTime);
        $availablePhases = $this->getStaffAvailablePhases($staffId, $startTime, $endTime);

        $breakdown = [
            'staff_id' => $staffId,
            'period' => [
                'start' => $startTime->toIso8601String(),
                'end' => $endTime->toIso8601String(),
            ],
            'busy_phases' => $busyPhases->map(fn($phase) => [
                'appointment_id' => $phase->appointment_id,
                'phase_type' => $phase->phase_type,
                'start' => $phase->start_time->toIso8601String(),
                'end' => $phase->end_time->toIso8601String(),
                'duration_minutes' => $phase->duration_minutes,
            ])->values()->toArray(),
            'available_phases' => $availablePhases->map(fn($phase) => [
                'appointment_id' => $phase->appointment_id,
                'phase_type' => $phase->phase_type,
                'start' => $phase->start_time->toIso8601String(),
                'end' => $phase->end_time->toIso8601String(),
                'duration_minutes' => $phase->duration_minutes,
            ])->values()->toArray(),
            'utilization' => $this->calculateStaffUtilization($staffId, $startTime, $endTime),
        ];

        return $breakdown;
    }

    /**
     * Check if two appointments can be interleaved (one during the other's processing phase)
     *
     * @param Carbon $appointment1Start
     * @param Service $service1
     * @param Carbon $appointment2Start
     * @param Service $service2
     * @return bool
     */
    public function canInterleaveAppointments(
        Carbon $appointment1Start,
        Service $service1,
        Carbon $appointment2Start,
        Service $service2
    ): bool {
        // If neither has processing time, they can't interleave
        if (!$service1->hasProcessingTime() && !$service2->hasProcessingTime()) {
            return false;
        }

        // Generate phases (or virtual phases for non-processing-time services)
        $phases1 = $service1->hasProcessingTime()
            ? $service1->generatePhases($appointment1Start)
            : [[
                'phase_type' => 'regular',
                'staff_required' => true,
                'start_time' => $appointment1Start,
                'end_time' => $appointment1Start->copy()->addMinutes($service1->getTotalDuration()),
            ]];

        $phases2 = $service2->hasProcessingTime()
            ? $service2->generatePhases($appointment2Start)
            : [[
                'phase_type' => 'regular',
                'staff_required' => true,
                'start_time' => $appointment2Start,
                'end_time' => $appointment2Start->copy()->addMinutes($service2->getTotalDuration()),
            ]];

        // Check if any busy phases overlap
        foreach ($phases1 as $phase1) {
            if (!$phase1['staff_required']) {
                continue; // Skip processing phases
            }

            foreach ($phases2 as $phase2) {
                if (!$phase2['staff_required']) {
                    continue; // Skip processing phases
                }

                // Check overlap between busy phases
                if ($this->phasesOverlap($phase1, $phase2)) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Check if two phases overlap
     *
     * @param array $phase1
     * @param array $phase2
     * @return bool
     */
    private function phasesOverlap(array $phase1, array $phase2): bool
    {
        return $phase1['start_time']->lt($phase2['end_time'])
            && $phase1['end_time']->gt($phase2['start_time']);
    }
}
