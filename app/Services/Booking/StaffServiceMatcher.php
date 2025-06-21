<?php

namespace App\Services\Booking;

use App\Models\Branch;
use App\Models\Staff;
use App\Models\StaffEventType;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

/**
 * StaffServiceMatcher - Matches staff members to service requirements
 * 
 * This service handles the complex logic of finding the right staff member
 * based on service requirements, skills, availability, and preferences.
 */
class StaffServiceMatcher
{
    /**
     * Find eligible staff members for a service at a branch
     * 
     * @param Branch $branch
     * @param array $serviceRequirements
     * @return Collection
     */
    public function findEligibleStaff(Branch $branch, array $serviceRequirements): Collection
    {
        Log::info('StaffServiceMatcher: Finding eligible staff', [
            'branch_id' => $branch->id,
            'service_requirements' => $serviceRequirements
        ]);
        
        // Start with all active staff at the branch
        $query = Staff::where('active', true)
            ->where(function ($q) use ($branch) {
                // Staff whose home branch is this branch
                $q->where('home_branch_id', $branch->id)
                    // Or staff who can work at this branch
                    ->orWhereHas('branches', function ($q) use ($branch) {
                        $q->where('branches.id', $branch->id);
                    });
            });
        
        // Filter by service capability
        if (!empty($serviceRequirements['service_id'])) {
            $query->whereHas('eventTypes', function ($q) use ($serviceRequirements) {
                $q->where('calcom_event_types.service_id', $serviceRequirements['service_id'])
                    ->orWhere('calcom_event_types.id', $serviceRequirements['service_id']);
            });
        }
        
        // Get staff with their capabilities loaded
        $staff = $query->with(['eventTypes', 'services', 'branches'])->get();
        
        // Further filter and score based on requirements
        $scoredStaff = $staff->map(function ($member) use ($serviceRequirements) {
            $score = $this->calculateMatchScore($member, $serviceRequirements);
            $member->match_score = $score;
            return $member;
        });
        
        // Filter out staff with score below threshold
        $eligibleStaff = $scoredStaff->filter(function ($member) {
            return $member->match_score >= 0.3; // 30% minimum match
        });
        
        // Sort by match score (highest first)
        $sortedStaff = $eligibleStaff->sortByDesc('match_score')->values();
        
        Log::info('StaffServiceMatcher: Found eligible staff', [
            'total_staff' => $staff->count(),
            'eligible_staff' => $sortedStaff->count(),
            'top_matches' => $sortedStaff->take(3)->map(function ($s) {
                return [
                    'name' => $s->name,
                    'score' => $s->match_score
                ];
            })->toArray()
        ]);
        
        return $sortedStaff;
    }
    
    /**
     * Calculate how well a staff member matches service requirements
     * 
     * @param Staff $staff
     * @param array $serviceRequirements
     * @return float Score between 0 and 1
     */
    public function calculateMatchScore(Staff $staff, array $serviceRequirements): float
    {
        $score = 0.0;
        $weights = [
            'service_match' => 0.4,      // Can perform the service
            'skill_match' => 0.2,        // Has required skills
            'preference_match' => 0.2,   // Matches customer preference
            'experience' => 0.1,         // Experience level
            'availability' => 0.1        // General availability
        ];
        
        // Service match
        if (!empty($serviceRequirements['service_id'])) {
            $hasService = $this->staffProvidesService($staff, $serviceRequirements['service_id']);
            $score += $hasService ? $weights['service_match'] : 0;
        } else {
            // No specific service required, give full score
            $score += $weights['service_match'];
        }
        
        // Skill match
        if (!empty($serviceRequirements['skills_required'])) {
            $skillMatch = $this->calculateSkillMatch($staff, $serviceRequirements['skills_required']);
            $score += $skillMatch * $weights['skill_match'];
        } else {
            $score += $weights['skill_match'];
        }
        
        // Preference match (customer requested specific staff)
        if (!empty($serviceRequirements['staff_preference'])) {
            $preferenceMatch = $this->calculatePreferenceMatch($staff, $serviceRequirements['staff_preference']);
            $score += $preferenceMatch * $weights['preference_match'];
        } else {
            $score += $weights['preference_match'];
        }
        
        // Experience score
        $experienceScore = $this->calculateExperienceScore($staff);
        $score += $experienceScore * $weights['experience'];
        
        // Availability score (based on workload)
        $availabilityScore = $this->calculateAvailabilityScore($staff);
        $score += $availabilityScore * $weights['availability'];
        
        return round($score, 3);
    }
    
    /**
     * Check if staff provides a specific service
     */
    private function staffProvidesService(Staff $staff, $serviceId): bool
    {
        // Check through staff_event_types
        $hasEventType = StaffEventType::where('staff_id', $staff->id)
            ->whereHas('eventType', function ($q) use ($serviceId) {
                $q->where('service_id', $serviceId)
                    ->orWhere('id', $serviceId);
            })
            ->exists();
        
        if ($hasEventType) {
            return true;
        }
        
        // Check direct service assignment (legacy)
        return $staff->services()
            ->where('services.id', $serviceId)
            ->where('services.is_active', true)
            ->exists();
    }
    
    /**
     * Calculate skill match percentage
     */
    private function calculateSkillMatch(Staff $staff, array $requiredSkills): float
    {
        if (empty($requiredSkills)) {
            return 1.0;
        }
        
        $staffSkills = $staff->skills ?? [];
        $matchedSkills = array_intersect($requiredSkills, $staffSkills);
        
        return count($matchedSkills) / count($requiredSkills);
    }
    
    /**
     * Calculate preference match (name matching)
     */
    private function calculatePreferenceMatch(Staff $staff, string $preference): float
    {
        $preference = strtolower(trim($preference));
        $staffName = strtolower($staff->name);
        $firstName = strtolower($staff->first_name ?? '');
        $lastName = strtolower($staff->last_name ?? '');
        
        // Exact match
        if ($preference === $staffName || $preference === $firstName || $preference === $lastName) {
            return 1.0;
        }
        
        // Partial match
        if (str_contains($staffName, $preference) || 
            str_contains($preference, $firstName) ||
            str_contains($preference, $lastName)) {
            return 0.7;
        }
        
        // Similar match (using similar_text)
        similar_text($preference, $staffName, $percent);
        if ($percent > 70) {
            return 0.5;
        }
        
        return 0.0;
    }
    
    /**
     * Calculate experience score based on various factors
     */
    private function calculateExperienceScore(Staff $staff): float
    {
        $score = 0.5; // Base score
        
        // Years of experience
        if ($staff->hired_at) {
            $years = $staff->hired_at->diffInYears(now());
            if ($years >= 5) {
                $score = 1.0;
            } elseif ($years >= 2) {
                $score = 0.8;
            } elseif ($years >= 1) {
                $score = 0.6;
            }
        }
        
        // Completed appointments (if tracked)
        $completedAppointments = $staff->appointments()
            ->where('status', 'completed')
            ->count();
            
        if ($completedAppointments > 1000) {
            $score = min(1.0, $score + 0.2);
        } elseif ($completedAppointments > 500) {
            $score = min(1.0, $score + 0.1);
        }
        
        // Rating (if available)
        if (isset($staff->average_rating) && $staff->average_rating > 0) {
            $ratingBonus = ($staff->average_rating - 3) / 2 * 0.2; // Max 0.2 bonus for 5-star rating
            $score = min(1.0, $score + $ratingBonus);
        }
        
        return $score;
    }
    
    /**
     * Calculate availability score based on current workload
     */
    private function calculateAvailabilityScore(Staff $staff): float
    {
        // Count upcoming appointments in the next 7 days
        $upcomingAppointments = $staff->appointments()
            ->where('status', '!=', 'cancelled')
            ->whereBetween('starts_at', [now(), now()->addDays(7)])
            ->count();
        
        // Assume 8 appointments per day as full capacity
        $dailyCapacity = 8;
        $weeklyCapacity = $dailyCapacity * 5; // 5 working days
        
        $utilizationRate = $upcomingAppointments / $weeklyCapacity;
        
        // Invert utilization to get availability
        if ($utilizationRate >= 1.0) {
            return 0.0; // Fully booked
        } elseif ($utilizationRate >= 0.8) {
            return 0.2; // Very busy
        } elseif ($utilizationRate >= 0.6) {
            return 0.5; // Moderately busy
        } elseif ($utilizationRate >= 0.4) {
            return 0.8; // Good availability
        } else {
            return 1.0; // Excellent availability
        }
    }
    
    /**
     * Get staff capabilities summary
     */
    public function getStaffCapabilities(Staff $staff): array
    {
        return [
            'services' => $staff->services->pluck('name', 'id')->toArray(),
            'event_types' => $staff->eventTypes->pluck('title', 'id')->toArray(),
            'skills' => $staff->skills ?? [],
            'languages' => $staff->languages ?? ['de'],
            'working_branches' => $staff->branches->pluck('name', 'id')->toArray(),
            'specializations' => $staff->specializations ?? [],
            'certifications' => $staff->certifications ?? []
        ];
    }
    
    /**
     * Find staff by direct ID or name
     */
    public function findStaffByIdentifier(string $identifier, ?int $companyId = null): ?Staff
    {
        // Try as ID first
        $staff = Staff::find($identifier);
        if ($staff) {
            return $staff;
        }
        
        // Try by name
        $query = Staff::where(function ($q) use ($identifier) {
            $q->where('name', 'LIKE', '%' . $identifier . '%')
                ->orWhere('first_name', 'LIKE', '%' . $identifier . '%')
                ->orWhere('last_name', 'LIKE', '%' . $identifier . '%');
        });
        
        if ($companyId) {
            $query->where('company_id', $companyId);
        }
        
        return $query->first();
    }
}