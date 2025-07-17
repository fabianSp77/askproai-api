<?php

namespace App\Http\Controllers\Portal\Api;

use App\Models\CompanyGoal;
use App\Models\GoalMetric;
use App\Models\GoalAchievement;
use App\Services\GoalService;
use App\Services\GoalCalculationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class GoalMetricsApiController extends BaseApiController
{
    protected $goalService;
    protected $calculationService;

    public function __construct(GoalService $goalService, GoalCalculationService $calculationService)
    {
        $this->goalService = $goalService;
        $this->calculationService = $calculationService;
    }

    /**
     * Get achievement trend for a goal
     */
    public function achievementTrend(Request $request, CompanyGoal $goal)
    {
        // Ensure goal belongs to user's company
        $company = $this->getCompany();
        
        if (!$company || $goal->company_id !== $company->id) {
            return response()->json([
                'success' => false,
                'message' => 'Ziel nicht gefunden',
            ], 404);
        }

        try {
            $days = $request->input('days', 30);
            $trend = $this->calculationService->getAchievementTrend($goal, $days);

            return response()->json([
                'success' => true,
                'data' => [
                    'goal_id' => $goal->id,
                    'goal_name' => $goal->name,
                    'days' => $days,
                    'trend' => $trend,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to get achievement trend', [
                'goal_id' => $goal->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Fehler beim Abrufen des Trends',
            ], 500);
        }
    }

    /**
     * Get metric trends for a goal
     */
    public function metricTrends(Request $request, CompanyGoal $goal)
    {
        // Ensure goal belongs to user's company
        $company = $this->getCompany();
        
        if (!$company || $goal->company_id !== $company->id) {
            return response()->json([
                'success' => false,
                'message' => 'Ziel nicht gefunden',
            ], 404);
        }

        try {
            $days = $request->input('days', 30);
            $trends = $this->calculationService->getMetricTrends($goal, $days);

            // Transform metric IDs to metric details
            $detailedTrends = [];
            foreach ($trends as $metricId => $trend) {
                $metric = $goal->metrics()->find($metricId);
                if ($metric) {
                    $detailedTrends[] = [
                        'metric' => [
                            'id' => $metric->id,
                            'name' => $metric->metric_name,
                            'type' => $metric->metric_type,
                            'unit' => $metric->target_unit,
                        ],
                        'trend' => $trend,
                    ];
                }
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'goal_id' => $goal->id,
                    'goal_name' => $goal->name,
                    'days' => $days,
                    'metrics' => $detailedTrends,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to get metric trends', [
                'goal_id' => $goal->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Fehler beim Abrufen der Metrik-Trends',
            ], 500);
        }
    }

    /**
     * Get funnel conversion trends
     */
    public function funnelTrends(Request $request, CompanyGoal $goal)
    {
        // Ensure goal belongs to user's company
        $company = $this->getCompany();
        
        if (!$company || $goal->company_id !== $company->id) {
            return response()->json([
                'success' => false,
                'message' => 'Ziel nicht gefunden',
            ], 404);
        }

        try {
            $days = $request->input('days', 30);
            $trends = $this->calculationService->getFunnelTrends($goal, $days);

            return response()->json([
                'success' => true,
                'data' => [
                    'goal_id' => $goal->id,
                    'goal_name' => $goal->name,
                    'days' => $days,
                    'funnel' => $trends,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to get funnel trends', [
                'goal_id' => $goal->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Fehler beim Abrufen der Trichter-Trends',
            ], 500);
        }
    }

    /**
     * Get projections for a goal
     */
    public function projections(Request $request, CompanyGoal $goal)
    {
        // Ensure goal belongs to user's company
        $company = $this->getCompany();
        
        if (!$company || $goal->company_id !== $company->id) {
            return response()->json([
                'success' => false,
                'message' => 'Ziel nicht gefunden',
            ], 404);
        }

        try {
            $projections = $this->calculationService->calculateProjections($goal);

            return response()->json([
                'success' => true,
                'data' => [
                    'goal_id' => $goal->id,
                    'goal_name' => $goal->name,
                    'projections' => $projections,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to calculate projections', [
                'goal_id' => $goal->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Fehler bei der Berechnung der Projektionen',
            ], 500);
        }
    }

    /**
     * Compare achievements between periods
     */
    public function compareAchievements(Request $request, CompanyGoal $goal)
    {
        // Ensure goal belongs to user's company
        $company = $this->getCompany();
        
        if (!$company || $goal->company_id !== $company->id) {
            return response()->json([
                'success' => false,
                'message' => 'Ziel nicht gefunden',
            ], 404);
        }

        try {
            // Default: Compare last week with this week
            $period1Start = $request->input('period1_start') ? Carbon::parse($request->input('period1_start')) : now()->subWeek()->startOfWeek();
            $period1End = $request->input('period1_end') ? Carbon::parse($request->input('period1_end')) : now()->subWeek()->endOfWeek();
            $period2Start = $request->input('period2_start') ? Carbon::parse($request->input('period2_start')) : now()->startOfWeek();
            $period2End = $request->input('period2_end') ? Carbon::parse($request->input('period2_end')) : now()->endOfWeek();

            $comparison = $this->calculationService->compareAchievements(
                $goal,
                $period1Start,
                $period1End,
                $period2Start,
                $period2End
            );

            return response()->json([
                'success' => true,
                'data' => $comparison,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to compare achievements', [
                'goal_id' => $goal->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Fehler beim Vergleich der Erfolge',
            ], 500);
        }
    }

    /**
     * Get achievement report
     */
    public function achievementReport(Request $request, CompanyGoal $goal)
    {
        // Ensure goal belongs to user's company
        $company = $this->getCompany();
        
        if (!$company || $goal->company_id !== $company->id) {
            return response()->json([
                'success' => false,
                'message' => 'Ziel nicht gefunden',
            ], 404);
        }

        try {
            $startDate = $request->input('start_date') ? Carbon::parse($request->input('start_date')) : null;
            $endDate = $request->input('end_date') ? Carbon::parse($request->input('end_date')) : null;

            $report = $this->calculationService->generateAchievementReport($goal, $startDate, $endDate);

            return response()->json([
                'success' => true,
                'data' => $report,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to generate achievement report', [
                'goal_id' => $goal->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Fehler beim Generieren des Berichts',
            ], 500);
        }
    }

    /**
     * Get top performing metrics across all goals
     */
    public function topPerformingMetrics(Request $request)
    {
        try {
            $company = $this->getCompany();
            
            if (!$company) {
                return response()->json(['error' => 'Unauthorized'], 401);
            }
            $limit = $request->input('limit', 5);
            
            $topMetrics = $this->calculationService->getTopPerformingMetrics($company, $limit);

            return response()->json([
                'success' => true,
                'data' => $topMetrics,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to get top performing metrics', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Fehler beim Abrufen der Top-Metriken',
            ], 500);
        }
    }

    /**
     * Get underperforming metrics that need attention
     */
    public function underperformingMetrics(Request $request)
    {
        try {
            $company = $this->getCompany();
            
            if (!$company) {
                return response()->json(['error' => 'Unauthorized'], 401);
            }
            $threshold = $request->input('threshold', 50);
            $limit = $request->input('limit', 5);
            
            $underperformingMetrics = $this->calculationService->getUnderperformingMetrics($company, $threshold, $limit);

            return response()->json([
                'success' => true,
                'data' => [
                    'threshold' => $threshold,
                    'metrics' => $underperformingMetrics,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to get underperforming metrics', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Fehler beim Abrufen der unterdurchschnittlichen Metriken',
            ], 500);
        }
    }

    /**
     * Record achievement manually (for testing or manual adjustments)
     */
    public function recordAchievement(Request $request, CompanyGoal $goal)
    {
        // Ensure goal belongs to user's company
        $company = $this->getCompany();
        
        if (!$company || $goal->company_id !== $company->id) {
            return response()->json([
                'success' => false,
                'message' => 'Ziel nicht gefunden',
            ], 404);
        }

        try {
            $periodType = $request->input('period_type', GoalAchievement::PERIOD_DAILY);
            $date = $request->input('date') ? Carbon::parse($request->input('date')) : null;

            $achievement = $this->goalService->recordAchievement($goal, $periodType, $date);

            return response()->json([
                'success' => true,
                'message' => 'Erfolg erfolgreich aufgezeichnet',
                'data' => $achievement,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to record achievement', [
                'goal_id' => $goal->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Fehler beim Aufzeichnen des Erfolgs',
            ], 500);
        }
    }

    /**
     * Get achievements for a specific period
     */
    public function achievements(Request $request, CompanyGoal $goal)
    {
        // Ensure goal belongs to user's company
        $company = $this->getCompany();
        
        if (!$company || $goal->company_id !== $company->id) {
            return response()->json([
                'success' => false,
                'message' => 'Ziel nicht gefunden',
            ], 404);
        }

        try {
            $periodType = $request->input('period_type', GoalAchievement::PERIOD_DAILY);
            $startDate = $request->input('start_date') ? Carbon::parse($request->input('start_date')) : null;
            $endDate = $request->input('end_date') ? Carbon::parse($request->input('end_date')) : null;

            $achievements = $this->goalService->getGoalAchievements($goal, $periodType, $startDate, $endDate);

            return response()->json([
                'success' => true,
                'data' => [
                    'goal_id' => $goal->id,
                    'goal_name' => $goal->name,
                    'period_type' => $periodType,
                    'achievements' => $achievements,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to get achievements', [
                'goal_id' => $goal->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Fehler beim Abrufen der Erfolge',
            ], 500);
        }
    }
}