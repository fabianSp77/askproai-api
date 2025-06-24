<?php

namespace App\Services\RealTime;

use App\Models\Call;
use App\Models\Staff;
use App\Models\Customer;
use App\Models\Service;
use App\Models\Branch;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

/**
 * Intelligent call routing system with ML-like scoring and predictive routing
 */
class IntelligentCallRouter
{
    // Routing strategies
    const STRATEGY_SKILL_BASED = 'skill_based';
    const STRATEGY_LEAST_BUSY = 'least_busy';
    const STRATEGY_ROUND_ROBIN = 'round_robin';
    const STRATEGY_PERFORMANCE_BASED = 'performance_based';
    const STRATEGY_LANGUAGE_PRIORITY = 'language_priority';
    
    // Weight configuration for scoring
    private array $scoringWeights = [
        'skill_match' => 0.25,
        'language_match' => 0.20,
        'availability' => 0.20,
        'performance' => 0.15,
        'customer_history' => 0.10,
        'workload_balance' => 0.10,
    ];
    
    /**
     * Route call to optimal staff member
     */
    public function routeCall(Call $call, Branch $branch, array $context = []): ?Staff
    {
        $startTime = microtime(true);
        
        try {
            // Extract routing requirements
            $requirements = $this->extractRoutingRequirements($call, $context);
            
            // Get eligible staff
            $eligibleStaff = $this->getEligibleStaff($branch, $requirements);
            
            if ($eligibleStaff->isEmpty()) {
                Log::warning('No eligible staff found for call routing', [
                    'call_id' => $call->id,
                    'branch_id' => $branch->id,
                ]);
                return null;
            }
            
            // Score and rank staff
            $scoredStaff = $this->scoreStaff($eligibleStaff, $requirements, $call);
            
            // Select best match
            $selectedStaff = $this->selectOptimalStaff($scoredStaff, $requirements);
            
            $routingTime = (microtime(true) - $startTime) * 1000;
            
            Log::info('Call routed successfully', [
                'call_id' => $call->id,
                'staff_id' => $selectedStaff->id,
                'score' => $selectedStaff->routing_score,
                'routing_time_ms' => $routingTime,
            ]);
            
            return $selectedStaff;
            
        } catch (\Exception $e) {
            Log::error('Call routing failed', [
                'call_id' => $call->id,
                'error' => $e->getMessage(),
            ]);
            
            // Fallback to any available staff
            return $this->getFallbackStaff($branch);
        }
    }
    
    /**
     * Extract routing requirements from call and context
     */
    private function extractRoutingRequirements(Call $call, array $context): array
    {
        $requirements = [
            'services' => [],
            'languages' => [],
            'skills' => [],
            'preferences' => [],
            'strategy' => self::STRATEGY_SKILL_BASED,
        ];
        
        // Extract from call metadata
        if ($call->metadata) {
            $requirements['services'] = $call->metadata['requested_services'] ?? [];
            $requirements['languages'] = $call->metadata['detected_languages'] ?? [];
        }
        
        // Extract from context
        if (!empty($context['service_name'])) {
            $service = Service::where('name', 'LIKE', '%' . $context['service_name'] . '%')->first();
            if ($service) {
                $requirements['services'][] = $service->id;
                $requirements['skills'] = array_merge($requirements['skills'], $service->required_skills ?? []);
            }
        }
        
        // Get customer preferences
        if ($call->customer_id) {
            $customer = Customer::find($call->customer_id);
            if ($customer) {
                $requirements['preferences'] = [
                    'preferred_staff' => $customer->preferred_staff_id,
                    'preferred_language' => $customer->preferred_language,
                    'avoid_staff' => $customer->avoid_staff_ids ?? [],
                ];
                
                // Add language preference
                if ($customer->preferred_language) {
                    array_unshift($requirements['languages'], $customer->preferred_language);
                }
            }
        }
        
        // Determine routing strategy based on context
        if (!empty($requirements['languages'])) {
            $requirements['strategy'] = self::STRATEGY_LANGUAGE_PRIORITY;
        } elseif (!empty($requirements['skills'])) {
            $requirements['strategy'] = self::STRATEGY_SKILL_BASED;
        }
        
        return $requirements;
    }
    
    /**
     * Get eligible staff based on requirements
     */
    private function getEligibleStaff(Branch $branch, array $requirements): Collection
    {
        $query = Staff::where('branch_id', $branch->id)
            ->where('is_active', true)
            ->where('is_available', true);
        
        // Filter by working hours
        $now = Carbon::now();
        $dayOfWeek = strtolower($now->format('l'));
        $currentTime = $now->format('H:i');
        
        $query->where(function($q) use ($dayOfWeek, $currentTime) {
            $q->whereJsonContains("working_hours->{$dayOfWeek}->is_working", true)
              ->where(function($sq) use ($dayOfWeek, $currentTime) {
                  // Validate dayOfWeek against whitelist
                  $validDays = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
                  if (!in_array($dayOfWeek, $validDays)) {
                      throw new \InvalidArgumentException("Invalid day of week: " . $dayOfWeek);
                  }
                  
                  $sq->whereRaw("JSON_EXTRACT(working_hours, ?) <= ?", ["$." . $dayOfWeek . ".start", $currentTime])
                     ->whereRaw("JSON_EXTRACT(working_hours, ?) >= ?", ["$." . $dayOfWeek . ".end", $currentTime]);
              });
        });
        
        // Exclude avoided staff
        if (!empty($requirements['preferences']['avoid_staff'])) {
            $query->whereNotIn('id', $requirements['preferences']['avoid_staff']);
        }
        
        // Get staff with relationships
        return $query->with([
            'currentCalls' => function($q) {
                $q->where('status', 'in_progress');
            },
            'todayAppointments' => function($q) {
                $q->whereDate('starts_at', today())
                  ->where('status', '!=', 'cancelled');
            }
        ])->get();
    }
    
    /**
     * Score staff based on multiple factors
     */
    private function scoreStaff(Collection $eligibleStaff, array $requirements, Call $call): Collection
    {
        return $eligibleStaff->map(function($staff) use ($requirements, $call) {
            $scores = [];
            
            // Skill match score
            $scores['skill_match'] = $this->calculateSkillMatchScore($staff, $requirements['skills']);
            
            // Language match score
            $scores['language_match'] = $this->calculateLanguageMatchScore($staff, $requirements['languages']);
            
            // Availability score
            $scores['availability'] = $this->calculateAvailabilityScore($staff);
            
            // Performance score
            $scores['performance'] = $this->calculatePerformanceScore($staff);
            
            // Customer history score
            $scores['customer_history'] = $this->calculateCustomerHistoryScore($staff, $call);
            
            // Workload balance score
            $scores['workload_balance'] = $this->calculateWorkloadBalanceScore($staff);
            
            // Calculate weighted total
            $totalScore = 0;
            foreach ($scores as $factor => $score) {
                $totalScore += $score * ($this->scoringWeights[$factor] ?? 0);
            }
            
            // Apply preference bonus
            if ($staff->id === ($requirements['preferences']['preferred_staff'] ?? null)) {
                $totalScore *= 1.5; // 50% bonus for preferred staff
            }
            
            $staff->routing_score = round($totalScore, 2);
            $staff->routing_scores = $scores;
            
            return $staff;
        })->sortByDesc('routing_score');
    }
    
    /**
     * Calculate skill match score
     */
    private function calculateSkillMatchScore(Staff $staff, array $requiredSkills): float
    {
        if (empty($requiredSkills)) {
            return 1.0; // No specific skills required
        }
        
        $staffSkills = $staff->skills ?? [];
        if (empty($staffSkills)) {
            return 0.0;
        }
        
        $matchCount = count(array_intersect($requiredSkills, $staffSkills));
        $score = $matchCount / count($requiredSkills);
        
        // Bonus for additional relevant skills
        $extraSkills = count($staffSkills) - $matchCount;
        if ($extraSkills > 0) {
            $score += min($extraSkills * 0.05, 0.2); // Up to 20% bonus
        }
        
        return min($score, 1.0);
    }
    
    /**
     * Calculate language match score
     */
    private function calculateLanguageMatchScore(Staff $staff, array $requiredLanguages): float
    {
        if (empty($requiredLanguages)) {
            return 1.0; // No specific language required
        }
        
        $staffLanguages = $staff->languages ?? ['de']; // Default to German
        
        // Check for exact matches
        foreach ($requiredLanguages as $index => $language) {
            if (in_array($language, $staffLanguages)) {
                // Higher score for primary language (first in array)
                return 1.0 - ($index * 0.1);
            }
        }
        
        // Check for related languages (e.g., dialect variations)
        $languageFamilies = [
            'de' => ['de', 'de-DE', 'de-AT', 'de-CH'],
            'en' => ['en', 'en-US', 'en-GB', 'en-AU'],
            'tr' => ['tr', 'tr-TR'],
        ];
        
        foreach ($requiredLanguages as $required) {
            foreach ($languageFamilies as $family) {
                if (in_array($required, $family)) {
                    foreach ($staffLanguages as $staffLang) {
                        if (in_array($staffLang, $family)) {
                            return 0.8; // Related language match
                        }
                    }
                }
            }
        }
        
        return 0.0; // No language match
    }
    
    /**
     * Calculate availability score based on current workload
     */
    private function calculateAvailabilityScore(Staff $staff): float
    {
        // Check current calls
        $currentCalls = $staff->currentCalls->count();
        if ($currentCalls > 0) {
            return 0.0; // Already on a call
        }
        
        // Check upcoming appointments
        $now = Carbon::now();
        $nextAppointment = $staff->todayAppointments
            ->where('starts_at', '>', $now)
            ->sortBy('starts_at')
            ->first();
        
        if ($nextAppointment) {
            $minutesUntilNext = $now->diffInMinutes($nextAppointment->starts_at);
            
            if ($minutesUntilNext < 15) {
                return 0.1; // Very limited availability
            } elseif ($minutesUntilNext < 30) {
                return 0.5; // Some availability
            } elseif ($minutesUntilNext < 60) {
                return 0.8; // Good availability
            }
        }
        
        return 1.0; // Fully available
    }
    
    /**
     * Calculate performance score based on metrics
     */
    private function calculatePerformanceScore(Staff $staff): float
    {
        $cacheKey = "staff_performance:{$staff->id}";
        
        return Cache::remember($cacheKey, 3600, function() use ($staff) {
            $metrics = [
                'satisfaction' => $this->getStaffSatisfactionScore($staff),
                'efficiency' => $this->getStaffEfficiencyScore($staff),
                'reliability' => $this->getStaffReliabilityScore($staff),
            ];
            
            // Weighted average
            return ($metrics['satisfaction'] * 0.5) + 
                   ($metrics['efficiency'] * 0.3) + 
                   ($metrics['reliability'] * 0.2);
        });
    }
    
    /**
     * Calculate customer history score
     */
    private function calculateCustomerHistoryScore(Staff $staff, Call $call): float
    {
        if (!$call->customer_id) {
            return 0.5; // Neutral score for unknown customers
        }
        
        // Check previous interactions
        $previousInteractions = Call::where('customer_id', $call->customer_id)
            ->where('staff_id', $staff->id)
            ->where('status', 'completed')
            ->count();
        
        if ($previousInteractions === 0) {
            return 0.5; // No history
        }
        
        // Check satisfaction from previous interactions
        $avgSatisfaction = Call::where('customer_id', $call->customer_id)
            ->where('staff_id', $staff->id)
            ->where('status', 'completed')
            ->whereNotNull('customer_satisfaction')
            ->avg('customer_satisfaction');
        
        if ($avgSatisfaction !== null) {
            return $avgSatisfaction / 5; // Normalize to 0-1
        }
        
        // Positive score for having history
        return 0.7 + min($previousInteractions * 0.05, 0.3);
    }
    
    /**
     * Calculate workload balance score
     */
    private function calculateWorkloadBalanceScore(Staff $staff): float
    {
        // Get today's workload
        $todayCallCount = Call::where('staff_id', $staff->id)
            ->whereDate('created_at', today())
            ->count();
        
        $todayAppointmentCount = $staff->todayAppointments->count();
        
        $totalWorkload = $todayCallCount + $todayAppointmentCount;
        
        // Inverse scoring - less workload = higher score
        if ($totalWorkload === 0) {
            return 1.0;
        } elseif ($totalWorkload < 5) {
            return 0.9;
        } elseif ($totalWorkload < 10) {
            return 0.7;
        } elseif ($totalWorkload < 15) {
            return 0.5;
        } elseif ($totalWorkload < 20) {
            return 0.3;
        } else {
            return 0.1;
        }
    }
    
    /**
     * Select optimal staff based on strategy
     */
    private function selectOptimalStaff(Collection $scoredStaff, array $requirements): ?Staff
    {
        if ($scoredStaff->isEmpty()) {
            return null;
        }
        
        switch ($requirements['strategy']) {
            case self::STRATEGY_LANGUAGE_PRIORITY:
                // Filter by language first, then by score
                $withLanguage = $scoredStaff->filter(function($staff) use ($requirements) {
                    return $staff->routing_scores['language_match'] > 0.5;
                });
                
                return $withLanguage->isNotEmpty() ? $withLanguage->first() : $scoredStaff->first();
                
            case self::STRATEGY_LEAST_BUSY:
                // Sort by workload balance score
                return $scoredStaff->sortByDesc(function($staff) {
                    return $staff->routing_scores['workload_balance'];
                })->first();
                
            case self::STRATEGY_PERFORMANCE_BASED:
                // Sort by performance score
                return $scoredStaff->sortByDesc(function($staff) {
                    return $staff->routing_scores['performance'];
                })->first();
                
            case self::STRATEGY_ROUND_ROBIN:
                // Get least recently used staff
                return $this->roundRobinSelection($scoredStaff);
                
            case self::STRATEGY_SKILL_BASED:
            default:
                // Use overall score (default behavior)
                return $scoredStaff->first();
        }
    }
    
    /**
     * Round-robin selection
     */
    private function roundRobinSelection(Collection $staff): ?Staff
    {
        $lastAssignments = [];
        
        foreach ($staff as $s) {
            $lastCall = Call::where('staff_id', $s->id)
                ->latest()
                ->first();
                
            $lastAssignments[$s->id] = $lastCall ? $lastCall->created_at->timestamp : 0;
        }
        
        // Sort by least recent assignment
        asort($lastAssignments);
        
        $selectedId = array_key_first($lastAssignments);
        return $staff->find($selectedId);
    }
    
    /**
     * Get fallback staff when routing fails
     */
    private function getFallbackStaff(Branch $branch): ?Staff
    {
        return Staff::where('branch_id', $branch->id)
            ->where('is_active', true)
            ->where('is_available', true)
            ->whereDoesntHave('currentCalls')
            ->first();
    }
    
    /**
     * Get staff satisfaction score
     */
    private function getStaffSatisfactionScore(Staff $staff): float
    {
        $recentCalls = Call::where('staff_id', $staff->id)
            ->where('created_at', '>', now()->subDays(30))
            ->whereNotNull('customer_satisfaction')
            ->get();
        
        if ($recentCalls->isEmpty()) {
            return 0.7; // Default score
        }
        
        return $recentCalls->avg('customer_satisfaction') / 5;
    }
    
    /**
     * Get staff efficiency score
     */
    private function getStaffEfficiencyScore(Staff $staff): float
    {
        $avgDuration = Call::where('staff_id', $staff->id)
            ->where('created_at', '>', now()->subDays(30))
            ->whereNotNull('duration_minutes')
            ->avg('duration_minutes');
        
        if (!$avgDuration) {
            return 0.7; // Default score
        }
        
        // Optimal call duration is 5-10 minutes
        if ($avgDuration >= 5 && $avgDuration <= 10) {
            return 1.0;
        } elseif ($avgDuration < 5) {
            return 0.8; // Too short, might miss details
        } elseif ($avgDuration <= 15) {
            return 0.7;
        } else {
            return 0.5; // Too long
        }
    }
    
    /**
     * Get staff reliability score
     */
    private function getStaffReliabilityScore(Staff $staff): float
    {
        $totalAppointments = $staff->appointments()
            ->where('created_at', '>', now()->subDays(30))
            ->count();
        
        if ($totalAppointments === 0) {
            return 0.8; // Default score
        }
        
        $completedOnTime = $staff->appointments()
            ->where('created_at', '>', now()->subDays(30))
            ->where('status', 'completed')
            ->whereRaw('TIMESTAMPDIFF(MINUTE, starts_at, actual_start_at) < 15')
            ->count();
        
        return $completedOnTime / $totalAppointments;
    }
    
    /**
     * Update routing weights dynamically
     */
    public function updateScoringWeights(array $weights): void
    {
        foreach ($weights as $factor => $weight) {
            if (isset($this->scoringWeights[$factor])) {
                $this->scoringWeights[$factor] = max(0, min(1, $weight));
            }
        }
        
        // Normalize weights to sum to 1
        $total = array_sum($this->scoringWeights);
        if ($total > 0) {
            foreach ($this->scoringWeights as &$weight) {
                $weight = $weight / $total;
            }
        }
    }
}