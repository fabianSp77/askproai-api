<?php

namespace App\Services\Booking;

use App\Models\Service;
use App\Models\Staff;
use App\Models\Appointment;
use App\Models\WorkingHour;
use App\Models\Branch;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AvailabilityService
{
    private int $slotDuration = 15; // minutes
    private int $minAdvanceBooking = 2; // hours
    private int $maxAdvanceBooking = 90; // days

    /**
     * Get available slots for a service on a specific date
     */
    public function getAvailableSlots(
        int $serviceId,
        int $branchId,
        Carbon $date,
        ?int $staffId = null,
        string $timezone = 'Europe/Berlin'
    ): Collection {
        // Validate date range
        if (!$this->isDateBookable($date)) {
            return collect();
        }

        $service = Service::with(['staff', 'company'])->find($serviceId);
        if (!$service) {
            return collect();
        }

        $branch = Branch::find($branchId);
        if (!$branch) {
            return collect();
        }

        // Get capable staff
        $staff = $this->getCapableStaff($service, $branch, $staffId);
        if ($staff->isEmpty()) {
            return collect();
        }

        // Generate slots for each staff member
        $allSlots = collect();

        foreach ($staff as $staffMember) {
            $slots = $this->generateStaffSlots(
                $staffMember,
                $service,
                $date,
                $timezone
            );

            $allSlots = $allSlots->merge($slots);
        }

        // Sort by time and remove duplicates
        return $allSlots
            ->unique(function ($slot) {
                return $slot['start'] . '-' . $slot['staff_id'];
            })
            ->sortBy('start')
            ->values();
    }

    /**
     * Generate time slots for a specific staff member
     */
    protected function generateStaffSlots(
        Staff $staff,
        Service $service,
        Carbon $date,
        string $timezone
    ): Collection {
        $slots = collect();
        $dayOfWeek = $date->dayOfWeek;

        // Get working hours for this day
        $workingHours = WorkingHour::where('staff_id', $staff->id)
            ->where('is_active', true)
            ->where(function ($query) use ($dayOfWeek) {
                $query->where('day_of_week', $dayOfWeek)
                    ->orWhere('weekday', $dayOfWeek);
            })
            ->first();

        if (!$workingHours) {
            return $slots;
        }

        // Parse working hours
        $workStart = Carbon::parse($date->format('Y-m-d') . ' ' . $workingHours->start);
        $workEnd = Carbon::parse($date->format('Y-m-d') . ' ' . $workingHours->end);

        // Get existing appointments for this staff member
        $existingAppointments = $this->getExistingAppointments($staff->id, $date);

        // Generate time slots
        $currentSlot = $workStart->copy();
        $serviceDuration = $service->duration_minutes;
        $bufferTime = $service->buffer_time_minutes ?? 0;
        $totalDuration = $serviceDuration + $bufferTime;

        while ($currentSlot->copy()->addMinutes($totalDuration)->lte($workEnd)) {
            $slotEnd = $currentSlot->copy()->addMinutes($serviceDuration);

            // Check if slot is available
            if ($this->isSlotAvailable(
                $currentSlot,
                $slotEnd,
                $existingAppointments,
                $staff->id
            )) {
                // Check if slot is in the future
                if ($currentSlot->gt(now()->addMinutes($this->minAdvanceBooking))) {
                    $slots->push([
                        'start' => $currentSlot->format('Y-m-d H:i:s'),
                        'end' => $slotEnd->format('Y-m-d H:i:s'),
                        'display_time' => $currentSlot->format('H:i'),
                        'staff_id' => $staff->id,
                        'staff_name' => $staff->name,
                        'available' => true,
                        'price' => $service->price,
                    ]);
                }
            }

            // Move to next slot
            $currentSlot->addMinutes($this->slotDuration);
        }

        return $slots;
    }

    /**
     * Check if a specific time slot is available
     */
    protected function isSlotAvailable(
        Carbon $start,
        Carbon $end,
        Collection $appointments,
        int $staffId
    ): bool {
        // Check for overlapping appointments
        foreach ($appointments as $appointment) {
            $appStart = Carbon::parse($appointment->starts_at);
            $appEnd = Carbon::parse($appointment->ends_at);

            // Check for overlap
            if ($start->lt($appEnd) && $end->gt($appStart)) {
                return false;
            }

            // Check buffer time
            if ($appointment->service && $appointment->service->buffer_time_minutes) {
                $bufferEnd = $appEnd->copy()->addMinutes($appointment->service->buffer_time_minutes);
                if ($start->lt($bufferEnd)) {
                    return false;
                }
            }
        }

        // Check if slot is locked
        if ($this->isSlotLocked($staffId, $start, $end)) {
            return false;
        }

        return true;
    }

    /**
     * Get capable staff for a service
     */
    protected function getCapableStaff(Service $service, Branch $branch, ?int $staffId = null): Collection
    {
        $query = Staff::query()
            ->where('branch_id', $branch->id)
            ->where('is_active', true)
            ->whereHas('services', function ($q) use ($service) {
                $q->where('service_id', $service->id);
            });

        if ($staffId) {
            $query->where('id', $staffId);
        }

        return $query->get();
    }

    /**
     * Get existing appointments for a staff member on a date
     */
    protected function getExistingAppointments(int $staffId, Carbon $date): Collection
    {
        $cacheKey = "appointments-{$staffId}-{$date->format('Y-m-d')}";

        return Cache::remember($cacheKey, 300, function () use ($staffId, $date) { // 5 minutes
            return Appointment::with('service')
                ->where('staff_id', $staffId)
                ->whereDate('starts_at', $date)
                ->whereNotIn('status', ['cancelled', 'no_show'])
                ->orderBy('starts_at')
                ->get();
        });
    }

    /**
     * Check if a slot is locked
     */
    protected function isSlotLocked(int $staffId, Carbon $start, Carbon $end): bool
    {
        $lockService = app(BookingLockService::class);
        return $lockService->isLocked((string)$staffId, $start, $end);
    }

    /**
     * Check if a date is bookable
     */
    protected function isDateBookable(Carbon $date): bool
    {
        // Can't book in the past
        if ($date->lt(today())) {
            return false;
        }

        // Can't book too far in advance
        if ($date->gt(today()->addDays($this->maxAdvanceBooking))) {
            return false;
        }

        // TODO: Check for holidays, blocked dates, etc.

        return true;
    }

    /**
     * Find next available slot for a service
     */
    public function findNextAvailableSlot(
        int $serviceId,
        int $branchId,
        ?int $staffId = null
    ): ?array {
        $date = today();
        $maxDays = 30;
        $daysChecked = 0;

        while ($daysChecked < $maxDays) {
            $slots = $this->getAvailableSlots(
                $serviceId,
                $branchId,
                $date,
                $staffId
            );

            if ($slots->isNotEmpty()) {
                return $slots->first();
            }

            $date->addDay();
            $daysChecked++;
        }

        return null;
    }

    /**
     * Check availability for composite service
     */
    public function checkCompositeAvailability(
        Service $service,
        Carbon $startDate,
        Carbon $endDate,
        int $branchId
    ): Collection {
        if (!$service->isComposite()) {
            throw new \InvalidArgumentException('Service is not composite');
        }

        $segments = $service->getSegments();
        $availableCompositeSlots = collect();

        // For each day in range
        $period = CarbonPeriod::create($startDate, $endDate);

        foreach ($period as $date) {
            // Find slots for first segment
            $firstSegmentSlots = $this->getAvailableSlots(
                $service->id,
                $branchId,
                $date
            );

            foreach ($firstSegmentSlots as $firstSlot) {
                // Calculate pause window
                $pauseMin = $segments[0]['gapAfterMin'] ?? 30;
                $pauseMax = $segments[0]['gapAfterMax'] ?? 60;

                $secondSegmentStart = Carbon::parse($firstSlot['end'])
                    ->addMinutes($pauseMin);
                $secondSegmentEnd = Carbon::parse($firstSlot['end'])
                    ->addMinutes($pauseMax);

                // Find matching second segment slots
                // TODO: Implement second segment matching logic

                $availableCompositeSlots->push([
                    'date' => $date->format('Y-m-d'),
                    'segment1' => $firstSlot,
                    'segment2' => null, // To be implemented
                    'total_duration' => $service->duration_minutes,
                ]);
            }
        }

        return $availableCompositeSlots;
    }

    /**
     * Calculate slot statistics
     */
    public function getSlotStatistics(int $branchId, Carbon $date): array
    {
        $stats = Cache::remember("slot-stats-{$branchId}-{$date->format('Y-m-d')}", 1800, function () use ($branchId, $date) { // 30 minutes
            $totalSlots = 0;
            $bookedSlots = 0;
            $availableSlots = 0;

            // Get all staff for branch
            $staff = Staff::where('branch_id', $branchId)
                ->where('is_active', true)
                ->get();

            foreach ($staff as $staffMember) {
                // Get working hours
                $workingHours = WorkingHour::where('staff_id', $staffMember->id)
                    ->where('is_active', true)
                    ->where(function ($query) use ($date) {
                        $query->where('day_of_week', $date->dayOfWeek)
                            ->orWhere('weekday', $date->dayOfWeek);
                    })
                    ->first();

                if ($workingHours) {
                    $workStart = Carbon::parse($date->format('Y-m-d') . ' ' . $workingHours->start);
                    $workEnd = Carbon::parse($date->format('Y-m-d') . ' ' . $workingHours->end);
                    $workMinutes = $workStart->diffInMinutes($workEnd);

                    // Count total possible slots
                    $slotsPerHour = 60 / $this->slotDuration;
                    $totalSlots += ($workMinutes / 60) * $slotsPerHour;

                    // Count booked time
                    $bookedMinutes = Appointment::where('staff_id', $staffMember->id)
                        ->whereDate('starts_at', $date)
                        ->whereNotIn('status', ['cancelled', 'no_show'])
                        ->sum(DB::raw('TIMESTAMPDIFF(MINUTE, starts_at, ends_at)'));

                    $bookedSlots += $bookedMinutes / $this->slotDuration;
                }
            }

            $availableSlots = $totalSlots - $bookedSlots;
            $utilizationRate = $totalSlots > 0 ? ($bookedSlots / $totalSlots) * 100 : 0;

            return [
                'total_slots' => (int) $totalSlots,
                'booked_slots' => (int) $bookedSlots,
                'available_slots' => (int) $availableSlots,
                'utilization_rate' => round($utilizationRate, 1),
                'date' => $date->format('Y-m-d'),
                'branch_id' => $branchId,
            ];
        });

        return $stats;
    }

    /**
     * Get availability heatmap for a month
     */
    public function getAvailabilityHeatmap(int $branchId, Carbon $month): array
    {
        $heatmap = [];
        $startOfMonth = $month->copy()->startOfMonth();
        $endOfMonth = $month->copy()->endOfMonth();

        $period = CarbonPeriod::create($startOfMonth, $endOfMonth);

        foreach ($period as $date) {
            $stats = $this->getSlotStatistics($branchId, $date);

            $heatmap[$date->format('Y-m-d')] = [
                'date' => $date->format('Y-m-d'),
                'day_name' => $date->format('l'),
                'utilization' => $stats['utilization_rate'],
                'available' => $stats['available_slots'],
                'level' => $this->getUtilizationLevel($stats['utilization_rate']),
            ];
        }

        return $heatmap;
    }

    /**
     * Get utilization level for heatmap coloring
     */
    protected function getUtilizationLevel(float $utilization): string
    {
        if ($utilization >= 90) return 'full';
        if ($utilization >= 70) return 'busy';
        if ($utilization >= 50) return 'moderate';
        if ($utilization >= 30) return 'light';
        return 'empty';
    }

    /**
     * Optimize slot suggestions based on various factors
     */
    public function getOptimizedSlotSuggestions(
        int $serviceId,
        int $branchId,
        Carbon $preferredDate,
        ?string $preferredTime = null
    ): Collection {
        $suggestions = collect();
        $service = Service::find($serviceId);

        if (!$service) {
            return $suggestions;
        }

        // Get slots for preferred date and surrounding days
        $dates = [
            $preferredDate->copy()->subDay(),
            $preferredDate,
            $preferredDate->copy()->addDay(),
        ];

        foreach ($dates as $date) {
            if (!$this->isDateBookable($date)) {
                continue;
            }

            $slots = $this->getAvailableSlots($serviceId, $branchId, $date);

            // Score each slot
            foreach ($slots as $slot) {
                $score = $this->calculateSlotScore($slot, $preferredDate, $preferredTime);

                $suggestions->push(array_merge($slot, [
                    'score' => $score,
                    'date' => $date->format('Y-m-d'),
                    'recommendation_reason' => $this->getRecommendationReason($slot, $score),
                ]));
            }
        }

        // Sort by score and return top suggestions
        return $suggestions
            ->sortByDesc('score')
            ->take(5)
            ->values();
    }

    /**
     * Calculate score for slot recommendation
     */
    protected function calculateSlotScore(array $slot, Carbon $preferredDate, ?string $preferredTime): float
    {
        $score = 100.0;

        // Penalty for different date
        $slotDate = Carbon::parse($slot['start'])->startOfDay();
        $dayDiff = $preferredDate->startOfDay()->diffInDays($slotDate);
        $score -= $dayDiff * 10;

        // Bonus for preferred time proximity
        if ($preferredTime) {
            $slotTime = Carbon::parse($slot['start'])->format('H:i');
            $timeDiff = abs(strtotime($slotTime) - strtotime($preferredTime)) / 3600;
            $score -= $timeDiff * 5;
        }

        // Bonus for less busy times
        $hour = (int) Carbon::parse($slot['start'])->format('H');
        if ($hour < 10 || $hour > 16) {
            $score += 5; // Off-peak bonus
        }

        // Random small variation to avoid ties
        $score += rand(0, 5) / 10;

        return max(0, $score);
    }

    /**
     * Get recommendation reason for a slot
     */
    protected function getRecommendationReason(array $slot, float $score): string
    {
        if ($score >= 95) {
            return 'Perfekte Übereinstimmung mit Ihren Präferenzen';
        } elseif ($score >= 85) {
            return 'Sehr gute Verfügbarkeit';
        } elseif ($score >= 75) {
            return 'Gute Alternative';
        } else {
            return 'Verfügbar';
        }
    }
}