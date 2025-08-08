<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Call;
use App\Models\Appointment;
use App\Models\Customer;
use App\Models\Company;
use App\Models\Staff;
use App\Models\Service;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;
use App\Models\RetellAgent;

/**
 * Advanced Call Analytics Service
 * 
 * Comprehensive analytics and metrics for call management system
 * Optimized for German market with GDPR compliance
 */
class AdvancedCallAnalyticsService
{
    // Cache durations in seconds
    private const CACHE_SHORT = 300;   // 5 minutes
    private const CACHE_MEDIUM = 1800; // 30 minutes
    private const CACHE_LONG = 3600;   // 1 hour
    
    // Performance thresholds for German market
    private const THRESHOLDS = [
        'excellent_answer_rate' => 90,     // >90% excellent
        'good_answer_rate' => 80,          // >80% good
        'poor_answer_rate' => 70,          // <70% needs improvement
        'excellent_conversion' => 25,       // >25% excellent
        'good_conversion' => 15,           // >15% good
        'poor_conversion' => 10,           // <10% needs improvement
        'max_call_duration' => 600,        // 10 minutes max
        'optimal_call_duration' => 300,    // 5 minutes optimal
        'high_cost_per_call' => 3.0,       // €3+ is expensive
        'optimal_cost_per_call' => 1.0,    // €1 is optimal
        'excellent_satisfaction' => 4.5,   // >4.5/5 excellent
        'good_satisfaction' => 3.5,        // >3.5/5 good
        'poor_satisfaction' => 2.5,        // <2.5/5 poor
    ];

    /**
     * Generate comprehensive dashboard analytics
     */
    public function getDashboardMetrics(array $filters = []): array
    {
        $cacheKey = 'advanced_analytics_' . md5(serialize($filters));
        
        return Cache::remember($cacheKey, self::CACHE_MEDIUM, function () use ($filters) {
            return [
                'overview' => $this->getOverviewMetrics($filters),
                'agent_performance' => $this->getAgentPerformanceMetrics($filters),
                'conversion_funnel' => $this->getConversionFunnelData($filters),
                'customer_journey' => $this->getCustomerJourneyMetrics($filters),
                'predictive_insights' => $this->getPredictiveInsights($filters),
                'real_time_kpis' => $this->getRealTimeKPIs($filters),
                'pattern_analysis' => $this->getCallPatternAnalysis($filters),
                'satisfaction_tracking' => $this->getCustomerSatisfactionMetrics($filters),
                'comparative_analysis' => $this->getComparativeAnalytics($filters),
            ];
        });
    }

    /**
     * Get comprehensive agent performance KPIs
     */
    public function getAgentPerformanceKPIs(
        ?int $companyId = null, 
        ?int $staffId = null,
        string $dateFrom = null,
        string $dateTo = null
    ): array {
        $dateFrom = $dateFrom ?: Carbon::now()->subDays(30)->format('Y-m-d');
        $dateTo = $dateTo ?: Carbon::now()->format('Y-m-d');
        
        $cacheKey = "agent_kpis_{$companyId}_{$staffId}_{$dateFrom}_{$dateTo}";
        
        return Cache::remember($cacheKey, self::CACHE_MEDIUM, function () use ($companyId, $staffId, $dateFrom, $dateTo) {
            $query = Call::query()
                ->when($companyId, fn($q) => $q->where('company_id', $companyId))
                ->when($staffId, fn($q) => $q->where('staff_id', $staffId))
                ->whereBetween('created_at', [$dateFrom, $dateTo]);
            
            // Core metrics
            $metrics = $query->selectRaw('
                COUNT(*) as total_calls,
                COUNT(CASE WHEN call_status = "ended" THEN 1 END) as successful_calls,
                COUNT(CASE WHEN call_status IN ("no-answer", "missed", "failed") THEN 1 END) as missed_calls,
                COUNT(CASE WHEN appointment_made = 1 THEN 1 END) as appointment_conversions,
                COUNT(CASE WHEN extracted_name IS NOT NULL OR extracted_email IS NOT NULL THEN 1 END) as data_captured,
                AVG(CASE WHEN call_status = "ended" THEN duration_minutes END) as avg_call_duration,
                AVG(CASE WHEN sentiment_score IS NOT NULL THEN sentiment_score END) as avg_sentiment,
                SUM(CASE WHEN cost IS NOT NULL THEN cost ELSE 0 END) as total_cost,
                COUNT(CASE WHEN first_visit = 1 THEN 1 END) as first_time_callers,
                COUNT(CASE WHEN sentiment = "positive" THEN 1 END) as positive_sentiment,
                COUNT(CASE WHEN sentiment = "negative" THEN 1 END) as negative_sentiment,
                AVG(CASE WHEN end_to_end_latency IS NOT NULL THEN end_to_end_latency END) as avg_latency
            ')->first();

            // Calculate derived KPIs
            $answerRate = $metrics->total_calls > 0 
                ? round(($metrics->successful_calls / $metrics->total_calls) * 100, 2) 
                : 0;
                
            $conversionRate = $metrics->successful_calls > 0 
                ? round(($metrics->appointment_conversions / $metrics->successful_calls) * 100, 2) 
                : 0;
                
            $dataCaptureRate = $metrics->successful_calls > 0 
                ? round(($metrics->data_captured / $metrics->successful_calls) * 100, 2) 
                : 0;
                
            $costPerCall = $metrics->total_calls > 0 
                ? round($metrics->total_cost / $metrics->total_calls, 2) 
                : 0;
                
            $costPerConversion = $metrics->appointment_conversions > 0 
                ? round($metrics->total_cost / $metrics->appointment_conversions, 2) 
                : 0;

            // Calculate revenue and ROI
            $revenueGenerated = $this->calculateRevenueFromCalls($companyId, $staffId, $dateFrom, $dateTo);
            $roi = $metrics->total_cost > 0 ? round((($revenueGenerated - $metrics->total_cost) / $metrics->total_cost) * 100, 2) : 0;

            // Performance scores
            $performanceScore = $this->calculatePerformanceScore($answerRate, $conversionRate, $metrics->avg_sentiment ?? 0);
            
            return [
                'total_calls' => $metrics->total_calls,
                'successful_calls' => $metrics->successful_calls,
                'missed_calls' => $metrics->missed_calls,
                'answer_rate' => $answerRate,
                'conversion_rate' => $conversionRate,
                'data_capture_rate' => $dataCaptureRate,
                'avg_call_duration' => round($metrics->avg_call_duration ?? 0, 2),
                'avg_sentiment_score' => round($metrics->avg_sentiment ?? 0, 2),
                'total_cost' => round($metrics->total_cost, 2),
                'cost_per_call' => $costPerCall,
                'cost_per_conversion' => $costPerConversion,
                'revenue_generated' => round($revenueGenerated, 2),
                'roi_percentage' => $roi,
                'first_time_caller_rate' => $metrics->total_calls > 0 ? round(($metrics->first_time_callers / $metrics->total_calls) * 100, 2) : 0,
                'positive_sentiment_rate' => $metrics->total_calls > 0 ? round(($metrics->positive_sentiment / $metrics->total_calls) * 100, 2) : 0,
                'negative_sentiment_rate' => $metrics->total_calls > 0 ? round(($metrics->negative_sentiment / $metrics->total_calls) * 100, 2) : 0,
                'avg_response_latency_ms' => round($metrics->avg_latency ?? 0),
                'performance_score' => $performanceScore,
                'performance_rating' => $this->getPerformanceRating($performanceScore),
                'improvement_suggestions' => $this->generateImprovementSuggestions($metrics, $answerRate, $conversionRate),
            ];
        });
    }

    /**
     * Get call pattern analysis and predictions
     */
    public function getCallPatternAnalysis(array $filters = []): array
    {
        $companyId = $filters['company_id'] ?? null;
        $dateFrom = $filters['date_from'] ?? Carbon::now()->subDays(90)->format('Y-m-d');
        $dateTo = $filters['date_to'] ?? Carbon::now()->format('Y-m-d');
        
        $cacheKey = "call_patterns_{$companyId}_{$dateFrom}_{$dateTo}";
        
        return Cache::remember($cacheKey, self::CACHE_LONG, function () use ($companyId, $dateFrom, $dateTo) {
            // Hourly patterns
            $hourlyPattern = DB::table('calls')
                ->when($companyId, fn($q) => $q->where('company_id', $companyId))
                ->whereBetween('created_at', [$dateFrom, $dateTo])
                ->selectRaw('HOUR(created_at) as hour, COUNT(*) as call_count, AVG(duration_minutes) as avg_duration')
                ->groupBy('hour')
                ->orderBy('hour')
                ->get()
                ->mapWithKeys(fn($item) => [$item->hour => [
                    'count' => $item->call_count,
                    'avg_duration' => round($item->avg_duration ?: 0, 2)
                ]]);

            // Daily patterns (Monday = 1, Sunday = 7)
            $dailyPattern = DB::table('calls')
                ->when($companyId, fn($q) => $q->where('company_id', $companyId))
                ->whereBetween('created_at', [$dateFrom, $dateTo])
                ->selectRaw('DAYOFWEEK(created_at) as day_of_week, COUNT(*) as call_count')
                ->groupBy('day_of_week')
                ->orderBy('day_of_week')
                ->get()
                ->mapWithKeys(fn($item) => [$this->getDayName($item->day_of_week) => $item->call_count]);

            // Peak time analysis
            $peakHours = $hourlyPattern->sortByDesc('count')->take(3)->keys()->toArray();
            $peakDays = $dailyPattern->sortByDesc()->take(2)->keys()->toArray();

            // Call volume prediction for next week
            $weeklyTrends = DB::table('calls')
                ->when($companyId, fn($q) => $q->where('company_id', $companyId))
                ->whereBetween('created_at', [Carbon::parse($dateFrom)->subDays(30), $dateTo])
                ->selectRaw('YEARWEEK(created_at) as week, COUNT(*) as call_count')
                ->groupBy('week')
                ->orderBy('week')
                ->pluck('call_count', 'week');

            $volumePrediction = $this->predictCallVolume($weeklyTrends->toArray());

            // Conversion rate by time patterns
            $conversionByHour = DB::table('calls')
                ->when($companyId, fn($q) => $q->where('company_id', $companyId))
                ->whereBetween('created_at', [$dateFrom, $dateTo])
                ->selectRaw('
                    HOUR(created_at) as hour, 
                    COUNT(*) as total_calls,
                    COUNT(CASE WHEN appointment_made = 1 THEN 1 END) as conversions
                ')
                ->groupBy('hour')
                ->orderBy('hour')
                ->get()
                ->map(fn($item) => [
                    'hour' => $item->hour,
                    'total_calls' => $item->total_calls,
                    'conversions' => $item->conversions,
                    'conversion_rate' => $item->total_calls > 0 ? round(($item->conversions / $item->total_calls) * 100, 2) : 0
                ]);

            return [
                'hourly_pattern' => $hourlyPattern->toArray(),
                'daily_pattern' => $dailyPattern->toArray(),
                'peak_hours' => $peakHours,
                'peak_days' => $peakDays,
                'volume_prediction' => $volumePrediction,
                'conversion_by_hour' => $conversionByHour->toArray(),
                'seasonal_insights' => $this->getSeasonalInsights($companyId, $dateFrom, $dateTo),
                'capacity_recommendations' => $this->generateCapacityRecommendations($hourlyPattern, $dailyPattern, $companyId),
            ];
        });
    }

    /**
     * Get customer satisfaction tracking metrics
     */
    public function getCustomerSatisfactionMetrics(array $filters = []): array
    {
        $companyId = $filters['company_id'] ?? null;
        $dateFrom = $filters['date_from'] ?? Carbon::now()->subDays(30)->format('Y-m-d');
        $dateTo = $filters['date_to'] ?? Carbon::now()->format('Y-m-d');
        
        $cacheKey = "satisfaction_metrics_{$companyId}_{$dateFrom}_{$dateTo}";
        
        return Cache::remember($cacheKey, self::CACHE_MEDIUM, function () use ($companyId, $dateFrom, $dateTo) {
            $query = Call::when($companyId, fn($q) => $q->where('company_id', $companyId))
                ->whereBetween('created_at', [$dateFrom, $dateTo]);

            // Sentiment analysis
            $sentimentData = (clone $query)
                ->whereNotNull('sentiment')
                ->selectRaw('sentiment, COUNT(*) as count')
                ->groupBy('sentiment')
                ->pluck('count', 'sentiment');

            // Appointment completion tracking
            $appointmentTracking = DB::table('calls as c')
                ->leftJoin('appointments as a', 'c.appointment_id', '=', 'a.id')
                ->when($companyId, fn($q) => $q->where('c.company_id', $companyId))
                ->whereBetween('c.created_at', [$dateFrom, $dateTo])
                ->selectRaw('
                    COUNT(c.id) as total_calls,
                    COUNT(c.appointment_id) as appointments_scheduled,
                    COUNT(CASE WHEN a.status = "completed" THEN 1 END) as appointments_completed,
                    COUNT(CASE WHEN a.status = "no_show" THEN 1 END) as no_shows,
                    COUNT(CASE WHEN a.status = "cancelled" THEN 1 END) as cancellations
                ')
                ->first();

            // Calculate satisfaction scores
            $totalSentimentCalls = $sentimentData->sum();
            $overallSatisfaction = $this->calculateOverallSatisfactionScore($sentimentData, $totalSentimentCalls);
            $npsScore = $this->calculateNPSEquivalent($sentimentData, $totalSentimentCalls);

            // Call resolution metrics
            $resolutionRate = $appointmentTracking->appointments_scheduled > 0 
                ? round(($appointmentTracking->appointments_completed / $appointmentTracking->appointments_scheduled) * 100, 2)
                : 0;

            $noShowRate = $appointmentTracking->appointments_scheduled > 0 
                ? round(($appointmentTracking->no_shows / $appointmentTracking->appointments_scheduled) * 100, 2)
                : 0;

            // Repeat customer analysis
            $repeatCustomers = $this->analyzeRepeatCustomers($companyId, $dateFrom, $dateTo);

            // Callback and complaint analysis
            $callbackRequests = (clone $query)
                ->where(function($q) {
                    $q->where('transcript', 'like', '%rückruf%')
                      ->orWhere('transcript', 'like', '%callback%')
                      ->orWhere('transcript', 'like', '%zurückrufen%');
                })
                ->count();

            return [
                'sentiment_distribution' => [
                    'positive' => $sentimentData['positive'] ?? 0,
                    'neutral' => $sentimentData['neutral'] ?? 0,
                    'negative' => $sentimentData['negative'] ?? 0,
                    'positive_percentage' => $totalSentimentCalls > 0 ? round(($sentimentData['positive'] ?? 0) / $totalSentimentCalls * 100, 2) : 0,
                    'negative_percentage' => $totalSentimentCalls > 0 ? round(($sentimentData['negative'] ?? 0) / $totalSentimentCalls * 100, 2) : 0,
                ],
                'overall_satisfaction_score' => $overallSatisfaction,
                'nps_equivalent' => $npsScore,
                'appointment_metrics' => [
                    'completion_rate' => $resolutionRate,
                    'no_show_rate' => $noShowRate,
                    'cancellation_rate' => $appointmentTracking->appointments_scheduled > 0 
                        ? round(($appointmentTracking->cancellations / $appointmentTracking->appointments_scheduled) * 100, 2)
                        : 0,
                ],
                'customer_loyalty' => [
                    'repeat_customer_rate' => $repeatCustomers['repeat_rate'],
                    'avg_interactions_per_customer' => $repeatCustomers['avg_interactions'],
                    'customer_lifetime_calls' => $repeatCustomers['lifetime_calls'],
                ],
                'service_quality' => [
                    'callback_requests' => $callbackRequests,
                    'callback_rate' => $appointmentTracking->total_calls > 0 ? round(($callbackRequests / $appointmentTracking->total_calls) * 100, 2) : 0,
                    'first_call_resolution' => $this->calculateFirstCallResolution($companyId, $dateFrom, $dateTo),
                ],
                'satisfaction_trends' => $this->getSatisfactionTrends($companyId, $dateFrom, $dateTo),
                'improvement_areas' => $this->identifySatisfactionImprovementAreas($sentimentData, $appointmentTracking, $callbackRequests),
            ];
        });
    }

    /**
     * Get conversion funnel visualization data
     */
    public function getConversionFunnelData(array $filters = []): array
    {
        $companyId = $filters['company_id'] ?? null;
        $dateFrom = $filters['date_from'] ?? Carbon::now()->subDays(30)->format('Y-m-d');
        $dateTo = $filters['date_to'] ?? Carbon::now()->format('Y-m-d');
        
        $cacheKey = "conversion_funnel_{$companyId}_{$dateFrom}_{$dateTo}";
        
        return Cache::remember($cacheKey, self::CACHE_MEDIUM, function () use ($companyId, $dateFrom, $dateTo) {
            $funnelData = DB::table('calls as c')
                ->leftJoin('appointments as a', 'c.appointment_id', '=', 'a.id')
                ->leftJoin('services as s', 'a.service_id', '=', 's.id')
                ->when($companyId, fn($q) => $q->where('c.company_id', $companyId))
                ->whereBetween('c.created_at', [$dateFrom, $dateTo])
                ->selectRaw('
                    COUNT(c.id) as total_calls,
                    COUNT(CASE WHEN c.call_status = "ended" THEN 1 END) as answered_calls,
                    COUNT(CASE WHEN c.extracted_name IS NOT NULL OR c.extracted_email IS NOT NULL THEN 1 END) as data_captured,
                    COUNT(CASE WHEN c.appointment_requested = 1 THEN 1 END) as appointment_interest,
                    COUNT(c.appointment_id) as appointments_booked,
                    COUNT(CASE WHEN a.status = "completed" THEN 1 END) as appointments_completed,
                    SUM(CASE WHEN a.status = "completed" THEN COALESCE(s.price, 0) ELSE 0 END) as revenue_generated
                ')
                ->first();

            // Calculate stages and conversion rates
            $stages = [
                'Eingehende Anrufe' => $funnelData->total_calls,
                'Angenommene Anrufe' => $funnelData->answered_calls,
                'Daten erfasst' => $funnelData->data_captured,
                'Terminwunsch geäußert' => $funnelData->appointment_interest,
                'Termine gebucht' => $funnelData->appointments_booked,
                'Termine wahrgenommen' => $funnelData->appointments_completed,
            ];

            // Calculate conversion rates for each stage
            $conversionRates = [];
            $dropOffAnalysis = [];
            $previousValue = $funnelData->total_calls;
            $previousStage = null;

            foreach ($stages as $stage => $value) {
                // Conversion rate from total calls
                $conversionRates[$stage] = $funnelData->total_calls > 0 
                    ? round(($value / $funnelData->total_calls) * 100, 2) 
                    : 0;

                // Drop-off analysis
                if ($previousStage !== null) {
                    $dropOff = $previousValue - $value;
                    $dropOffRate = $previousValue > 0 ? round(($dropOff / $previousValue) * 100, 2) : 0;
                    
                    $dropOffAnalysis[] = [
                        'from_stage' => $previousStage,
                        'to_stage' => $stage,
                        'drop_off_count' => $dropOff,
                        'drop_off_rate' => $dropOffRate,
                        'severity' => $this->categorizeDrop​OffSeverity($dropOffRate),
                    ];
                }

                $previousValue = $value;
                $previousStage = $stage;
            }

            // Industry benchmarks for comparison
            $benchmarks = $this->getGermanMarketBenchmarks();
            $benchmarkComparison = [];
            
            foreach ($benchmarks as $stage => $benchmark) {
                if (isset($conversionRates[$stage])) {
                    $actual = $conversionRates[$stage];
                    $benchmarkComparison[$stage] = [
                        'actual' => $actual,
                        'benchmark' => $benchmark,
                        'variance' => round($actual - $benchmark, 2),
                        'performance' => $actual >= $benchmark ? 'überdurchschnittlich' : 'unterdurchschnittlich',
                    ];
                }
            }

            return [
                'funnel_stages' => $stages,
                'conversion_rates' => $conversionRates,
                'drop_off_analysis' => $dropOffAnalysis,
                'benchmark_comparison' => $benchmarkComparison,
                'revenue_per_stage' => [
                    'total_revenue' => round($funnelData->revenue_generated ?: 0, 2),
                    'revenue_per_call' => $funnelData->total_calls > 0 ? round($funnelData->revenue_generated / $funnelData->total_calls, 2) : 0,
                    'revenue_per_conversion' => $funnelData->appointments_completed > 0 ? round($funnelData->revenue_generated / $funnelData->appointments_completed, 2) : 0,
                ],
                'optimization_opportunities' => $this->identifyFunnelOptimizations($dropOffAnalysis, $benchmarkComparison),
            ];
        });
    }

    /**
     * Get real-time performance dashboard
     */
    public function getRealTimeKPIs(array $filters = []): array
    {
        $companyId = $filters['company_id'] ?? null;
        $cacheKey = "realtime_kpis_{$companyId}";
        
        return Cache::remember($cacheKey, 60, function () use ($companyId) { // 1-minute cache
            $now = Carbon::now();
            $todayStart = $now->copy()->startOfDay();
            $last24Hours = $now->copy()->subHours(24);
            $thisWeekStart = $now->copy()->startOfWeek();

            // Today's performance
            $todayMetrics = DB::table('calls')
                ->when($companyId, fn($q) => $q->where('company_id', $companyId))
                ->where('created_at', '>=', $todayStart)
                ->selectRaw('
                    COUNT(*) as total_calls,
                    COUNT(CASE WHEN call_status = "ended" THEN 1 END) as answered_calls,
                    COUNT(CASE WHEN appointment_made = 1 THEN 1 END) as appointments_today,
                    AVG(duration_minutes) as avg_duration,
                    SUM(cost) as total_cost,
                    AVG(sentiment_score) as avg_sentiment
                ')
                ->first();

            // Last hour activity
            $lastHourMetrics = DB::table('calls')
                ->when($companyId, fn($q) => $q->where('company_id', $companyId))
                ->where('created_at', '>=', $now->copy()->subHour())
                ->selectRaw('
                    COUNT(*) as calls_last_hour,
                    COUNT(CASE WHEN appointment_made = 1 THEN 1 END) as appointments_last_hour
                ')
                ->first();

            // Active calls (ongoing)
            $activeCalls = Call::when($companyId, fn($q) => $q->where('company_id', $companyId))
                ->whereNull('end_timestamp')
                ->where('created_at', '>', $now->copy()->subHours(2))
                ->count();

            // This week vs last week comparison
            $thisWeekCalls = Call::when($companyId, fn($q) => $q->where('company_id', $companyId))
                ->where('created_at', '>=', $thisWeekStart)
                ->count();

            $lastWeekCalls = Call::when($companyId, fn($q) => $q->where('company_id', $companyId))
                ->whereBetween('created_at', [$thisWeekStart->copy()->subWeek(), $thisWeekStart->copy()->subDay()])
                ->count();

            $weeklyGrowth = $lastWeekCalls > 0 ? round((($thisWeekCalls - $lastWeekCalls) / $lastWeekCalls) * 100, 1) : 0;

            // Performance alerts
            $alerts = $this->generateRealTimeAlerts($companyId, $todayMetrics, $lastHourMetrics);

            return [
                'current_time' => $now->format('Y-m-d H:i:s'),
                'active_calls' => $activeCalls,
                'today_summary' => [
                    'total_calls' => $todayMetrics->total_calls,
                    'answered_calls' => $todayMetrics->answered_calls,
                    'answer_rate' => $todayMetrics->total_calls > 0 ? round(($todayMetrics->answered_calls / $todayMetrics->total_calls) * 100, 1) : 0,
                    'appointments_booked' => $todayMetrics->appointments_today,
                    'conversion_rate' => $todayMetrics->answered_calls > 0 ? round(($todayMetrics->appointments_today / $todayMetrics->answered_calls) * 100, 1) : 0,
                    'avg_call_duration' => round($todayMetrics->avg_duration ?: 0, 1),
                    'total_cost' => round($todayMetrics->total_cost ?: 0, 2),
                    'avg_sentiment' => round($todayMetrics->avg_sentiment ?: 0, 1),
                ],
                'last_hour_activity' => [
                    'calls' => $lastHourMetrics->calls_last_hour,
                    'appointments' => $lastHourMetrics->appointments_last_hour,
                    'hourly_rate' => $lastHourMetrics->calls_last_hour, // calls per hour
                ],
                'weekly_trend' => [
                    'this_week_calls' => $thisWeekCalls,
                    'last_week_calls' => $lastWeekCalls,
                    'growth_percentage' => $weeklyGrowth,
                    'trend' => $weeklyGrowth > 0 ? 'increasing' : ($weeklyGrowth < 0 ? 'decreasing' : 'stable'),
                ],
                'performance_alerts' => $alerts,
                'system_health' => [
                    'avg_response_time' => $this->getSystemResponseTime($companyId),
                    'error_rate' => $this->getSystemErrorRate($companyId),
                    'capacity_utilization' => $this->getCapacityUtilization($companyId),
                ],
            ];
        });
    }

    /**
     * Get comparative analytics (agent vs team vs company)
     */
    public function getComparativeAnalytics(array $filters = []): array
    {
        $companyId = $filters['company_id'] ?? null;
        $staffId = $filters['staff_id'] ?? null;
        $dateFrom = $filters['date_from'] ?? Carbon::now()->subDays(30)->format('Y-m-d');
        $dateTo = $filters['date_to'] ?? Carbon::now()->format('Y-m-d');
        
        if (!$companyId) {
            return ['error' => 'Company ID required for comparative analysis'];
        }

        $cacheKey = "comparative_analytics_{$companyId}_{$staffId}_{$dateFrom}_{$dateTo}";
        
        return Cache::remember($cacheKey, self::CACHE_MEDIUM, function () use ($companyId, $staffId, $dateFrom, $dateTo) {
            // Get individual agent metrics if specified
            $agentMetrics = $staffId ? $this->getAgentPerformanceKPIs($companyId, $staffId, $dateFrom, $dateTo) : null;
            
            // Get team averages (all active staff in company)
            $teamMetrics = $this->getTeamAverages($companyId, $dateFrom, $dateTo);
            
            // Get company-wide metrics
            $companyMetrics = $this->getAgentPerformanceKPIs($companyId, null, $dateFrom, $dateTo);
            
            // Get industry benchmarks
            $industryBenchmarks = $this->getIndustryBenchmarks();
            
            // Calculate percentiles for the agent
            $percentiles = $staffId ? $this->calculateAgentPercentiles($companyId, $staffId, $dateFrom, $dateTo) : null;
            
            // Performance comparisons
            $comparisons = [];
            if ($agentMetrics && $teamMetrics) {
                $comparisons = $this->calculatePerformanceComparisons($agentMetrics, $teamMetrics, $companyMetrics, $industryBenchmarks);
            }

            // Top performers in company
            $topPerformers = $this->getTopPerformers($companyId, $dateFrom, $dateTo);
            
            // Performance distribution
            $performanceDistribution = $this->getPerformanceDistribution($companyId, $dateFrom, $dateTo);

            return [
                'agent_metrics' => $agentMetrics,
                'team_averages' => $teamMetrics,
                'company_metrics' => $companyMetrics,
                'industry_benchmarks' => $industryBenchmarks,
                'agent_percentiles' => $percentiles,
                'performance_comparisons' => $comparisons,
                'top_performers' => $topPerformers,
                'performance_distribution' => $performanceDistribution,
                'improvement_opportunities' => $agentMetrics ? $this->identifyImprovementOpportunities($agentMetrics, $teamMetrics, $industryBenchmarks) : [],
            ];
        });
    }

    /**
     * Generate performance alerts and notifications
     */
    public function generatePerformanceAlerts(int $companyId, ?int $staffId = null): array
    {
        $alerts = [];
        $metrics = $this->getAgentPerformanceKPIs($companyId, $staffId);
        
        // Answer rate alerts
        if ($metrics['answer_rate'] < self::THRESHOLDS['poor_answer_rate']) {
            $alerts[] = [
                'type' => 'warning',
                'category' => 'answer_rate',
                'title' => 'Niedrige Annahmequote',
                'message' => sprintf('Annahmequote liegt bei %.1f%% (unter %.0f%%)', $metrics['answer_rate'], self::THRESHOLDS['poor_answer_rate']),
                'severity' => $metrics['answer_rate'] < 60 ? 'high' : 'medium',
                'action_required' => true,
                'recommendations' => [
                    'Personalkapazitäten prüfen',
                    'Anrufweiterleitung optimieren',
                    'Stoßzeiten analysieren'
                ]
            ];
        }

        // Conversion rate alerts
        if ($metrics['conversion_rate'] < self::THRESHOLDS['poor_conversion']) {
            $alerts[] = [
                'type' => 'error',
                'category' => 'conversion_rate',
                'title' => 'Niedrige Konversionsrate',
                'message' => sprintf('Terminkonversion bei %.1f%% (unter %.0f%%)', $metrics['conversion_rate'], self::THRESHOLDS['poor_conversion']),
                'severity' => 'high',
                'action_required' => true,
                'recommendations' => [
                    'Gesprächsleitfäden überarbeiten',
                    'Agent-Training durchführen',
                    'Terminbuchungsprozess vereinfachen'
                ]
            ];
        }

        // Cost efficiency alerts
        if ($metrics['cost_per_call'] > self::THRESHOLDS['high_cost_per_call']) {
            $alerts[] = [
                'type' => 'warning',
                'category' => 'cost_efficiency',
                'title' => 'Hohe Kosten pro Anruf',
                'message' => sprintf('€%.2f pro Anruf (über €%.2f)', $metrics['cost_per_call'], self::THRESHOLDS['high_cost_per_call']),
                'severity' => 'medium',
                'action_required' => false,
                'recommendations' => [
                    'Gesprächsdauer optimieren',
                    'Tarifmodell überprüfen',
                    'Automatisierung erhöhen'
                ]
            ];
        }

        // Customer satisfaction alerts
        if ($metrics['avg_sentiment_score'] < self::THRESHOLDS['poor_satisfaction']) {
            $alerts[] = [
                'type' => 'error',
                'category' => 'customer_satisfaction',
                'title' => 'Niedrige Kundenzufriedenheit',
                'message' => sprintf('Sentiment-Score: %.1f/5 (unter %.1f)', $metrics['avg_sentiment_score'], self::THRESHOLDS['poor_satisfaction']),
                'severity' => 'high',
                'action_required' => true,
                'recommendations' => [
                    'Kundenservice-Training',
                    'Beschwerdeanalyse durchführen',
                    'Kommunikationsstil anpassen'
                ]
            ];
        }

        // ROI alerts
        if ($metrics['roi_percentage'] < 0) {
            $alerts[] = [
                'type' => 'error',
                'category' => 'roi',
                'title' => 'Negativer ROI',
                'message' => sprintf('ROI: %.1f%% (verlustbringend)', $metrics['roi_percentage']),
                'severity' => 'high',
                'action_required' => true,
                'recommendations' => [
                    'Kostenstellen analysieren',
                    'Pricing-Strategie überprüfen',
                    'Effizienzmaßnahmen einleiten'
                ]
            ];
        }

        return [
            'alerts' => $alerts,
            'total_alerts' => count($alerts),
            'critical_alerts' => count(array_filter($alerts, fn($a) => $a['severity'] === 'high')),
            'action_required' => count(array_filter($alerts, fn($a) => $a['action_required'])),
            'overall_health_score' => $this->calculateOverallHealthScore($metrics),
        ];
    }

    // Private helper methods

    private function calculateRevenueFromCalls(?int $companyId, ?int $staffId, string $dateFrom, string $dateTo): float
    {
        $query = DB::table('calls as c')
            ->join('appointments as a', 'c.appointment_id', '=', 'a.id')
            ->join('services as s', 'a.service_id', '=', 's.id')
            ->where('a.status', 'completed')
            ->whereBetween('c.created_at', [$dateFrom, $dateTo]);

        if ($companyId) {
            $query->where('c.company_id', $companyId);
        }
        
        if ($staffId) {
            $query->where('c.staff_id', $staffId);
        }

        return (float) $query->sum('s.price');
    }

    private function calculatePerformanceScore(float $answerRate, float $conversionRate, float $sentimentScore): float
    {
        // Weighted performance score (0-100)
        $answerWeight = 0.3;
        $conversionWeight = 0.4;
        $sentimentWeight = 0.3;

        $answerScore = min(100, ($answerRate / 90) * 100); // 90% = 100 points
        $conversionScore = min(100, ($conversionRate / 30) * 100); // 30% = 100 points
        $sentimentNormalized = min(100, ($sentimentScore / 5) * 100); // 5.0 = 100 points

        return round(
            ($answerScore * $answerWeight) + 
            ($conversionScore * $conversionWeight) + 
            ($sentimentNormalized * $sentimentWeight),
            1
        );
    }

    private function getPerformanceRating(float $score): string
    {
        if ($score >= 85) return 'Exzellent';
        if ($score >= 70) return 'Sehr gut';
        if ($score >= 55) return 'Gut';
        if ($score >= 40) return 'Durchschnittlich';
        return 'Verbesserungsbedarf';
    }

    private function generateImprovementSuggestions($metrics, float $answerRate, float $conversionRate): array
    {
        $suggestions = [];

        if ($answerRate < self::THRESHOLDS['good_answer_rate']) {
            $suggestions[] = 'Annahmequote verbessern: Personalbesetzung in Stoßzeiten erhöhen';
        }

        if ($conversionRate < self::THRESHOLDS['good_conversion']) {
            $suggestions[] = 'Konversionsrate steigern: Schulung in Verkaufstechniken und Einwandbehandlung';
        }

        if (($metrics->avg_call_duration ?? 0) > self::THRESHOLDS['optimal_call_duration'] / 60) {
            $suggestions[] = 'Gesprächseffizienz: Strukturierte Gesprächsleitfäden verwenden';
        }

        if (($metrics->avg_sentiment ?? 0) < self::THRESHOLDS['good_satisfaction']) {
            $suggestions[] = 'Kundenzufriedenheit: Empathie-Training und aktives Zuhören fördern';
        }

        return array_slice($suggestions, 0, 3); // Top 3 suggestions
    }

    private function getDayName(int $dayOfWeek): string
    {
        return match($dayOfWeek) {
            1 => 'Sonntag',
            2 => 'Montag',
            3 => 'Dienstag',
            4 => 'Mittwoch',
            5 => 'Donnerstag',
            6 => 'Freitag',
            7 => 'Samstag',
            default => 'Unbekannt'
        };
    }

    private function predictCallVolume(array $weeklyTrends): array
    {
        if (count($weeklyTrends) < 4) {
            return [
                'next_week_calls' => 0,
                'confidence' => 'low',
                'trend' => 'insufficient_data'
            ];
        }

        $recent = array_slice($weeklyTrends, -4, 4, true);
        $average = array_sum($recent) / count($recent);
        
        // Calculate trend
        $values = array_values($recent);
        $trend = count($values) > 1 ? ($values[count($values) - 1] - $values[0]) / (count($values) - 1) : 0;
        
        $prediction = max(0, round($average + $trend));
        $confidence = $this->calculatePredictionConfidence($values);
        
        return [
            'next_week_calls' => $prediction,
            'confidence' => $confidence,
            'trend' => $trend > 0 ? 'increasing' : ($trend < 0 ? 'decreasing' : 'stable'),
            'trend_strength' => abs($trend),
        ];
    }

    private function calculatePredictionConfidence(array $values): string
    {
        $variance = $this->calculateVariance($values);
        
        if ($variance < 0.15) return 'high';
        if ($variance < 0.35) return 'medium';
        return 'low';
    }

    private function calculateVariance(array $values): float
    {
        if (count($values) < 2) return 0;
        
        $mean = array_sum($values) / count($values);
        if ($mean == 0) return 0;
        
        $squaredDifferences = array_map(fn($x) => pow($x - $mean, 2), $values);
        $variance = array_sum($squaredDifferences) / count($values);
        
        return sqrt($variance) / $mean; // Coefficient of variation
    }

    private function getSeasonalInsights(?int $companyId, string $dateFrom, string $dateTo): array
    {
        $monthlyData = DB::table('calls')
            ->when($companyId, fn($q) => $q->where('company_id', $companyId))
            ->where('created_at', '>=', Carbon::now()->subYear())
            ->selectRaw('MONTH(created_at) as month, COUNT(*) as call_count')
            ->groupBy('month')
            ->pluck('call_count', 'month');

        if ($monthlyData->count() < 6) {
            return ['status' => 'insufficient_data'];
        }

        $avgVolume = $monthlyData->avg();
        $currentMonth = Carbon::now()->month;
        $currentVolume = $monthlyData[$currentMonth] ?? 0;
        
        $seasonality = $avgVolume > 0 ? round((($currentVolume / $avgVolume) - 1) * 100, 1) : 0;

        return [
            'status' => 'available',
            'current_vs_average' => $seasonality,
            'peak_months' => $monthlyData->sortDesc()->take(3)->keys()->map(fn($m) => Carbon::create()->month($m)->format('F'))->toArray(),
            'trend_direction' => $seasonality > 10 ? 'peak_season' : ($seasonality < -10 ? 'low_season' : 'normal'),
        ];
    }

    private function generateCapacityRecommendations($hourlyPattern, $dailyPattern, ?int $companyId): array
    {
        $recommendations = [];
        
        // Peak hour recommendations
        $peakHours = $hourlyPattern->sortByDesc('count')->take(2);
        foreach ($peakHours as $hour => $data) {
            if ($data['count'] > 0) {
                $recommendations[] = [
                    'type' => 'capacity',
                    'message' => "Verstärkung um {$hour}:00 Uhr empfohlen ({$data['count']} Anrufe)",
                    'priority' => 'medium'
                ];
            }
        }

        // Daily pattern recommendations
        $busyDays = $dailyPattern->sortByDesc()->take(2);
        foreach ($busyDays as $day => $count) {
            $recommendations[] = [
                'type' => 'staffing',
                'message' => "Erhöhte Personalbesetzung am {$day} empfohlen ({$count} Anrufe)",
                'priority' => 'medium'
            ];
        }

        return array_slice($recommendations, 0, 5);
    }

    private function calculateOverallSatisfactionScore($sentimentData, int $totalCalls): float
    {
        if ($totalCalls === 0) return 0;

        $positiveWeight = 5.0;
        $neutralWeight = 3.0;
        $negativeWeight = 1.0;

        $weightedScore = (
            ($sentimentData['positive'] ?? 0) * $positiveWeight +
            ($sentimentData['neutral'] ?? 0) * $neutralWeight +
            ($sentimentData['negative'] ?? 0) * $negativeWeight
        ) / $totalCalls;

        return round($weightedScore, 2);
    }

    private function calculateNPSEquivalent($sentimentData, int $totalCalls): int
    {
        if ($totalCalls === 0) return 0;

        $promoters = ($sentimentData['positive'] ?? 0) / $totalCalls * 100;
        $detractors = ($sentimentData['negative'] ?? 0) / $totalCalls * 100;

        return round($promoters - $detractors);
    }

    private function analyzeRepeatCustomers(?int $companyId, string $dateFrom, string $dateTo): array
    {
        $customerAnalysis = DB::table('calls')
            ->select('customer_id')
            ->selectRaw('COUNT(*) as interaction_count')
            ->when($companyId, fn($q) => $q->where('company_id', $companyId))
            ->whereBetween('created_at', [$dateFrom, $dateTo])
            ->whereNotNull('customer_id')
            ->groupBy('customer_id')
            ->get();

        $totalCustomers = $customerAnalysis->count();
        $repeatCustomers = $customerAnalysis->where('interaction_count', '>', 1)->count();
        $avgInteractions = $customerAnalysis->avg('interaction_count');
        $lifetimeCalls = $customerAnalysis->sum('interaction_count');

        return [
            'repeat_rate' => $totalCustomers > 0 ? round(($repeatCustomers / $totalCustomers) * 100, 2) : 0,
            'avg_interactions' => round($avgInteractions ?: 0, 1),
            'lifetime_calls' => $lifetimeCalls,
        ];
    }

    private function calculateFirstCallResolution(?int $companyId, string $dateFrom, string $dateTo): float
    {
        $firstCalls = DB::table('calls as c1')
            ->leftJoin('calls as c2', function($join) {
                $join->on('c1.customer_id', '=', 'c2.customer_id')
                     ->where('c2.created_at', '<', DB::raw('c1.created_at'));
            })
            ->when($companyId, fn($q) => $q->where('c1.company_id', $companyId))
            ->whereBetween('c1.created_at', [$dateFrom, $dateTo])
            ->whereNull('c2.id') // First calls only
            ->where('c1.appointment_made', 1)
            ->count();

        $totalFirstCalls = DB::table('calls as c1')
            ->leftJoin('calls as c2', function($join) {
                $join->on('c1.customer_id', '=', 'c2.customer_id')
                     ->where('c2.created_at', '<', DB::raw('c1.created_at'));
            })
            ->when($companyId, fn($q) => $q->where('c1.company_id', $companyId))
            ->whereBetween('c1.created_at', [$dateFrom, $dateTo])
            ->whereNull('c2.id')
            ->count();

        return $totalFirstCalls > 0 ? round(($firstCalls / $totalFirstCalls) * 100, 2) : 0;
    }

    private function getSatisfactionTrends(?int $companyId, string $dateFrom, string $dateTo): array
    {
        return DB::table('calls')
            ->selectRaw('DATE(created_at) as date, AVG(sentiment_score) as avg_sentiment')
            ->when($companyId, fn($q) => $q->where('company_id', $companyId))
            ->whereBetween('created_at', [$dateFrom, $dateTo])
            ->whereNotNull('sentiment_score')
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->map(fn($item) => [
                'date' => $item->date,
                'satisfaction' => round($item->avg_sentiment, 2)
            ])
            ->toArray();
    }

    private function identifySatisfactionImprovementAreas($sentimentData, $appointmentData, int $callbackRequests): array
    {
        $improvements = [];
        
        $totalSentiment = $sentimentData->sum();
        if ($totalSentiment > 0) {
            $negativeRate = ($sentimentData['negative'] ?? 0) / $totalSentiment * 100;
            
            if ($negativeRate > 15) {
                $improvements[] = 'Negative Stimmung reduzieren: Schulung in Konfliktvermeidung und Deeskalation';
            }
        }

        if ($appointmentData->no_shows > 0 && $appointmentData->appointments_scheduled > 0) {
            $noShowRate = ($appointmentData->no_shows / $appointmentData->appointments_scheduled) * 100;
            if ($noShowRate > 20) {
                $improvements[] = 'No-Show-Rate senken: Terminerinnerungen und Bestätigungssystem implementieren';
            }
        }

        if ($callbackRequests > 0) {
            $improvements[] = 'Rückrufwünsche minimieren: Vollständige Problemlösung im ersten Gespräch anstreben';
        }

        return array_slice($improvements, 0, 3);
    }

    private function categorizeDropOffSeverity(float $dropOffRate): string
    {
        if ($dropOffRate > 40) return 'kritisch';
        if ($dropOffRate > 25) return 'hoch';
        if ($dropOffRate > 15) return 'mittel';
        return 'gering';
    }

    private function getGermanMarketBenchmarks(): array
    {
        return [
            'Angenommene Anrufe' => 85.0,
            'Daten erfasst' => 75.0,
            'Terminwunsch geäußert' => 60.0,
            'Termine gebucht' => 50.0,
            'Termine wahrgenommen' => 80.0,
        ];
    }

    private function identifyFunnelOptimizations(array $dropOffs, array $benchmarks): array
    {
        $optimizations = [];
        
        foreach ($dropOffs as $dropOff) {
            if ($dropOff['drop_off_rate'] > 30) {
                $optimizations[] = [
                    'stage' => $dropOff['to_stage'],
                    'issue' => "Hoher Verlust von {$dropOff['drop_off_rate']}%",
                    'recommendation' => $this->getStageOptimizationRecommendation($dropOff['to_stage']),
                    'priority' => $dropOff['drop_off_rate'] > 40 ? 'high' : 'medium'
                ];
            }
        }

        return array_slice($optimizations, 0, 3);
    }

    private function getStageOptimizationRecommendation(string $stage): string
    {
        return match($stage) {
            'Angenommene Anrufe' => 'Personalkapazitäten erhöhen, Anrufweiterleitung optimieren',
            'Daten erfasst' => 'Gesprächseinstieg verbessern, Vertrauen aufbauen',
            'Terminwunsch geäußert' => 'Bedarfsanalyse vertiefen, Nutzen klarer kommunizieren',
            'Termine gebucht' => 'Buchungsprozess vereinfachen, Einwände besser behandeln',
            'Termine wahrgenommen' => 'Terminerinnerungen senden, Vorfreude schaffen',
            default => 'Prozess analysieren und optimieren'
        };
    }

    private function generateRealTimeAlerts(?int $companyId, $todayMetrics, $lastHourMetrics): array
    {
        $alerts = [];
        
        // Low activity alert
        if ($lastHourMetrics->calls_last_hour === 0 && Carbon::now()->hour >= 9 && Carbon::now()->hour <= 17) {
            $alerts[] = [
                'type' => 'warning',
                'message' => 'Keine Anrufe in der letzten Stunde während der Geschäftszeiten',
                'priority' => 'medium'
            ];
        }

        // Poor conversion alert
        if ($todayMetrics->total_calls > 10) {
            $todayConversion = $todayMetrics->answered_calls > 0 ? ($todayMetrics->appointments_today / $todayMetrics->answered_calls) * 100 : 0;
            if ($todayConversion < 10) {
                $alerts[] = [
                    'type' => 'error',
                    'message' => sprintf('Niedrige Tageskonversion: %.1f%%', $todayConversion),
                    'priority' => 'high'
                ];
            }
        }

        return $alerts;
    }

    private function getSystemResponseTime(?int $companyId): float
    {
        return (float) DB::table('calls')
            ->when($companyId, fn($q) => $q->where('company_id', $companyId))
            ->where('created_at', '>=', Carbon::now()->subHour())
            ->avg('end_to_end_latency') ?: 0;
    }

    private function getSystemErrorRate(?int $companyId): float
    {
        $total = DB::table('calls')
            ->when($companyId, fn($q) => $q->where('company_id', $companyId))
            ->where('created_at', '>=', Carbon::now()->subHour())
            ->count();

        $errors = DB::table('calls')
            ->when($companyId, fn($q) => $q->where('company_id', $companyId))
            ->where('created_at', '>=', Carbon::now()->subHour())
            ->whereIn('call_status', ['failed', 'error'])
            ->count();

        return $total > 0 ? round(($errors / $total) * 100, 2) : 0;
    }

    private function getCapacityUtilization(?int $companyId): float
    {
        // Simplified capacity calculation
        $activeStaff = Staff::when($companyId, fn($q) => $q->where('company_id', $companyId))
            ->where('is_active', true)
            ->count();

        $activeCalls = Call::when($companyId, fn($q) => $q->where('company_id', $companyId))
            ->whereNull('end_timestamp')
            ->where('created_at', '>', Carbon::now()->subHours(2))
            ->count();

        return $activeStaff > 0 ? min(100, round(($activeCalls / $activeStaff) * 100, 1)) : 0;
    }

    private function getTeamAverages(int $companyId, string $dateFrom, string $dateTo): array
    {
        $staffMembers = Staff::where('company_id', $companyId)
            ->where('is_active', true)
            ->pluck('id');

        if ($staffMembers->isEmpty()) {
            return ['status' => 'no_staff'];
        }

        $teamTotals = [];
        $validStaffCount = 0;

        foreach ($staffMembers as $staffId) {
            $staffMetrics = $this->getAgentPerformanceKPIs($companyId, $staffId, $dateFrom, $dateTo);
            if ($staffMetrics['total_calls'] > 0) {
                foreach (['answer_rate', 'conversion_rate', 'avg_call_duration', 'avg_sentiment_score', 'cost_per_call'] as $key) {
                    $teamTotals[$key] = ($teamTotals[$key] ?? 0) + $staffMetrics[$key];
                }
                $validStaffCount++;
            }
        }

        if ($validStaffCount === 0) {
            return ['status' => 'no_active_staff'];
        }

        return array_map(fn($total) => round($total / $validStaffCount, 2), $teamTotals) + ['team_size' => $validStaffCount];
    }

    private function getIndustryBenchmarks(): array
    {
        return [
            'answer_rate' => 85.0,
            'conversion_rate' => 20.0,
            'avg_call_duration' => 4.5,
            'avg_sentiment_score' => 3.8,
            'cost_per_call' => 1.50,
        ];
    }

    private function calculateAgentPercentiles(int $companyId, int $staffId, string $dateFrom, string $dateTo): array
    {
        $allStaffMetrics = [];
        $staffMembers = Staff::where('company_id', $companyId)->where('is_active', true)->pluck('id');

        foreach ($staffMembers as $id) {
            $metrics = $this->getAgentPerformanceKPIs($companyId, $id, $dateFrom, $dateTo);
            if ($metrics['total_calls'] > 0) {
                $allStaffMetrics[$id] = $metrics;
            }
        }

        if (count($allStaffMetrics) < 3 || !isset($allStaffMetrics[$staffId])) {
            return ['status' => 'insufficient_data'];
        }

        $percentiles = [];
        $keys = ['answer_rate', 'conversion_rate', 'avg_sentiment_score'];
        $targetMetrics = $allStaffMetrics[$staffId];

        foreach ($keys as $key) {
            $values = collect($allStaffMetrics)->pluck($key)->sort()->values();
            $targetValue = $targetMetrics[$key];
            $rank = $values->search(fn($v) => $v >= $targetValue);
            $percentile = $values->count() > 1 ? round(($rank / ($values->count() - 1)) * 100) : 50;
            $percentiles[$key] = $percentile;
        }

        return $percentiles;
    }

    private function calculatePerformanceComparisons(array $agent, array $team, array $company, array $industry): array
    {
        $comparisons = [];
        $keys = ['answer_rate', 'conversion_rate', 'avg_sentiment_score'];

        foreach ($keys as $key) {
            $agentValue = $agent[$key] ?? 0;
            $teamValue = $team[$key] ?? 0;
            $companyValue = $company[$key] ?? 0;
            $industryValue = $industry[$key] ?? 0;

            $comparisons[$key] = [
                'vs_team' => $teamValue > 0 ? round((($agentValue / $teamValue) - 1) * 100, 1) : 0,
                'vs_company' => $companyValue > 0 ? round((($agentValue / $companyValue) - 1) * 100, 1) : 0,
                'vs_industry' => $industryValue > 0 ? round((($agentValue / $industryValue) - 1) * 100, 1) : 0,
            ];
        }

        return $comparisons;
    }

    private function getTopPerformers(int $companyId, string $dateFrom, string $dateTo): array
    {
        $performers = [];
        $staffMembers = Staff::where('company_id', $companyId)->where('is_active', true)->get();

        foreach ($staffMembers as $staff) {
            $metrics = $this->getAgentPerformanceKPIs($companyId, $staff->id, $dateFrom, $dateTo);
            if ($metrics['total_calls'] > 5) { // Minimum threshold
                $performers[] = [
                    'staff_id' => $staff->id,
                    'name' => $staff->name,
                    'performance_score' => $metrics['performance_score'],
                    'conversion_rate' => $metrics['conversion_rate'],
                    'total_calls' => $metrics['total_calls'],
                ];
            }
        }

        return collect($performers)->sortByDesc('performance_score')->take(5)->values()->toArray();
    }

    private function getPerformanceDistribution(int $companyId, string $dateFrom, string $dateTo): array
    {
        $scores = [];
        $staffMembers = Staff::where('company_id', $companyId)->where('is_active', true)->pluck('id');

        foreach ($staffMembers as $staffId) {
            $metrics = $this->getAgentPerformanceKPIs($companyId, $staffId, $dateFrom, $dateTo);
            if ($metrics['total_calls'] > 0) {
                $scores[] = $metrics['performance_score'];
            }
        }

        return [
            'excellent' => count(array_filter($scores, fn($s) => $s >= 85)),
            'good' => count(array_filter($scores, fn($s) => $s >= 70 && $s < 85)),
            'average' => count(array_filter($scores, fn($s) => $s >= 55 && $s < 70)),
            'needs_improvement' => count(array_filter($scores, fn($s) => $s < 55)),
        ];
    }

    private function identifyImprovementOpportunities(array $agent, array $team, array $industry): array
    {
        $opportunities = [];

        if ($agent['answer_rate'] < $team['answer_rate'] - 5) {
            $opportunities[] = 'Annahmequote verbessern durch optimierte Erreichbarkeit';
        }

        if ($agent['conversion_rate'] < $team['conversion_rate'] - 5) {
            $opportunities[] = 'Gesprächstechniken und Verkaufsskills entwickeln';
        }

        if ($agent['avg_sentiment_score'] < $team['avg_sentiment_score'] - 0.3) {
            $opportunities[] = 'Kundenkommunikation und Empathie stärken';
        }

        return array_slice($opportunities, 0, 3);
    }

    private function calculateOverallHealthScore(array $metrics): float
    {
        $weights = [
            'answer_rate' => 0.25,
            'conversion_rate' => 0.35,
            'avg_sentiment_score' => 0.25,
            'roi_percentage' => 0.15,
        ];

        $normalizedScores = [
            'answer_rate' => min(100, $metrics['answer_rate']),
            'conversion_rate' => min(100, $metrics['conversion_rate'] * 3), // Scale up conversion
            'avg_sentiment_score' => min(100, $metrics['avg_sentiment_score'] * 20), // Scale 0-5 to 0-100
            'roi_percentage' => max(0, min(100, $metrics['roi_percentage'])), // Cap at 100%
        ];

        $healthScore = 0;
        foreach ($weights as $metric => $weight) {
            $healthScore += ($normalizedScores[$metric] ?? 0) * $weight;
        }

        return round($healthScore, 1);
    }

    // Placeholder methods for comprehensive implementation
    private function getOverviewMetrics(array $filters = []): array { return []; }
    private function getAgentPerformanceMetrics(array $filters = []): array { return []; }
    private function getConversionFunnelData(array $filters = []): array { return []; }
    private function getCustomerJourneyMetrics(array $filters = []): array { return []; }
    private function getPredictiveInsights(array $filters = []): array { return []; }
    
}