<?php

namespace App\Services;

use App\Models\Company;
use App\Models\CompanyGoal;
use App\Models\GoalAchievement;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class GoalCalculationService
{
    /**
     * Calculate achievements for all active goals
     */
    public function calculateAllAchievements($periodType = GoalAchievement::PERIOD_DAILY)
    {
        $activeGoals = CompanyGoal::active()
            ->current()
            ->with(['company', 'metrics', 'funnelSteps'])
            ->get();

        $results = [];

        foreach ($activeGoals as $goal) {
            try {
                $achievement = $this->calculateGoalAchievement($goal, $periodType);
                $results[] = [
                    'goal_id' => $goal->id,
                    'company_id' => $goal->company_id,
                    'success' => true,
                    'achievement' => $achievement,
                ];
            } catch (\Exception $e) {
                Log::error('Failed to calculate goal achievement', [
                    'goal_id' => $goal->id,
                    'error' => $e->getMessage(),
                ]);

                $results[] = [
                    'goal_id' => $goal->id,
                    'company_id' => $goal->company_id,
                    'success' => false,
                    'error' => $e->getMessage(),
                ];
            }
        }

        return $results;
    }

    /**
     * Calculate achievement for a specific goal
     */
    public function calculateGoalAchievement(CompanyGoal $goal, $periodType = GoalAchievement::PERIOD_DAILY, $date = null)
    {
        return GoalAchievement::recordAchievement($goal, $periodType, $date);
    }

    /**
     * Get achievement trend for a goal
     */
    public function getAchievementTrend(CompanyGoal $goal, $days = 30)
    {
        $endDate = now();
        $startDate = now()->subDays($days);

        $achievements = $goal->achievements()
            ->where('period_type', GoalAchievement::PERIOD_DAILY)
            ->whereNull('goal_metric_id')
            ->whereBetween('period_start', [$startDate, $endDate])
            ->orderBy('period_start')
            ->get();

        $trend = [];
        $currentDate = $startDate->copy();

        while ($currentDate <= $endDate) {
            $achievement = $achievements->first(function ($a) use ($currentDate) {
                return $a->period_start->isSameDay($currentDate);
            });

            $trend[] = [
                'date' => $currentDate->format('Y-m-d'),
                'achievement_percentage' => $achievement ? $achievement->achievement_percentage : null,
                'target_value' => 100,
            ];

            $currentDate->addDay();
        }

        return $trend;
    }

    /**
     * Get metric-specific trends
     */
    public function getMetricTrends(CompanyGoal $goal, $days = 30)
    {
        $trends = [];

        foreach ($goal->metrics as $metric) {
            $trends[$metric->id] = $this->getMetricTrend($metric, $days);
        }

        return $trends;
    }

    /**
     * Get trend for a specific metric
     */
    public function getMetricTrend($metric, $days = 30)
    {
        $endDate = now();
        $startDate = now()->subDays($days);

        $achievements = GoalAchievement::where('company_goal_id', $metric->company_goal_id)
            ->where('goal_metric_id', $metric->id)
            ->where('period_type', GoalAchievement::PERIOD_DAILY)
            ->whereBetween('period_start', [$startDate, $endDate])
            ->orderBy('period_start')
            ->get();

        $trend = [];
        $currentDate = $startDate->copy();

        while ($currentDate <= $endDate) {
            $achievement = $achievements->first(function ($a) use ($currentDate) {
                return $a->period_start->isSameDay($currentDate);
            });

            $trend[] = [
                'date' => $currentDate->format('Y-m-d'),
                'value' => $achievement ? $achievement->achieved_value : null,
                'achievement_percentage' => $achievement ? $achievement->achievement_percentage : null,
                'target_value' => $metric->target_value,
            ];

            $currentDate->addDay();
        }

        return $trend;
    }

    /**
     * Get funnel conversion trends
     */
    public function getFunnelTrends(CompanyGoal $goal, $days = 30)
    {
        $endDate = now();
        $startDate = now()->subDays($days);

        $achievements = $goal->achievements()
            ->whereNull('goal_metric_id')
            ->where('period_type', GoalAchievement::PERIOD_DAILY)
            ->whereBetween('period_start', [$startDate, $endDate])
            ->orderBy('period_start')
            ->get();

        $trends = [];
        
        foreach ($achievements as $achievement) {
            if ($achievement->funnel_data) {
                foreach ($achievement->funnel_data as $stepData) {
                    $stepOrder = $stepData['step_order'];
                    
                    if (!isset($trends[$stepOrder])) {
                        $trends[$stepOrder] = [
                            'step_name' => $stepData['step_name'],
                            'data' => [],
                        ];
                    }

                    $trends[$stepOrder]['data'][] = [
                        'date' => $achievement->period_start->format('Y-m-d'),
                        'count' => $stepData['count'],
                        'conversion_rate' => $stepData['conversion_rate'],
                    ];
                }
            }
        }

        return $trends;
    }

    /**
     * Calculate goal projections
     */
    public function calculateProjections(CompanyGoal $goal)
    {
        $cacheKey = "goal_projections_{$goal->id}";
        
        return Cache::remember($cacheKey, 3600, function () use ($goal) {
            $historicalData = $this->getAchievementTrend($goal, 30);
            
            // Calculate average daily achievement
            $validData = collect($historicalData)
                ->filter(function ($data) {
                    return $data['achievement_percentage'] !== null;
                });

            if ($validData->isEmpty()) {
                return null;
            }

            $avgAchievement = $validData->avg('achievement_percentage');
            $trend = $this->calculateTrendLine($validData->pluck('achievement_percentage')->toArray());

            $daysRemaining = $goal->days_remaining;
            $currentAchievement = $validData->last()['achievement_percentage'] ?? 0;

            // Project based on trend
            $projectedAchievement = $currentAchievement + ($trend * $daysRemaining);

            return [
                'current_achievement' => $currentAchievement,
                'average_daily_achievement' => $avgAchievement,
                'trend_coefficient' => $trend,
                'projected_achievement' => min(100, max(0, $projectedAchievement)),
                'days_remaining' => $daysRemaining,
                'on_track' => $projectedAchievement >= 100,
                'confidence' => $this->calculateConfidence($validData),
            ];
        });
    }

    /**
     * Calculate trend line coefficient
     */
    private function calculateTrendLine($data)
    {
        $n = count($data);
        if ($n < 2) {
            return 0;
        }

        $x = range(1, $n);
        $y = array_values($data);

        $sumX = array_sum($x);
        $sumY = array_sum($y);
        $sumXY = 0;
        $sumX2 = 0;

        for ($i = 0; $i < $n; $i++) {
            $sumXY += $x[$i] * $y[$i];
            $sumX2 += $x[$i] * $x[$i];
        }

        $denominator = ($n * $sumX2) - ($sumX * $sumX);
        if ($denominator == 0) {
            return 0;
        }

        return (($n * $sumXY) - ($sumX * $sumY)) / $denominator;
    }

    /**
     * Calculate confidence in projection
     */
    private function calculateConfidence($data)
    {
        $count = $data->count();
        $variance = $data->pluck('achievement_percentage')->variance();

        // Base confidence on data points and variance
        $dataConfidence = min(100, ($count / 30) * 100);
        $varianceConfidence = max(0, 100 - ($variance / 10));

        return ($dataConfidence + $varianceConfidence) / 2;
    }

    /**
     * Get achievement comparison between periods
     */
    public function compareAchievements(CompanyGoal $goal, $period1Start, $period1End, $period2Start, $period2End)
    {
        $period1Achievements = $goal->achievements()
            ->whereNull('goal_metric_id')
            ->whereBetween('period_start', [$period1Start, $period1End])
            ->get();

        $period2Achievements = $goal->achievements()
            ->whereNull('goal_metric_id')
            ->whereBetween('period_start', [$period2Start, $period2End])
            ->get();

        $period1Avg = $period1Achievements->avg('achievement_percentage') ?? 0;
        $period2Avg = $period2Achievements->avg('achievement_percentage') ?? 0;

        $improvement = $period1Avg > 0 ? (($period2Avg - $period1Avg) / $period1Avg) * 100 : 0;

        return [
            'period1' => [
                'start' => $period1Start,
                'end' => $period1End,
                'average_achievement' => $period1Avg,
                'count' => $period1Achievements->count(),
            ],
            'period2' => [
                'start' => $period2Start,
                'end' => $period2End,
                'average_achievement' => $period2Avg,
                'count' => $period2Achievements->count(),
            ],
            'improvement_percentage' => $improvement,
            'improved' => $improvement > 0,
        ];
    }

    /**
     * Get top performing metrics
     */
    public function getTopPerformingMetrics(Company $company, $limit = 5)
    {
        $activeGoals = $company->goals()
            ->active()
            ->current()
            ->with('metrics')
            ->get();

        $metricPerformances = [];

        foreach ($activeGoals as $goal) {
            foreach ($goal->metrics as $metric) {
                $achievement = $metric->getAchievementPercentage();
                
                $metricPerformances[] = [
                    'goal' => $goal,
                    'metric' => $metric,
                    'achievement_percentage' => $achievement,
                    'current_value' => $metric->getCurrentValue(),
                    'formatted_value' => $metric->formatValue(),
                ];
            }
        }

        return collect($metricPerformances)
            ->sortByDesc('achievement_percentage')
            ->take($limit)
            ->values();
    }

    /**
     * Get underperforming metrics that need attention
     */
    public function getUnderperformingMetrics(Company $company, $threshold = 50, $limit = 5)
    {
        $activeGoals = $company->goals()
            ->active()
            ->current()
            ->with('metrics')
            ->get();

        $metricPerformances = [];

        foreach ($activeGoals as $goal) {
            foreach ($goal->metrics as $metric) {
                $achievement = $metric->getAchievementPercentage();
                
                if ($achievement < $threshold) {
                    $metricPerformances[] = [
                        'goal' => $goal,
                        'metric' => $metric,
                        'achievement_percentage' => $achievement,
                        'current_value' => $metric->getCurrentValue(),
                        'formatted_value' => $metric->formatValue(),
                        'gap' => $metric->target_value - $metric->getCurrentValue(),
                    ];
                }
            }
        }

        return collect($metricPerformances)
            ->sortBy('achievement_percentage')
            ->take($limit)
            ->values();
    }

    /**
     * Generate achievement report
     */
    public function generateAchievementReport(CompanyGoal $goal, $startDate = null, $endDate = null)
    {
        $startDate = $startDate ?: $goal->start_date;
        $endDate = $endDate ?: now();

        $service = app(GoalService::class);

        return [
            'goal' => $goal,
            'period' => [
                'start' => $startDate,
                'end' => $endDate,
            ],
            'progress' => $service->getGoalProgress($goal),
            'trends' => [
                'overall' => $this->getAchievementTrend($goal, $startDate->diffInDays($endDate)),
                'metrics' => $this->getMetricTrends($goal, $startDate->diffInDays($endDate)),
                'funnel' => $this->getFunnelTrends($goal, $startDate->diffInDays($endDate)),
            ],
            'projections' => $this->calculateProjections($goal),
            'summary' => $this->generateSummaryStats($goal, $startDate, $endDate),
        ];
    }

    /**
     * Generate summary statistics
     */
    private function generateSummaryStats(CompanyGoal $goal, $startDate, $endDate)
    {
        $achievements = $goal->achievements()
            ->whereNull('goal_metric_id')
            ->whereBetween('period_start', [$startDate, $endDate])
            ->get();

        $successfulDays = $achievements->filter(function ($a) {
            return $a->achievement_percentage >= 100;
        })->count();

        return [
            'total_days' => $startDate->diffInDays($endDate) + 1,
            'days_with_data' => $achievements->count(),
            'successful_days' => $successfulDays,
            'average_achievement' => $achievements->avg('achievement_percentage') ?? 0,
            'best_day' => $achievements->sortByDesc('achievement_percentage')->first(),
            'worst_day' => $achievements->sortBy('achievement_percentage')->first(),
            'current_streak' => $this->calculateStreak($achievements),
        ];
    }

    /**
     * Calculate success streak
     */
    private function calculateStreak($achievements)
    {
        $streak = 0;
        $currentStreak = 0;

        foreach ($achievements->sortByDesc('period_start') as $achievement) {
            if ($achievement->achievement_percentage >= 100) {
                $currentStreak++;
                $streak = max($streak, $currentStreak);
            } else {
                break;
            }
        }

        return $currentStreak;
    }
}