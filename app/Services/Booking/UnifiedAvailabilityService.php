<?php

namespace App\Services\Booking;

use App\Models\Staff;
use App\Models\Branch;
use App\Models\Appointment;
use App\Models\WorkingHour;
use App\Services\CalcomV2Service;
use App\Services\CacheService;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

/**
 * UnifiedAvailabilityService - Aggregates availability across multiple sources
 * 
 * This service provides a unified view of availability by combining:
 * - Staff working hours
 * - Existing appointments
 * - Cal.com availability
 * - Break times and holidays
 */
class UnifiedAvailabilityService
{
    private CalcomV2Service $calcomService;
    private CacheService $cacheService;
    
    // Cache TTL in seconds
    const CACHE_TTL = 300; // 5 minutes
    
    public function __construct(
        CalcomV2Service $calcomService,
        CacheService $cacheService
    ) {
        $this->calcomService = $calcomService;
        $this->cacheService = $cacheService;
    }
    
    /**
     * Get available time slots for a staff member
     * 
     * @param Staff $staff
     * @param array $dateRange ['start' => Carbon, 'end' => Carbon]
     * @param int $duration Service duration in minutes
     * @param array $options Additional options (buffer time, etc.)
     * @return array Available time slots
     */
    public function getStaffAvailability(
        Staff $staff,
        array $dateRange,
        int $duration = 30,
        array $options = []
    ): array {
        $cacheKey = $this->buildCacheKey('staff_availability', [
            'staff_id' => $staff->id,
            'start' => $dateRange['start']->format('Y-m-d'),
            'end' => $dateRange['end']->format('Y-m-d'),
            'duration' => $duration
        ]);
        
        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($staff, $dateRange, $duration, $options) {
            Log::info('UnifiedAvailabilityService: Calculating staff availability', [
                'staff_id' => $staff->id,
                'date_range' => [
                    'start' => $dateRange['start']->toDateString(),
                    'end' => $dateRange['end']->toDateString()
                ],
                'duration' => $duration
            ]);
            
            $availableSlots = [];
            
            // Process each day in the range
            $period = CarbonPeriod::create($dateRange['start'], $dateRange['end']);
            
            foreach ($period as $date) {
                $daySlots = $this->getStaffDayAvailability($staff, $date, $duration, $options);
                $availableSlots = array_merge($availableSlots, $daySlots);
            }
            
            return $availableSlots;
        });
    }
    
    /**
     * Get availability for a specific day
     */
    private function getStaffDayAvailability(
        Staff $staff,
        Carbon $date,
        int $duration,
        array $options = []
    ): array {
        // Skip if date is in the past
        if ($date->isPast() && !$date->isToday()) {
            return [];
        }
        
        // Get working hours for this day
        $workingHours = $this->getWorkingHours($staff, $date);
        if (empty($workingHours)) {
            return [];
        }
        
        $slots = [];
        
        foreach ($workingHours as $period) {
            // Generate time slots within working hours
            $periodSlots = $this->generateTimeSlots(
                $period['start'],
                $period['end'],
                $duration,
                $options['slot_interval'] ?? 15 // Default 15-minute intervals
            );
            
            // Filter out booked times
            $availableSlots = $this->filterBookedSlots($staff, $periodSlots, $duration);
            
            // Add to results
            foreach ($availableSlots as $slot) {
                $slots[] = [
                    'start' => $slot['start']->toIso8601String(),
                    'end' => $slot['end']->toIso8601String(),
                    'duration' => $duration,
                    'staff_id' => $staff->id,
                    'staff_name' => $staff->name,
                    'date' => $date->toDateString(),
                    'time' => $slot['start']->format('H:i'),
                    'available' => true
                ];
            }
        }
        
        return $slots;
    }
    
    /**
     * Get working hours for a staff member on a specific date
     */
    private function getWorkingHours(Staff $staff, Carbon $date): array
    {
        $dayOfWeek = strtolower($date->format('l'));
        
        // Check for specific date overrides (holidays, time off)
        if ($this->isStaffUnavailable($staff, $date)) {
            return [];
        }
        
        // Get regular working hours
        $workingHours = WorkingHour::where('staff_id', $staff->id)
            ->where('day_of_week', $dayOfWeek)
            ->where('is_working_day', true)
            ->get();
        
        if ($workingHours->isEmpty()) {
            // Fallback to branch working hours
            $branch = Branch::find($staff->home_branch_id);
            if ($branch && isset($branch->business_hours[$dayOfWeek])) {
                $hours = $branch->business_hours[$dayOfWeek];
                if ($hours['is_open'] ?? false) {
                    return [[
                        'start' => Carbon::parse($date->format('Y-m-d') . ' ' . $hours['open']),
                        'end' => Carbon::parse($date->format('Y-m-d') . ' ' . $hours['close'])
                    ]];
                }
            }
            return [];
        }
        
        // Convert working hours to time periods
        return $workingHours->map(function ($wh) use ($date) {
            return [
                'start' => Carbon::parse($date->format('Y-m-d') . ' ' . $wh->start_time),
                'end' => Carbon::parse($date->format('Y-m-d') . ' ' . $wh->end_time)
            ];
        })->toArray();
    }
    
    /**
     * Generate time slots within a period
     */
    private function generateTimeSlots(
        Carbon $start,
        Carbon $end,
        int $duration,
        int $interval = 15
    ): array {
        $slots = [];
        $current = $start->copy();
        
        // Ensure we don't generate slots in the past
        if ($current->isPast() && $current->isToday()) {
            $current = Carbon::now()->addMinutes($interval - (Carbon::now()->minute % $interval));
            if ($current->lt($start)) {
                $current = $start->copy();
            }
        }
        
        while ($current->copy()->addMinutes($duration)->lte($end)) {
            $slots[] = [
                'start' => $current->copy(),
                'end' => $current->copy()->addMinutes($duration)
            ];
            $current->addMinutes($interval);
        }
        
        return $slots;
    }
    
    /**
     * Filter out already booked time slots
     */
    private function filterBookedSlots(Staff $staff, array $slots, int $bufferMinutes = 0): array
    {
        if (empty($slots)) {
            return [];
        }
        
        // Get the date range for the query
        $startTime = $slots[0]['start']->copy()->subMinutes($bufferMinutes);
        $endTime = end($slots)['end']->copy()->addMinutes($bufferMinutes);
        
        // Get existing appointments
        $appointments = Appointment::where('staff_id', $staff->id)
            ->where('status', '!=', 'cancelled')
            ->whereBetween('starts_at', [$startTime, $endTime])
            ->orWhere(function ($query) use ($startTime, $endTime, $staff) {
                $query->where('staff_id', $staff->id)
                    ->where('status', '!=', 'cancelled')
                    ->whereBetween('ends_at', [$startTime, $endTime]);
            })
            ->get();
        
        // Filter available slots
        return array_filter($slots, function ($slot) use ($appointments, $bufferMinutes) {
            foreach ($appointments as $appointment) {
                $appointmentStart = $appointment->starts_at->copy()->subMinutes($bufferMinutes);
                $appointmentEnd = $appointment->ends_at->copy()->addMinutes($bufferMinutes);
                
                // Check if slot overlaps with appointment (including buffer)
                if ($slot['start']->lt($appointmentEnd) && $slot['end']->gt($appointmentStart)) {
                    return false;
                }
            }
            return true;
        });
    }
    
    /**
     * Check if staff is unavailable on a specific date
     */
    private function isStaffUnavailable(Staff $staff, Carbon $date): bool
    {
        // Check holidays
        if ($staff->holidays && in_array($date->format('Y-m-d'), $staff->holidays)) {
            return true;
        }
        
        // Check time off requests
        if ($staff->timeOffRequests) {
            foreach ($staff->timeOffRequests as $timeOff) {
                if ($timeOff['status'] === 'approved' &&
                    $date->between(
                        Carbon::parse($timeOff['start_date']),
                        Carbon::parse($timeOff['end_date'])
                    )) {
                    return true;
                }
            }
        }
        
        return false;
    }
    
    /**
     * Get availability across multiple branches
     */
    public function getMultiBranchAvailability(
        array $branches,
        array $serviceRequirements,
        array $dateRange,
        int $limit = 50
    ): array {
        $allSlots = [];
        
        foreach ($branches as $branch) {
            $branchSlots = $this->getBranchAvailability($branch, $serviceRequirements, $dateRange);
            
            foreach ($branchSlots as $slot) {
                $slot['branch_id'] = $branch->id;
                $slot['branch_name'] = $branch->name;
                $allSlots[] = $slot;
            }
        }
        
        // Sort by start time
        usort($allSlots, function ($a, $b) {
            return Carbon::parse($a['start'])->timestamp <=> Carbon::parse($b['start'])->timestamp;
        });
        
        // Return limited results
        return array_slice($allSlots, 0, $limit);
    }
    
    /**
     * Get availability for a branch
     */
    private function getBranchAvailability(
        Branch $branch,
        array $serviceRequirements,
        array $dateRange
    ): array {
        // This would aggregate availability across all eligible staff at the branch
        $eligibleStaff = app(StaffServiceMatcher::class)->findEligibleStaff($branch, $serviceRequirements);
        
        $branchSlots = [];
        
        foreach ($eligibleStaff as $staff) {
            $staffSlots = $this->getStaffAvailability(
                $staff,
                $dateRange,
                $serviceRequirements['duration'] ?? 30
            );
            
            $branchSlots = array_merge($branchSlots, $staffSlots);
        }
        
        // Remove duplicates (same time slot from multiple staff)
        return $this->deduplicateSlots($branchSlots);
    }
    
    /**
     * Remove duplicate time slots (keep one per unique start time)
     */
    private function deduplicateSlots(array $slots): array
    {
        $unique = [];
        $seen = [];
        
        foreach ($slots as $slot) {
            $key = $slot['start'] . '-' . $slot['duration'];
            if (!isset($seen[$key])) {
                $seen[$key] = true;
                $unique[] = $slot;
            }
        }
        
        return $unique;
    }
    
    /**
     * Find alternative slots if requested time is not available
     */
    public function findAlternativeSlots(
        Staff $staff,
        Carbon $requestedTime,
        int $duration,
        int $maxAlternatives = 5
    ): array {
        $alternatives = [];
        
        // Search window: +/- 3 days from requested time
        $searchStart = $requestedTime->copy()->subDays(3)->startOfDay();
        $searchEnd = $requestedTime->copy()->addDays(3)->endOfDay();
        
        $allSlots = $this->getStaffAvailability(
            $staff,
            ['start' => $searchStart, 'end' => $searchEnd],
            $duration
        );
        
        // Sort by proximity to requested time
        usort($allSlots, function ($a, $b) use ($requestedTime) {
            $aDiff = abs(Carbon::parse($a['start'])->timestamp - $requestedTime->timestamp);
            $bDiff = abs(Carbon::parse($b['start'])->timestamp - $requestedTime->timestamp);
            return $aDiff <=> $bDiff;
        });
        
        // Return closest alternatives
        return array_slice($allSlots, 0, $maxAlternatives);
    }
    
    /**
     * Reserve a time slot temporarily
     */
    public function reserveSlot(Staff $staff, Carbon $start, Carbon $end): string
    {
        $reservationId = uniqid('reservation_');
        
        // Store reservation in cache for 10 minutes
        Cache::put(
            "slot_reservation:{$reservationId}",
            [
                'staff_id' => $staff->id,
                'start' => $start->toIso8601String(),
                'end' => $end->toIso8601String(),
                'expires_at' => now()->addMinutes(10)
            ],
            600 // 10 minutes
        );
        
        Log::info('Slot reserved', [
            'reservation_id' => $reservationId,
            'staff_id' => $staff->id,
            'slot' => $start->format('Y-m-d H:i')
        ]);
        
        return $reservationId;
    }
    
    /**
     * Check if a slot is still available (considering reservations)
     */
    public function isSlotAvailable(Staff $staff, Carbon $start, Carbon $end): bool
    {
        // Check appointments
        $hasAppointment = Appointment::where('staff_id', $staff->id)
            ->where('status', '!=', 'cancelled')
            ->where(function ($query) use ($start, $end) {
                $query->whereBetween('starts_at', [$start, $end->copy()->subMinute()])
                    ->orWhereBetween('ends_at', [$start->copy()->addMinute(), $end])
                    ->orWhere(function ($q) use ($start, $end) {
                        $q->where('starts_at', '<=', $start)
                            ->where('ends_at', '>=', $end);
                    });
            })
            ->exists();
        
        if ($hasAppointment) {
            return false;
        }
        
        // Check active reservations
        $reservations = Cache::get('slot_reservations:*', []);
        foreach ($reservations as $reservation) {
            if ($reservation['staff_id'] === $staff->id &&
                Carbon::parse($reservation['start'])->eq($start) &&
                Carbon::parse($reservation['expires_at'])->isFuture()) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Build cache key for availability queries
     */
    private function buildCacheKey(string $prefix, array $params): string
    {
        $key = $prefix;
        foreach ($params as $k => $v) {
            $key .= ":{$k}:{$v}";
        }
        return $key;
    }
    
    /**
     * Clear availability cache for a staff member
     */
    public function clearStaffAvailabilityCache(Staff $staff): void
    {
        Cache::tags(['availability', "staff:{$staff->id}"])->flush();
    }
    
    /**
     * Sync availability with Cal.com
     */
    public function syncWithCalcom(Staff $staff, Branch $branch): void
    {
        $config = $branch->getEffectiveCalcomConfig();
        if (!$config || !$config['event_type_id']) {
            return;
        }
        
        try {
            $this->calcomService->setApiKey($config['api_key']);
            
            // Get Cal.com availability
            $calcomAvailability = $this->calcomService->getAvailability(
                $config['event_type_id'],
                now()->format('Y-m-d'),
                now()->addDays(30)->format('Y-m-d')
            );
            
            // Store in cache for quick access
            Cache::put(
                "calcom_availability:{$staff->id}",
                $calcomAvailability,
                3600 // 1 hour
            );
            
        } catch (\Exception $e) {
            Log::error('Failed to sync Cal.com availability', [
                'staff_id' => $staff->id,
                'error' => $e->getMessage()
            ]);
        }
    }
}