<?php

namespace App\Filament\Admin\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\Call;
use App\Models\Appointment;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

class AIPerformanceWidget extends BaseWidget
{
    protected static ?int $sort = 4;
    
    public ?int $companyId = null;
    public ?int $selectedBranchId = null;

    protected function getListeners(): array
    {
        return [
            'tenantFilterUpdated' => 'handleTenantFilterUpdate',
        ];
    }

    public function handleTenantFilterUpdate($companyId, $branchId): void
    {
        $this->companyId = $companyId;
        $this->selectedBranchId = $branchId;
    }

    protected function getStats(): array
    {
        $cacheKey = "ai-performance-{$this->companyId}-{$this->selectedBranchId}";
        
        $data = Cache::remember($cacheKey, 300, function () {
            return $this->calculateAIMetrics();
        });

        return [
            Stat::make('Konversionsrate', $data['conversion_rate'] . '%')
                ->description($data['conversion_trend'])
                ->descriptionIcon($data['conversion_icon'])
                ->color($data['conversion_color'])
                ->chart($data['conversion_chart']),
                
            Stat::make('Ø Anrufdauer', $this->formatDuration($data['avg_duration']))
                ->description('Optimal: 3-5 Min')
                ->color($data['duration_color']),
                
            Stat::make('KI-Erfolgsquote', $data['success_rate'] . '%')
                ->description($data['failed_calls'] . ' fehlgeschlagene Anrufe')
                ->color($data['success_color']),
        ];
    }

    protected function calculateAIMetrics(): array
    {
        $period = Carbon::now()->subDays(7);
        
        // Konversionsrate (Anrufe zu Terminen)
        $totalCalls = Call::query()
            ->when($this->companyId, function ($q) {
                $q->whereHas('branch', fn($q) => $q->where('company_id', $this->companyId));
            })
            ->when($this->selectedBranchId, fn($q) => $q->where('branch_id', $this->selectedBranchId))
            ->where('created_at', '>=', $period)
            ->count();
        
        $callsWithAppointments = Call::query()
            ->when($this->companyId, function ($q) {
                $q->whereHas('branch', fn($q) => $q->where('company_id', $this->companyId));
            })
            ->when($this->selectedBranchId, fn($q) => $q->where('branch_id', $this->selectedBranchId))
            ->where('created_at', '>=', $period)
            ->whereHas('appointments')
            ->count();
        
        $conversionRate = $totalCalls > 0 ? round(($callsWithAppointments / $totalCalls) * 100) : 0;
        
        // Trend berechnen
        $lastWeekRate = $this->getLastWeekConversionRate();
        $conversionTrend = $conversionRate - $lastWeekRate;
        
        // Durchschnittliche Anrufdauer
        $avgDuration = Call::query()
            ->when($this->companyId, function ($q) {
                $q->whereHas('branch', fn($q) => $q->where('company_id', $this->companyId));
            })
            ->when($this->selectedBranchId, fn($q) => $q->where('branch_id', $this->selectedBranchId))
            ->where('created_at', '>=', $period)
            ->where('duration_sec', '>', 0)
            ->avg('duration_sec') ?? 0;
        
        // Erfolgsquote (nicht fehlgeschlagene Anrufe)
        $failedCalls = Call::query()
            ->when($this->companyId, function ($q) {
                $q->whereHas('branch', fn($q) => $q->where('company_id', $this->companyId));
            })
            ->when($this->selectedBranchId, fn($q) => $q->where('branch_id', $this->selectedBranchId))
            ->where('created_at', '>=', $period)
            ->where('call_status', 'failed')
            ->count();
        
        $successRate = $totalCalls > 0 ? round((($totalCalls - $failedCalls) / $totalCalls) * 100) : 100;
        
        // Chart Daten für Konversion (letzte 7 Tage)
        $conversionChart = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::today()->subDays($i);
            $dayTotal = Call::query()
                ->when($this->companyId, function ($q) {
                    $q->whereHas('branch', fn($q) => $q->where('company_id', $this->companyId));
                })
                ->when($this->selectedBranchId, fn($q) => $q->where('branch_id', $this->selectedBranchId))
                ->whereDate('created_at', $date)
                ->count();
            
            $dayConverted = Call::query()
                ->when($this->companyId, function ($q) {
                    $q->whereHas('branch', fn($q) => $q->where('company_id', $this->companyId));
                })
                ->when($this->selectedBranchId, fn($q) => $q->where('branch_id', $this->selectedBranchId))
                ->whereDate('created_at', $date)
                ->whereHas('appointments')
                ->count();
            
            $conversionChart[] = $dayTotal > 0 ? round(($dayConverted / $dayTotal) * 100) : 0;
        }
        
        return [
            'conversion_rate' => $conversionRate,
            'conversion_trend' => $conversionTrend > 0 ? '+' . $conversionTrend . '%' : $conversionTrend . '%',
            'conversion_icon' => $conversionTrend > 0 ? 'heroicon-o-arrow-trending-up' : 'heroicon-o-arrow-trending-down',
            'conversion_color' => $conversionRate >= 30 ? 'success' : ($conversionRate >= 20 ? 'warning' : 'danger'),
            'conversion_chart' => $conversionChart,
            'avg_duration' => $avgDuration,
            'duration_color' => ($avgDuration >= 180 && $avgDuration <= 300) ? 'success' : 'warning',
            'success_rate' => $successRate,
            'failed_calls' => $failedCalls,
            'success_color' => $successRate >= 95 ? 'success' : ($successRate >= 90 ? 'warning' : 'danger'),
        ];
    }

    protected function getLastWeekConversionRate(): float
    {
        $start = Carbon::now()->subDays(14);
        $end = Carbon::now()->subDays(7);
        
        $totalCalls = Call::query()
            ->when($this->companyId, function ($q) {
                $q->whereHas('branch', fn($q) => $q->where('company_id', $this->companyId));
            })
            ->when($this->selectedBranchId, fn($q) => $q->where('branch_id', $this->selectedBranchId))
            ->whereBetween('created_at', [$start, $end])
            ->count();
        
        $callsWithAppointments = Call::query()
            ->when($this->companyId, function ($q) {
                $q->whereHas('branch', fn($q) => $q->where('company_id', $this->companyId));
            })
            ->when($this->selectedBranchId, fn($q) => $q->where('branch_id', $this->selectedBranchId))
            ->whereBetween('created_at', [$start, $end])
            ->whereHas('appointments')
            ->count();
        
        return $totalCalls > 0 ? round(($callsWithAppointments / $totalCalls) * 100) : 0;
    }

    protected function formatDuration(float $seconds): string
    {
        if ($seconds < 60) {
            return round($seconds) . ' Sek';
        }
        
        $minutes = floor($seconds / 60);
        $seconds = $seconds % 60;
        
        return $minutes . ':' . str_pad(round($seconds), 2, '0', STR_PAD_LEFT) . ' Min';
    }
}