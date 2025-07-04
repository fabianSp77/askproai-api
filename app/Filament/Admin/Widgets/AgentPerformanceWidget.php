<?php

namespace App\Filament\Admin\Widgets;

use App\Models\AgentPerformanceMetric;
use App\Models\Call;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AgentPerformanceWidget extends Widget
{
    protected static string $view = 'filament.widgets.agent-performance';
    
    protected int | string | array $columnSpan = 'full';
    
    public ?string $agentId = null;
    public string $dateRange = '7days';
    
    protected function getViewData(): array
    {
        $endDate = Carbon::today();
        $startDate = match($this->dateRange) {
            '30days' => $endDate->copy()->subDays(30),
            '90days' => $endDate->copy()->subDays(90),
            default => $endDate->copy()->subDays(7),
        };
        
        // Get default agent if not specified
        if (!$this->agentId) {
            $this->agentId = $this->getDefaultAgentId();
        }
        
        // Get performance metrics
        $metrics = AgentPerformanceMetric::where('agent_id', $this->agentId)
            ->whereBetween('date', [$startDate, $endDate])
            ->orderBy('date')
            ->get();
        
        // Calculate current performance score
        $latestMetric = $metrics->last();
        $performanceScore = $latestMetric ? $latestMetric->performance_score : 0;
        
        // Calculate trend
        $previousMetric = $metrics->count() > 1 ? $metrics->get($metrics->count() - 2) : null;
        $scoreTrend = $previousMetric 
            ? $performanceScore - $previousMetric->performance_score 
            : 0;
        
        // Prepare chart data
        $chartData = $this->prepareChartData($metrics);
        
        // Get correlation data
        $correlations = $this->calculateCorrelations($this->agentId, $startDate, $endDate);
        
        // Get top performing agents for comparison
        $topAgents = $this->getTopPerformingAgents($startDate, $endDate);
        
        return [
            'agentId' => $this->agentId,
            'dateRange' => $this->dateRange,
            'performanceScore' => $performanceScore,
            'scoreTrend' => $scoreTrend,
            'metrics' => $metrics,
            'chartData' => $chartData,
            'correlations' => $correlations,
            'topAgents' => $topAgents,
            'availableAgents' => $this->getAvailableAgents(),
        ];
    }
    
    protected function prepareChartData($metrics): array
    {
        $dates = [];
        $sentimentScores = [];
        $conversionRates = [];
        $callCounts = [];
        
        foreach ($metrics as $metric) {
            $dates[] = $metric->date->format('d.m');
            $sentimentScores[] = round(($metric->avg_sentiment_score + 1) * 50, 1); // Convert to 0-100 scale
            $conversionRates[] = round($metric->conversion_rate, 1);
            $callCounts[] = $metric->total_calls;
        }
        
        return [
            'labels' => $dates,
            'datasets' => [
                [
                    'label' => 'Sentiment Score',
                    'data' => $sentimentScores,
                    'borderColor' => 'rgb(59, 130, 246)',
                    'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                    'yAxisID' => 'y',
                ],
                [
                    'label' => 'Konversionsrate (%)',
                    'data' => $conversionRates,
                    'borderColor' => 'rgb(16, 185, 129)',
                    'backgroundColor' => 'rgba(16, 185, 129, 0.1)',
                    'yAxisID' => 'y',
                ],
                [
                    'label' => 'Anzahl Anrufe',
                    'data' => $callCounts,
                    'borderColor' => 'rgb(156, 163, 175)',
                    'backgroundColor' => 'rgba(156, 163, 175, 0.1)',
                    'yAxisID' => 'y1',
                    'type' => 'bar',
                ],
            ],
        ];
    }
    
    protected function calculateCorrelations(string $agentId, Carbon $startDate, Carbon $endDate): array
    {
        $data = DB::table('calls')
            ->leftJoin('ml_call_predictions', 'calls.id', '=', 'ml_call_predictions.call_id')
            ->where('calls.retell_agent_id', $agentId)
            ->whereBetween('calls.created_at', [$startDate, $endDate])
            ->whereNotNull('ml_call_predictions.sentiment_score')
            ->select([
                'ml_call_predictions.sentiment_score',
                'calls.duration_sec',
                DB::raw('CASE WHEN calls.appointment_id IS NOT NULL THEN 1 ELSE 0 END as has_appointment'),
                DB::raw('HOUR(calls.created_at) as hour_of_day'),
            ])
            ->get();
        
        if ($data->count() < 5) {
            return [
                'sentiment_conversion' => ['value' => 0, 'label' => 'Zu wenig Daten'],
                'duration_sentiment' => ['value' => 0, 'label' => 'Zu wenig Daten'],
                'time_success' => ['value' => 0, 'label' => 'Zu wenig Daten'],
            ];
        }
        
        // Calculate correlations
        $sentiments = $data->pluck('sentiment_score')->toArray();
        $appointments = $data->pluck('has_appointment')->toArray();
        $durations = $data->pluck('duration_sec')->toArray();
        
        $sentimentConversion = $this->pearsonCorrelation($sentiments, $appointments);
        $durationSentiment = $this->pearsonCorrelation($durations, $sentiments);
        
        return [
            'sentiment_conversion' => [
                'value' => $sentimentConversion,
                'label' => $this->getCorrelationLabel($sentimentConversion),
                'description' => 'Stimmung ↔ Konversion'
            ],
            'duration_sentiment' => [
                'value' => $durationSentiment,
                'label' => $this->getCorrelationLabel($durationSentiment),
                'description' => 'Dauer ↔ Stimmung'
            ],
        ];
    }
    
    protected function pearsonCorrelation(array $x, array $y): float
    {
        $n = count($x);
        if ($n !== count($y) || $n < 2) {
            return 0;
        }
        
        $sumX = array_sum($x);
        $sumY = array_sum($y);
        $sumXY = 0;
        $sumX2 = 0;
        $sumY2 = 0;
        
        for ($i = 0; $i < $n; $i++) {
            $sumXY += $x[$i] * $y[$i];
            $sumX2 += $x[$i] * $x[$i];
            $sumY2 += $y[$i] * $y[$i];
        }
        
        $numerator = ($n * $sumXY) - ($sumX * $sumY);
        $denominator = sqrt(($n * $sumX2 - $sumX * $sumX) * ($n * $sumY2 - $sumY * $sumY));
        
        if ($denominator == 0) {
            return 0;
        }
        
        return round($numerator / $denominator, 3);
    }
    
    protected function getCorrelationLabel(float $correlation): string
    {
        $abs = abs($correlation);
        
        if ($abs >= 0.8) return 'Sehr stark';
        if ($abs >= 0.6) return 'Stark';
        if ($abs >= 0.4) return 'Moderat';
        if ($abs >= 0.2) return 'Schwach';
        return 'Keine';
    }
    
    protected function getTopPerformingAgents(Carbon $startDate, Carbon $endDate): array
    {
        return AgentPerformanceMetric::select([
                'agent_id',
                DB::raw('AVG((avg_sentiment_score + 1) / 2 * 40 + LEAST(conversion_rate, 50) + COALESCE(avg_satisfaction_score, 0.5) * 10) as avg_score'),
                DB::raw('SUM(total_calls) as total_calls'),
                DB::raw('AVG(conversion_rate) as avg_conversion'),
            ])
            ->whereBetween('date', [$startDate, $endDate])
            ->groupBy('agent_id')
            ->orderByDesc('avg_score')
            ->limit(5)
            ->get()
            ->map(function ($agent) {
                return [
                    'agent_id' => $agent->agent_id,
                    'score' => round($agent->avg_score),
                    'calls' => $agent->total_calls,
                    'conversion' => round($agent->avg_conversion, 1),
                ];
            });
    }
    
    protected function getAvailableAgents(): array
    {
        return Call::whereNotNull('retell_agent_id')
            ->distinct()
            ->pluck('retell_agent_id')
            ->map(fn($id) => ['id' => $id, 'name' => "Agent {$id}"])
            ->toArray();
    }
    
    protected function getDefaultAgentId(): ?string
    {
        return Call::whereNotNull('retell_agent_id')
            ->orderBy('created_at', 'desc')
            ->value('retell_agent_id');
    }
}