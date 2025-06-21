<?php

namespace App\Services\Booking;

use App\Models\Staff;
use App\Models\Service;
use App\Models\Branch;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class StaffSkillMatcher
{
    /**
     * Performance metrics storage
     */
    private static array $performanceMetrics = [];
    /**
     * Find eligible staff for a service at a branch
     */
    public function findEligibleStaff(Branch $branch, array $requirements): Collection
    {
        $startTime = microtime(true);
        
        $query = Staff::query()
            ->where('active', true)
            ->where('is_bookable', true);
            
        // Filter by branch
        if ($branch) {
            $query->where(function ($q) use ($branch) {
                $q->where('home_branch_id', $branch->id)
                  ->orWhereHas('branches', function ($bq) use ($branch) {
                      $bq->where('branches.id', $branch->id);
                  });
            });
        }
        
        // Get all potential staff - Track query time
        $queryStart = microtime(true);
        $potentialStaff = $query->with(['services', 'eventTypes'])->get();
        $queryDuration = (microtime(true) - $queryStart) * 1000; // Convert to milliseconds
        
        // Log slow queries
        if ($queryDuration > 200) {
            Log::warning('Slow query in StaffSkillMatcher::findEligibleStaff', [
                'duration_ms' => round($queryDuration, 2),
                'query' => $query->toSql(),
                'bindings' => $query->getBindings(),
                'branch_id' => $branch->id,
                'staff_count' => $potentialStaff->count()
            ]);
        }
        
        // Score and filter staff
        $scoredStaff = $potentialStaff->map(function ($staff) use ($requirements) {
            $score = $this->calculateStaffScore($staff, $requirements);
            
            if ($score > 0) {
                $staff->match_score = $score;
                return $staff;
            }
            
            return null;
        })->filter()->sortByDesc('match_score');
        
        // Calculate total execution time
        $totalDuration = (microtime(true) - $startTime) * 1000;
        
        Log::info('Staff skill matching completed', [
            'branch_id' => $branch->id,
            'requirements' => $requirements,
            'found_staff' => $scoredStaff->count(),
            'total_duration_ms' => round($totalDuration, 2),
            'query_duration_ms' => round($queryDuration, 2),
            'scoring_duration_ms' => round($totalDuration - $queryDuration, 2)
        ]);
        
        // Log performance warning if total time exceeds threshold
        if ($totalDuration > 500) {
            Log::warning('StaffSkillMatcher performance threshold exceeded', [
                'total_duration_ms' => round($totalDuration, 2),
                'threshold_ms' => 500,
                'branch_id' => $branch->id,
                'staff_evaluated' => $potentialStaff->count(),
                'staff_matched' => $scoredStaff->count()
            ]);
        }
        
        // Store performance metrics for analysis
        self::$performanceMetrics[] = [
            'timestamp' => now()->toIso8601String(),
            'total_duration_ms' => round($totalDuration, 2),
            'query_duration_ms' => round($queryDuration, 2),
            'scoring_duration_ms' => round($totalDuration - $queryDuration, 2),
            'staff_evaluated' => $potentialStaff->count(),
            'staff_matched' => $scoredStaff->count(),
            'branch_id' => $branch->id
        ];
        
        // Keep only last 100 metrics to prevent memory issues
        if (count(self::$performanceMetrics) > 100) {
            self::$performanceMetrics = array_slice(self::$performanceMetrics, -100);
        }
        
        return $scoredStaff;
    }
    
    /**
     * Calculate match score for staff member
     */
    private function calculateStaffScore(Staff $staff, array $requirements): float
    {
        $score = 0;
        
        // Service matching
        if (isset($requirements['service_name'])) {
            $serviceScore = $this->matchService($staff, $requirements['service_name']);
            if ($serviceScore === 0) {
                return 0; // No match for required service
            }
            $score += $serviceScore * 40; // 40% weight
        }
        
        // Language matching
        if (isset($requirements['language'])) {
            $score += $this->matchLanguage($staff, $requirements['language']) * 20; // 20% weight
        }
        
        // Experience level
        if (isset($requirements['min_experience'])) {
            $score += $this->matchExperience($staff, $requirements['min_experience']) * 15; // 15% weight
        }
        
        // Specializations
        if (isset($requirements['specializations'])) {
            $score += $this->matchSpecializations($staff, $requirements['specializations']) * 15; // 15% weight
        }
        
        // Certifications
        if (isset($requirements['certifications'])) {
            $score += $this->matchCertifications($staff, $requirements['certifications']) * 10; // 10% weight
        }
        
        // Preferred staff bonus
        if (isset($requirements['preferred_staff_id']) && $staff->id === $requirements['preferred_staff_id']) {
            $score += 20; // Bonus for preferred staff
        }
        
        return $score;
    }
    
    /**
     * Match service capability
     */
    private function matchService(Staff $staff, string $serviceName): float
    {
        // Direct service match
        $hasService = $staff->services->contains(function ($service) use ($serviceName) {
            return stripos($service->name, $serviceName) !== false ||
                   stripos($serviceName, $service->name) !== false;
        });
        
        if ($hasService) {
            return 1.0;
        }
        
        // Check skills for service keywords
        $skills = $staff->skills ?? [];
        foreach ($skills as $skill) {
            if (stripos($skill, $serviceName) !== false || stripos($serviceName, $skill) !== false) {
                return 0.8; // Partial match through skills
            }
        }
        
        // Check specializations
        $specializations = $staff->specializations ?? [];
        foreach ($specializations as $spec) {
            if (stripos($spec, $serviceName) !== false || stripos($serviceName, $spec) !== false) {
                return 0.7; // Partial match through specializations
            }
        }
        
        return 0;
    }
    
    /**
     * Match language requirements
     */
    private function matchLanguage(Staff $staff, string $requiredLanguage): float
    {
        $languages = $staff->languages ?? ['de']; // Default to German
        
        // Check for exact match
        if (in_array(strtolower($requiredLanguage), array_map('strtolower', $languages))) {
            return 1.0;
        }
        
        // Check for partial match (e.g., "en" matches "en-US")
        foreach ($languages as $lang) {
            if (str_starts_with(strtolower($lang), strtolower($requiredLanguage)) ||
                str_starts_with(strtolower($requiredLanguage), strtolower($lang))) {
                return 0.8;
            }
        }
        
        return 0;
    }
    
    /**
     * Match experience level
     */
    private function matchExperience(Staff $staff, int $minExperience): float
    {
        $staffExperience = $staff->experience_level ?? 1;
        
        if ($staffExperience >= $minExperience) {
            // Higher experience is better
            return min(1.0, $staffExperience / ($minExperience + 2));
        }
        
        // Penalize if below minimum
        return max(0, $staffExperience / $minExperience * 0.5);
    }
    
    /**
     * Match specializations
     */
    private function matchSpecializations(Staff $staff, array $requiredSpecs): float
    {
        $staffSpecs = $staff->specializations ?? [];
        
        if (empty($staffSpecs) || empty($requiredSpecs)) {
            return 0.5; // Neutral if no data
        }
        
        $matches = 0;
        foreach ($requiredSpecs as $required) {
            foreach ($staffSpecs as $spec) {
                if (stripos($spec, $required) !== false || stripos($required, $spec) !== false) {
                    $matches++;
                    break;
                }
            }
        }
        
        return $matches / count($requiredSpecs);
    }
    
    /**
     * Match certifications
     */
    private function matchCertifications(Staff $staff, array $requiredCerts): float
    {
        $staffCerts = $staff->certifications ?? [];
        
        if (empty($requiredCerts)) {
            return 1.0; // No certifications required
        }
        
        if (empty($staffCerts)) {
            return 0; // Required but staff has none
        }
        
        $matches = 0;
        foreach ($requiredCerts as $required) {
            foreach ($staffCerts as $cert) {
                if (stripos($cert, $required) !== false || stripos($required, $cert) !== false) {
                    $matches++;
                    break;
                }
            }
        }
        
        return $matches / count($requiredCerts);
    }
    
    /**
     * Find staff with availability at specific time
     */
    public function filterByAvailability(Collection $staff, \Carbon\Carbon $dateTime, int $duration = 30): Collection
    {
        return $staff->filter(function ($staffMember) use ($dateTime, $duration) {
            // Check working hours
            if (!$this->isWithinWorkingHours($staffMember, $dateTime)) {
                return false;
            }
            
            // Check for conflicts
            return !$this->hasConflict($staffMember, $dateTime, $duration);
        });
    }
    
    /**
     * Check if time is within working hours
     */
    private function isWithinWorkingHours(Staff $staff, \Carbon\Carbon $dateTime): bool
    {
        $dayOfWeek = strtolower($dateTime->format('l'));
        
        $workingHours = $staff->workingHours()
            ->where('day_of_week', $dayOfWeek)
            ->first();
            
        if (!$workingHours) {
            // Check branch default hours if no personal hours set
            $branch = $staff->homeBranch ?? $staff->branch;
            if ($branch) {
                $branchHours = $branch->business_hours[$dayOfWeek] ?? null;
                if ($branchHours && isset($branchHours['open']) && isset($branchHours['close'])) {
                    $start = \Carbon\Carbon::parse($branchHours['open']);
                    $end = \Carbon\Carbon::parse($branchHours['close']);
                    return $dateTime->between($start, $end);
                }
            }
            return false;
        }
        
        $start = \Carbon\Carbon::parse($workingHours->start_time);
        $end = \Carbon\Carbon::parse($workingHours->end_time);
        
        return $dateTime->between($start, $end);
    }
    
    /**
     * Check for appointment conflicts
     */
    private function hasConflict(Staff $staff, \Carbon\Carbon $dateTime, int $duration): bool
    {
        $endTime = $dateTime->copy()->addMinutes($duration);
        
        return $staff->appointments()
            ->where('status', '!=', 'cancelled')
            ->where(function ($query) use ($dateTime, $endTime) {
                $query->whereBetween('starts_at', [$dateTime, $endTime])
                      ->orWhereBetween('ends_at', [$dateTime, $endTime])
                      ->orWhere(function ($q) use ($dateTime, $endTime) {
                          $q->where('starts_at', '<=', $dateTime)
                            ->where('ends_at', '>=', $endTime);
                      });
            })
            ->exists();
    }
    
    /**
     * Get staff recommendations with reasons
     */
    public function getStaffRecommendations(Branch $branch, array $requirements, int $limit = 3): array
    {
        $eligibleStaff = $this->findEligibleStaff($branch, $requirements);
        
        $recommendations = [];
        
        foreach ($eligibleStaff->take($limit) as $staff) {
            $reasons = $this->generateRecommendationReasons($staff, $requirements);
            
            $recommendations[] = [
                'staff' => $staff,
                'score' => $staff->match_score,
                'reasons' => $reasons,
                'available_slots' => [] // Would be populated by availability service
            ];
        }
        
        return $recommendations;
    }
    
    /**
     * Generate recommendation reasons
     */
    private function generateRecommendationReasons(Staff $staff, array $requirements): array
    {
        $reasons = [];
        
        // Check service match
        if (isset($requirements['service_name'])) {
            $services = $staff->services->pluck('name')->toArray();
            if (!empty($services)) {
                $reasons[] = 'Spezialisiert auf: ' . implode(', ', array_slice($services, 0, 3));
            }
        }
        
        // Check experience
        if ($staff->experience_level >= 3) {
            $reasons[] = 'Erfahrener Mitarbeiter (' . $staff->experience_level . '+ Jahre)';
        }
        
        // Check languages
        $languages = $staff->languages ?? [];
        if (count($languages) > 1) {
            $langNames = array_map(function($lang) {
                return match($lang) {
                    'de' => 'Deutsch',
                    'en' => 'Englisch',
                    'tr' => 'Türkisch',
                    'fr' => 'Französisch',
                    'es' => 'Spanisch',
                    default => $lang
                };
            }, $languages);
            $reasons[] = 'Spricht: ' . implode(', ', $langNames);
        }
        
        // Check certifications
        $certs = $staff->certifications ?? [];
        if (!empty($certs)) {
            $reasons[] = 'Zertifiziert in: ' . implode(', ', array_slice($certs, 0, 2));
        }
        
        return $reasons;
    }
    
    /**
     * Get performance metrics
     */
    public static function getPerformanceMetrics(): array
    {
        return [
            'metrics' => self::$performanceMetrics,
            'summary' => self::calculatePerformanceSummary()
        ];
    }
    
    /**
     * Calculate performance summary statistics
     */
    private static function calculatePerformanceSummary(): array
    {
        if (empty(self::$performanceMetrics)) {
            return [
                'avg_total_duration_ms' => 0,
                'avg_query_duration_ms' => 0,
                'avg_scoring_duration_ms' => 0,
                'slow_queries_count' => 0,
                'total_queries' => 0
            ];
        }
        
        $metrics = self::$performanceMetrics;
        $count = count($metrics);
        
        $totalDurations = array_column($metrics, 'total_duration_ms');
        $queryDurations = array_column($metrics, 'query_duration_ms');
        $scoringDurations = array_column($metrics, 'scoring_duration_ms');
        
        $slowQueries = array_filter($queryDurations, fn($duration) => $duration > 200);
        
        return [
            'avg_total_duration_ms' => round(array_sum($totalDurations) / $count, 2),
            'avg_query_duration_ms' => round(array_sum($queryDurations) / $count, 2),
            'avg_scoring_duration_ms' => round(array_sum($scoringDurations) / $count, 2),
            'max_total_duration_ms' => max($totalDurations),
            'min_total_duration_ms' => min($totalDurations),
            'slow_queries_count' => count($slowQueries),
            'total_queries' => $count,
            'slow_query_percentage' => round((count($slowQueries) / $count) * 100, 2)
        ];
    }
    
    /**
     * Clear performance metrics
     */
    public static function clearPerformanceMetrics(): void
    {
        self::$performanceMetrics = [];
    }
}