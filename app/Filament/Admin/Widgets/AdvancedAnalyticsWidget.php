<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Call;
use App\Models\Appointment;
use App\Models\Customer;
use App\Services\AdvancedCallAnalyticsService;
use Carbon\Carbon;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class AdvancedAnalyticsWidget extends Widget
{
    protected static string $view = 'filament.admin.widgets.advanced-analytics';
    protected static ?int $sort = 5;
    protected int | string | array $columnSpan = 'full';
    protected static ?string $pollingInterval = '30s';
    
    protected AdvancedCallAnalyticsService $analyticsService;
    
    public function __construct()
    {
        $this->analyticsService = app(AdvancedCallAnalyticsService::class);
    }
    
    public function getViewData(): array
    {
        $cacheKey = 'advanced_analytics_widget_' . auth()->id();
        
        return Cache::remember($cacheKey, 300, function () {
            // Get current date range (last 30 days)
            $dateFrom = Carbon::now()->subDays(30)->startOfDay();
            $dateTo = Carbon::now()->endOfDay();
            
            // Get user's company context
            $user = auth()->user();
            $companyId = null;
            if ($user && !$user->hasRole(['Super Admin', 'super_admin'])) {
                $company = $user->companies()->first();
                $companyId = $company?->id;
            }
            
            $filters = [
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
                'company_id' => $companyId,
            ];
            
            // Get analytics data
            $analytics = $this->analyticsService->getDashboardMetrics($filters);
            
            return [
                'overview' => $analytics['overview'] ?? [],
                'realtime_kpis' => $analytics['real_time_kpis'] ?? [],
                'agent_performance' => $this->getTopAgentPerformers($analytics['agent_performance'] ?? []),
                'conversion_funnel' => $this->getFunnelSummary($analytics['conversion_funnel'] ?? []),
                'anomalies' => $analytics['anomaly_detection']['anomalies'] ?? [],
                'trend_indicators' => $this->getTrendIndicators($filters),
                'quick_insights' => $this->generateQuickInsights($analytics),
            ];
        });
    }
    
    protected function getTopAgentPerformers(array $agentData): array
    {
        $rankings = $agentData['agent_rankings'] ?? [];
        return array_slice($rankings, 0, 3); // Top 3 performers
    }
    
    protected function getFunnelSummary(array $funnelData): array
    {
        $stages = $funnelData['stages'] ?? [];
        $rates = $funnelData['conversion_rates'] ?? [];
        
        return [
            'total_calls' => $stages['Total Calls'] ?? 0,
            'appointments_completed' => $stages['Appointment Completed'] ?? 0,
            'overall_conversion' => end($rates) ?: 0,
            'biggest_dropoff' => $this->findBiggestDropoff($funnelData['drop_off_analysis'] ?? []),
        ];
    }
    
    protected function findBiggestDropoff(array $dropOffs): ?array
    {
        if (empty($dropOffs)) {
            return null;
        }
        
        return collect($dropOffs)->sortByDesc('drop_off_count')->first();
    }
    
    protected function getTrendIndicators(array $filters): array
    {
        // Compare current period with previous period
        $currentPeriod = $this->getPeriodMetrics($filters);
        
        $previousFilters = $filters;
        $previousFilters['date_from'] = Carbon::parse($filters['date_from'])->subDays(30)->startOfDay();
        $previousFilters['date_to'] = Carbon::parse($filters['date_from'])->subDay()->endOfDay();
        $previousPeriod = $this->getPeriodMetrics($previousFilters);
        
        return [
            'calls_trend' => $this->calculateTrend($currentPeriod['calls'], $previousPeriod['calls']),
            'conversions_trend' => $this->calculateTrend($currentPeriod['conversions'], $previousPeriod['conversions']),
            'sentiment_trend' => $this->calculateTrend($currentPeriod['sentiment'], $previousPeriod['sentiment']),
            'efficiency_trend' => $this->calculateTrend($currentPeriod['efficiency'], $previousPeriod['efficiency']),
        ];
    }
    
    protected function getPeriodMetrics(array $filters): array
    {
        $query = Call::whereBetween('created_at', [$filters['date_from'], $filters['date_to']]);
        
        if (isset($filters['company_id'])) {
            $query->where('company_id', $filters['company_id']);
        }
        
        $metrics = $query->selectRaw('
            COUNT(*) as calls,
            COUNT(CASE WHEN appointment_made = 1 THEN 1 END) as conversions,
            AVG(CASE WHEN sentiment_score IS NOT NULL THEN sentiment_score END) as sentiment,
            AVG(duration_sec) as avg_duration
        ')->first();
        
        return [
            'calls' => $metrics->calls,
            'conversions' => $metrics->conversions,
            'sentiment' => $metrics->sentiment ?? 0,
            'efficiency' => $metrics->avg_duration > 0 ? 300 / $metrics->avg_duration : 0, // Efficiency based on duration
        ];
    }
    
    protected function calculateTrend(float $current, float $previous): array
    {
        if ($previous == 0) {
            return ['change' => 0, 'direction' => 'neutral', 'percentage' => 0];
        }
        
        $change = $current - $previous;
        $percentage = ($change / $previous) * 100;
        $direction = $change > 0 ? 'up' : ($change < 0 ? 'down' : 'neutral');
        
        return [
            'change' => round($change, 2),
            'direction' => $direction,
            'percentage' => round($percentage, 1),
        ];
    }
    
    protected function generateQuickInsights(array $analytics): array
    {
        $insights = [];
        
        $overview = $analytics['overview'] ?? [];
        $agentPerformance = $analytics['agent_performance'] ?? [];
        $conversionFunnel = $analytics['conversion_funnel'] ?? [];
        
        // Performance insights
        if (($overview['conversion_rate'] ?? 0) > 30) {
            $insights[] = [
                'type' => 'success',
                'title' => 'Excellent Conversion Performance',
                'message' => 'Your conversion rate of ' . ($overview['conversion_rate'] ?? 0) . '% is above industry average.',
                'icon' => '💪',
            ];
        } elseif (($overview['conversion_rate'] ?? 0) < 15) {
            $insights[] = [
                'type' => 'warning',
                'title' => 'Conversion Rate Opportunity',
                'message' => 'Consider optimizing agent scripts and follow-up processes.',
                'icon' => '🎯',
            ];
        }
        
        // Sentiment insights
        if (($overview['avg_sentiment_score'] ?? 0) < 3.0) {
            $insights[] = [
                'type' => 'warning',
                'title' => 'Customer Sentiment Alert',
                'message' => 'Customer satisfaction scores are below optimal levels.',
                'icon' => '🙁',
            ];
        }
        
        // Agent performance insights
        $topPerformer = $agentPerformance['top_performer'] ?? null;
        if ($topPerformer && ($topPerformer['conversion_rate'] ?? 0) > 40) {
            $insights[] = [
                'type' => 'info',
                'title' => 'Top Performer Spotlight',
                'message' => $topPerformer['agent_name'] . ' achieved ' . $topPerformer['conversion_rate'] . '% conversion rate.',
                'icon' => '🏆',
            ];
        }
        
        // Cost efficiency insights
        if (($overview['cost_efficiency_score'] ?? 0) < 60) {
            $insights[] = [
                'type' => 'info',
                'title' => 'Cost Optimization Opportunity',
                'message' => 'Review AI usage patterns to improve cost efficiency.',
                'icon' => '💰',
            ];
        }
        
        return array_slice($insights, 0, 3); // Limit to 3 insights
    }
    
    public static function canView(): bool
    {
        return auth()->check();
    }
}
