<?php

namespace App\Services\Booking;

use App\Models\Branch;
use App\Models\Staff;
use App\Services\CalcomV2Service;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class AlternativeSlotFinder
{
    private StaffSkillMatcher $skillMatcher;
    private UnifiedAvailabilityService $availabilityService;
    
    public function __construct(
        StaffSkillMatcher $skillMatcher,
        UnifiedAvailabilityService $availabilityService
    ) {
        $this->skillMatcher = $skillMatcher;
        $this->availabilityService = $availabilityService;
    }
    
    /**
     * Find alternative slots when requested slot is not available
     */
    public function findAlternatives(array $request): array
    {
        $alternatives = collect();
        
        // Strategy 1: Same staff, different times
        if (isset($request['staff_id'])) {
            $sameStaffAlternatives = $this->findSameStaffAlternatives($request);
            $alternatives = $alternatives->merge($sameStaffAlternatives);
        }
        
        // Strategy 2: Different staff, same time
        $differentStaffAlternatives = $this->findDifferentStaffAlternatives($request);
        $alternatives = $alternatives->merge($differentStaffAlternatives);
        
        // Strategy 3: Different branch, same time
        if ($request['allow_other_branches'] ?? false) {
            $otherBranchAlternatives = $this->findOtherBranchAlternatives($request);
            $alternatives = $alternatives->merge($otherBranchAlternatives);
        }
        
        // Strategy 4: Next available slots
        $nextAvailableAlternatives = $this->findNextAvailableSlots($request);
        $alternatives = $alternatives->merge($nextAvailableAlternatives);
        
        // Deduplicate and sort by score
        $alternatives = $this->deduplicateAndScore($alternatives, $request);
        
        // Limit results
        $limit = $request['max_alternatives'] ?? 5;
        
        return $alternatives->take($limit)->values()->toArray();
    }
    
    /**
     * Find alternatives with same staff at different times
     */
    private function findSameStaffAlternatives(array $request): Collection
    {
        $staff = Staff::find($request['staff_id']);
        if (!$staff) {
            return collect();
        }
        
        $alternatives = collect();
        $requestedTime = Carbon::parse($request['date'] . ' ' . $request['time']);
        $duration = $request['duration'] ?? 30;
        
        // Check +/- 2 hours from requested time
        $searchStart = $requestedTime->copy()->subHours(2);
        $searchEnd = $requestedTime->copy()->addHours(2);
        
        $slots = $this->availabilityService->getStaffAvailability(
            $staff,
            ['start' => $searchStart, 'end' => $searchEnd],
            $duration
        );
        
        foreach ($slots as $slot) {
            $slotTime = Carbon::parse($slot['start']);
            
            // Skip the originally requested time
            if ($slotTime->equalTo($requestedTime)) {
                continue;
            }
            
            $alternatives->push([
                'type' => 'same_staff_different_time',
                'staff_id' => $staff->id,
                'staff_name' => $staff->name,
                'branch_id' => $request['branch_id'],
                'date' => $slotTime->format('Y-m-d'),
                'time' => $slotTime->format('H:i'),
                'duration' => $duration,
                'difference_minutes' => abs($slotTime->diffInMinutes($requestedTime)),
                'description' => $this->formatTimeDifference($slotTime, $requestedTime)
            ]);
        }
        
        // Also check next 3 days
        for ($i = 1; $i <= 3; $i++) {
            $futureDate = $requestedTime->copy()->addDays($i);
            $daySlots = $this->availabilityService->getStaffAvailability(
                $staff,
                [
                    'start' => $futureDate->copy()->setTime(8, 0),
                    'end' => $futureDate->copy()->setTime(20, 0)
                ],
                $duration
            );
            
            // Find closest time to original request
            foreach ($daySlots as $slot) {
                $slotTime = Carbon::parse($slot['start']);
                if ($slotTime->format('H:i') === $requestedTime->format('H:i')) {
                    $alternatives->push([
                        'type' => 'same_staff_different_day',
                        'staff_id' => $staff->id,
                        'staff_name' => $staff->name,
                        'branch_id' => $request['branch_id'],
                        'date' => $slotTime->format('Y-m-d'),
                        'time' => $slotTime->format('H:i'),
                        'duration' => $duration,
                        'difference_days' => $i,
                        'description' => $this->formatDateDifference($slotTime, $requestedTime)
                    ]);
                    break;
                }
            }
        }
        
        return $alternatives;
    }
    
    /**
     * Find alternatives with different staff at same time
     */
    private function findDifferentStaffAlternatives(array $request): Collection
    {
        $branch = Branch::find($request['branch_id']);
        if (!$branch) {
            return collect();
        }
        
        $alternatives = collect();
        $requestedTime = Carbon::parse($request['date'] . ' ' . $request['time']);
        $duration = $request['duration'] ?? 30;
        
        // Find eligible staff
        $requirements = [
            'service_name' => $request['service_name'] ?? null,
            'language' => $request['language'] ?? null,
        ];
        
        $eligibleStaff = $this->skillMatcher->findEligibleStaff($branch, $requirements);
        
        // Exclude originally requested staff
        if (isset($request['staff_id'])) {
            $eligibleStaff = $eligibleStaff->where('id', '!=', $request['staff_id']);
        }
        
        foreach ($eligibleStaff as $staff) {
            // Check if this staff is available at requested time
            $slots = $this->availabilityService->getStaffAvailability(
                $staff,
                ['start' => $requestedTime, 'end' => $requestedTime->copy()->addMinute()],
                $duration
            );
            
            if (!empty($slots)) {
                $alternatives->push([
                    'type' => 'different_staff_same_time',
                    'staff_id' => $staff->id,
                    'staff_name' => $staff->name,
                    'staff_score' => $staff->match_score ?? 0,
                    'branch_id' => $branch->id,
                    'branch_name' => $branch->name,
                    'date' => $requestedTime->format('Y-m-d'),
                    'time' => $requestedTime->format('H:i'),
                    'duration' => $duration,
                    'description' => "Mit {$staff->name} zur gleichen Zeit"
                ]);
            }
        }
        
        return $alternatives;
    }
    
    /**
     * Find alternatives at other branches
     */
    private function findOtherBranchAlternatives(array $request): Collection
    {
        $currentBranch = Branch::find($request['branch_id']);
        if (!$currentBranch) {
            return collect();
        }
        
        $alternatives = collect();
        $requestedTime = Carbon::parse($request['date'] . ' ' . $request['time']);
        $duration = $request['duration'] ?? 30;
        
        // Get other branches of same company
        $otherBranches = Branch::where('company_id', $currentBranch->company_id)
            ->where('id', '!=', $currentBranch->id)
            ->where('active', true)
            ->get();
            
        // If customer location is provided, sort by distance
        if (isset($request['customer_location'])) {
            $otherBranches = $this->sortBranchesByDistance($otherBranches, $request['customer_location']);
        }
        
        foreach ($otherBranches as $branch) {
            // Find eligible staff at this branch
            $requirements = [
                'service_name' => $request['service_name'] ?? null,
                'language' => $request['language'] ?? null,
            ];
            
            $eligibleStaff = $this->skillMatcher->findEligibleStaff($branch, $requirements);
            
            foreach ($eligibleStaff as $staff) {
                $slots = $this->availabilityService->getStaffAvailability(
                    $staff,
                    ['start' => $requestedTime, 'end' => $requestedTime->copy()->addMinute()],
                    $duration
                );
                
                if (!empty($slots)) {
                    $distance = $this->calculateDistance(
                        $currentBranch->coordinates ?? null,
                        $branch->coordinates ?? null
                    );
                    
                    $alternatives->push([
                        'type' => 'different_branch',
                        'staff_id' => $staff->id,
                        'staff_name' => $staff->name,
                        'branch_id' => $branch->id,
                        'branch_name' => $branch->name,
                        'date' => $requestedTime->format('Y-m-d'),
                        'time' => $requestedTime->format('H:i'),
                        'duration' => $duration,
                        'distance_km' => $distance,
                        'description' => "In {$branch->name}" . ($distance ? " ({$distance} km entfernt)" : "")
                    ]);
                    
                    break; // One alternative per branch is enough
                }
            }
        }
        
        return $alternatives;
    }
    
    /**
     * Find next available slots regardless of constraints
     */
    private function findNextAvailableSlots(array $request): Collection
    {
        $branch = Branch::find($request['branch_id']);
        if (!$branch) {
            return collect();
        }
        
        $alternatives = collect();
        $requestedTime = Carbon::parse($request['date'] . ' ' . $request['time']);
        $duration = $request['duration'] ?? 30;
        
        // Search next 7 days
        $searchEnd = $requestedTime->copy()->addDays(7);
        
        $slots = $this->availabilityService->getMultiBranchAvailability(
            [$branch],
            ['service_name' => $request['service_name'] ?? null],
            ['start' => $requestedTime, 'end' => $searchEnd],
            10 // Get up to 10 slots
        );
        
        foreach ($slots as $slot) {
            $slotTime = Carbon::parse($slot['start']);
            
            // Skip if too far in the future
            if ($slotTime->diffInDays($requestedTime) > 7) {
                continue;
            }
            
            $alternatives->push([
                'type' => 'next_available',
                'staff_id' => $slot['staff_id'] ?? null,
                'staff_name' => $slot['staff_name'] ?? 'Beliebiger Mitarbeiter',
                'branch_id' => $slot['branch_id'] ?? $branch->id,
                'branch_name' => $slot['branch_name'] ?? $branch->name,
                'date' => $slotTime->format('Y-m-d'),
                'time' => $slotTime->format('H:i'),
                'duration' => $duration,
                'days_difference' => $slotTime->startOfDay()->diffInDays($requestedTime->startOfDay()),
                'description' => $this->formatNextAvailable($slotTime, $requestedTime)
            ]);
        }
        
        return $alternatives;
    }
    
    /**
     * Deduplicate and score alternatives
     */
    private function deduplicateAndScore(Collection $alternatives, array $request): Collection
    {
        // Remove exact duplicates
        $unique = $alternatives->unique(function ($item) {
            return $item['date'] . $item['time'] . $item['staff_id'] . $item['branch_id'];
        });
        
        // Score each alternative
        $scored = $unique->map(function ($alternative) use ($request) {
            $score = 100; // Base score
            
            // Time difference penalty
            if (isset($alternative['difference_minutes'])) {
                $score -= min(30, $alternative['difference_minutes'] / 10);
            }
            
            // Day difference penalty
            if (isset($alternative['difference_days'])) {
                $score -= $alternative['difference_days'] * 10;
            }
            
            // Different staff penalty (if staff was specifically requested)
            if (isset($request['staff_id']) && $alternative['staff_id'] !== $request['staff_id']) {
                $score -= 20;
            }
            
            // Different branch penalty
            if ($alternative['branch_id'] !== $request['branch_id']) {
                $score -= 15;
                
                // Distance penalty
                if (isset($alternative['distance_km'])) {
                    $score -= min(20, $alternative['distance_km'] * 2);
                }
            }
            
            // Staff skill match bonus
            if (isset($alternative['staff_score'])) {
                $score += $alternative['staff_score'] * 0.3;
            }
            
            // Prefer same day
            if (Carbon::parse($alternative['date'])->isSameDay(Carbon::parse($request['date']))) {
                $score += 10;
            }
            
            $alternative['score'] = max(0, $score);
            return $alternative;
        });
        
        // Sort by score descending
        return $scored->sortByDesc('score');
    }
    
    /**
     * Format time difference for display
     */
    private function formatTimeDifference(Carbon $slotTime, Carbon $requestedTime): string
    {
        if ($slotTime->isSameDay($requestedTime)) {
            $diff = $slotTime->diffInMinutes($requestedTime);
            
            if ($slotTime->gt($requestedTime)) {
                return "{$diff} Minuten später";
            } else {
                return "{$diff} Minuten früher";
            }
        }
        
        return $slotTime->format('d.m.Y H:i') . ' Uhr';
    }
    
    /**
     * Format date difference for display
     */
    private function formatDateDifference(Carbon $slotTime, Carbon $requestedTime): string
    {
        $days = $slotTime->diffInDays($requestedTime);
        
        if ($days === 1) {
            return "Morgen zur gleichen Zeit";
        } elseif ($days === 2) {
            return "Übermorgen zur gleichen Zeit";
        } else {
            return "In {$days} Tagen zur gleichen Zeit";
        }
    }
    
    /**
     * Format next available slot description
     */
    private function formatNextAvailable(Carbon $slotTime, Carbon $requestedTime): string
    {
        if ($slotTime->isToday()) {
            return "Heute um " . $slotTime->format('H:i') . " Uhr";
        } elseif ($slotTime->isTomorrow()) {
            return "Morgen um " . $slotTime->format('H:i') . " Uhr";
        } else {
            $weekday = $this->getGermanWeekday($slotTime);
            return "{$weekday} um " . $slotTime->format('H:i') . " Uhr";
        }
    }
    
    /**
     * Get German weekday name
     */
    private function getGermanWeekday(Carbon $date): string
    {
        $weekdays = [
            'Monday' => 'Montag',
            'Tuesday' => 'Dienstag',
            'Wednesday' => 'Mittwoch',
            'Thursday' => 'Donnerstag',
            'Friday' => 'Freitag',
            'Saturday' => 'Samstag',
            'Sunday' => 'Sonntag'
        ];
        
        return $weekdays[$date->format('l')] ?? $date->format('l');
    }
    
    /**
     * Calculate distance between coordinates
     */
    private function calculateDistance(?array $coords1, ?array $coords2): ?float
    {
        if (!$coords1 || !$coords2) {
            return null;
        }
        
        $lat1 = $coords1['lat'] ?? null;
        $lon1 = $coords1['lon'] ?? null;
        $lat2 = $coords2['lat'] ?? null;
        $lon2 = $coords2['lon'] ?? null;
        
        if (!$lat1 || !$lon1 || !$lat2 || !$lon2) {
            return null;
        }
        
        // Haversine formula
        $earthRadius = 6371; // km
        
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        
        $a = sin($dLat/2) * sin($dLat/2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLon/2) * sin($dLon/2);
             
        $c = 2 * atan2(sqrt($a), sqrt(1-$a));
        $distance = $earthRadius * $c;
        
        return round($distance, 1);
    }
    
    /**
     * Sort branches by distance from location
     */
    private function sortBranchesByDistance(Collection $branches, array $location): Collection
    {
        return $branches->map(function ($branch) use ($location) {
            $branch->distance = $this->calculateDistance($location, $branch->coordinates);
            return $branch;
        })->sortBy('distance');
    }
}