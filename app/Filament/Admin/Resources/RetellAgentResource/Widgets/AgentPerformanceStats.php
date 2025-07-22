<?php

namespace App\Filament\Admin\Resources\RetellAgentResource\Widgets;

use App\Models\RetellAgent;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class AgentPerformanceStats extends BaseWidget
{
    protected function getStats(): array
    {
        $companyId = auth()->user()->company_id;
        
        $totalAgents = RetellAgent::where('company_id', $companyId)->count();
        $activeAgents = RetellAgent::where('company_id', $companyId)->active()->count();
        
        $performanceData = RetellAgent::where('company_id', $companyId)
            ->active()
            ->selectRaw('
                SUM(total_calls) as total_calls,
                SUM(successful_calls) as successful_calls,
                AVG(average_duration) as avg_duration,
                AVG(satisfaction_score) as avg_satisfaction
            ')
            ->first();
        
        $overallSuccessRate = $performanceData->total_calls > 0 
            ? round(($performanceData->successful_calls / $performanceData->total_calls) * 100, 1)
            : 0;
        
        return [
            Stat::make('Total Agents', $totalAgents)
                ->description("{$activeAgents} active")
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('primary'),
            
            Stat::make('Overall Success Rate', $overallSuccessRate . '%')
                ->description('Across all agents')
                ->descriptionIcon('heroicon-m-chart-bar')
                ->color($overallSuccessRate >= 80 ? 'success' : ($overallSuccessRate >= 60 ? 'warning' : 'danger'))
                ->chart($this->getSuccessRateChart()),
            
            Stat::make('Avg Call Duration', gmdate('i:s', $performanceData->avg_duration ?? 0))
                ->description('Per successful call')
                ->descriptionIcon('heroicon-m-clock')
                ->color('info'),
            
            Stat::make('Customer Satisfaction', number_format($performanceData->avg_satisfaction ?? 0, 1) . '/5')
                ->description('Average rating')
                ->descriptionIcon('heroicon-m-star')
                ->color($performanceData->avg_satisfaction >= 4 ? 'success' : 'warning'),
        ];
    }
    
    protected function getSuccessRateChart(): array
    {
        // Get last 7 days of success rates
        $data = RetellAgent::where('company_id', auth()->user()->company_id)
            ->active()
            ->join('calls', 'calls.metadata->agent_id', '=', 'retell_agents.retell_agent_id')
            ->where('calls.created_at', '>=', now()->subDays(7))
            ->selectRaw('DATE(calls.created_at) as date, 
                        COUNT(*) as total,
                        SUM(CASE WHEN calls.metadata->>"$.outcome" = "success" THEN 1 ELSE 0 END) as successful')
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->map(fn ($day) => $day->total > 0 ? round(($day->successful / $day->total) * 100) : 0)
            ->values()
            ->toArray();
        
        return array_pad($data, -7, 0);
    }
}