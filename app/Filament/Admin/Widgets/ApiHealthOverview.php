<?php

namespace App\Filament\Admin\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Services\CircuitBreaker\CircuitBreaker;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ApiHealthOverview extends BaseWidget
{
    protected static ?int $sort = 1;
    
    protected int|string|array $columnSpan = 'full';
    
    protected function getStats(): array
    {
        $hourAgo = now()->subHour();
        $circuitBreakerStatus = CircuitBreaker::getStatus();
        
        // Get metrics for each service
        $metrics = DB::table('circuit_breaker_metrics')
            ->select('service', 'status', DB::raw('COUNT(*) as count'), DB::raw('AVG(duration_ms) as avg_duration'))
            ->where('created_at', '>=', $hourAgo)
            ->groupBy('service', 'status')
            ->get();
        
        $stats = [];
        
        // Overall System Health
        $totalCalls = $metrics->sum('count');
        $successCalls = $metrics->where('status', 'success')->sum('count');
        $overallSuccessRate = $totalCalls > 0 ? round(($successCalls / $totalCalls) * 100, 1) : 100;
        
        $systemHealthColor = $overallSuccessRate >= 95 ? 'success' : ($overallSuccessRate >= 80 ? 'warning' : 'danger');
        $systemHealthIcon = $overallSuccessRate >= 95 ? 'heroicon-o-check-circle' : ($overallSuccessRate >= 80 ? 'heroicon-o-exclamation-triangle' : 'heroicon-o-x-circle');
        
        $stats[] = Stat::make('System Health', $overallSuccessRate . '%')
            ->description($totalCalls . ' API calls in last hour')
            ->descriptionIcon('heroicon-o-arrow-trending-up')
            ->color($systemHealthColor)
            ->icon($systemHealthIcon);
        
        // Cal.com API Status
        $calcomMetrics = $metrics->where('service', 'calcom');
        $calcomTotal = $calcomMetrics->sum('count');
        $calcomSuccess = $calcomMetrics->where('status', 'success')->sum('count');
        $calcomSuccessRate = $calcomTotal > 0 ? round(($calcomSuccess / $calcomTotal) * 100, 1) : 100;
        $calcomAvgDuration = $calcomMetrics->where('status', 'success')->first()->avg_duration ?? 0;
        $calcomCircuitState = $circuitBreakerStatus['calcom']['state'] ?? 'closed';
        
        $calcomColor = $calcomCircuitState === 'open' ? 'danger' : ($calcomSuccessRate >= 90 ? 'success' : 'warning');
        
        $stats[] = Stat::make('Cal.com API', $calcomSuccessRate . '%')
            ->description($calcomCircuitState === 'open' ? 'Circuit OPEN' : round($calcomAvgDuration) . 'ms avg response')
            ->descriptionIcon($calcomCircuitState === 'open' ? 'heroicon-o-exclamation-circle' : 'heroicon-o-clock')
            ->color($calcomColor)
            ->icon('heroicon-o-calendar');
        
        // Retell.ai API Status
        $retellMetrics = $metrics->where('service', 'retell');
        $retellTotal = $retellMetrics->sum('count');
        $retellSuccess = $retellMetrics->where('status', 'success')->sum('count');
        $retellSuccessRate = $retellTotal > 0 ? round(($retellSuccess / $retellTotal) * 100, 1) : 100;
        $retellAvgDuration = $retellMetrics->where('status', 'success')->first()->avg_duration ?? 0;
        $retellCircuitState = $circuitBreakerStatus['retell']['state'] ?? 'closed';
        
        $retellColor = $retellCircuitState === 'open' ? 'danger' : ($retellSuccessRate >= 90 ? 'success' : 'warning');
        
        $stats[] = Stat::make('Retell.ai API', $retellSuccessRate . '%')
            ->description($retellCircuitState === 'open' ? 'Circuit OPEN' : round($retellAvgDuration) . 'ms avg response')
            ->descriptionIcon($retellCircuitState === 'open' ? 'heroicon-o-exclamation-circle' : 'heroicon-o-clock')
            ->color($retellColor)
            ->icon('heroicon-o-phone');
        
        // Critical Errors
        $criticalErrors = DB::table('critical_errors')
            ->where('created_at', '>=', $hourAgo)
            ->count();
        
        $errorColor = $criticalErrors === 0 ? 'success' : ($criticalErrors <= 5 ? 'warning' : 'danger');
        
        $stats[] = Stat::make('Critical Errors', $criticalErrors)
            ->description('In last hour')
            ->descriptionIcon('heroicon-o-clock')
            ->color($errorColor)
            ->icon('heroicon-o-exclamation-triangle')
            ->url('/admin/api-health-monitor');
        
        return $stats;
    }
    
    public function getPollingInterval(): ?string
    {
        return '30s';
    }
}