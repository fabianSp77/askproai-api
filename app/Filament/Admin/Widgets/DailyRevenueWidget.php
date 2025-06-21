<?php

namespace App\Filament\Admin\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\Appointment;
use App\Models\CalcomEventType;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

class DailyRevenueWidget extends BaseWidget
{
    protected static ?int $sort = 2;
    
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
        $cacheKey = "daily-revenue-{$this->companyId}-{$this->selectedBranchId}";
        
        $data = Cache::remember($cacheKey, 300, function () {
            return $this->calculateRevenueStats();
        });

        return [
            Stat::make('Heutiger Umsatz', $this->formatCurrency($data['today']))
                ->description($data['trend_text'])
                ->descriptionIcon($data['trend_icon'])
                ->color($data['trend_color'])
                ->chart($data['daily_chart']),
                
            Stat::make('Wochenumsatz', $this->formatCurrency($data['week']))
                ->description("Ziel: " . $this->formatCurrency($data['week_target']))
                ->color($data['week_on_target'] ? 'success' : 'warning'),
                
            Stat::make('Monatsumsatz', $this->formatCurrency($data['month']))
                ->description($data['month_progress'] . '% des Ziels')
                ->color($data['month_on_target'] ? 'success' : 'danger'),
        ];
    }

    protected function calculateRevenueStats(): array
    {
        // Heute
        $todayRevenue = $this->getRevenue(Carbon::today(), Carbon::today()->endOfDay());
        $yesterdayRevenue = $this->getRevenue(Carbon::yesterday(), Carbon::yesterday()->endOfDay());
        
        // Trend berechnen
        $trend = $yesterdayRevenue > 0 ? (($todayRevenue - $yesterdayRevenue) / $yesterdayRevenue) * 100 : 0;
        
        // Woche
        $weekRevenue = $this->getRevenue(Carbon::now()->startOfWeek(), Carbon::now());
        $weekTarget = $this->getWeeklyTarget();
        
        // Monat
        $monthRevenue = $this->getRevenue(Carbon::now()->startOfMonth(), Carbon::now());
        $monthTarget = $this->getMonthlyTarget();
        $monthProgress = $monthTarget > 0 ? ($monthRevenue / $monthTarget) * 100 : 0;
        
        // Chart Daten (letzte 7 Tage)
        $dailyChart = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::today()->subDays($i);
            $dailyChart[] = $this->getRevenue($date, $date->endOfDay());
        }

        return [
            'today' => $todayRevenue,
            'yesterday' => $yesterdayRevenue,
            'trend' => $trend,
            'trend_text' => $trend > 0 ? '+' . round($trend) . '% vs. gestern' : round($trend) . '% vs. gestern',
            'trend_icon' => $trend > 0 ? 'heroicon-o-arrow-trending-up' : 'heroicon-o-arrow-trending-down',
            'trend_color' => $trend > 0 ? 'success' : 'danger',
            'week' => $weekRevenue,
            'week_target' => $weekTarget,
            'week_on_target' => $weekRevenue >= ($weekTarget * 0.8), // 80% als Schwellenwert
            'month' => $monthRevenue,
            'month_target' => $monthTarget,
            'month_progress' => round($monthProgress),
            'month_on_target' => $monthProgress >= 80,
            'daily_chart' => $dailyChart,
        ];
    }

    protected function getRevenue(Carbon $start, Carbon $end): float
    {
        return Appointment::query()
            ->when($this->companyId, fn($q) => $q->where('company_id', $this->companyId))
            ->when($this->selectedBranchId, fn($q) => $q->where('branch_id', $this->selectedBranchId))
            ->whereBetween('starts_at', [$start, $end])
            ->whereIn('status', ['completed', 'confirmed'])
            ->sum('price') ?? 0;
    }

    protected function getWeeklyTarget(): float
    {
        // Durchschnitt der letzten 4 Wochen als Ziel
        $fourWeeksAgo = Carbon::now()->subWeeks(4)->startOfWeek();
        $lastWeek = Carbon::now()->subWeek()->endOfWeek();
        
        $totalRevenue = Appointment::query()
            ->when($this->companyId, fn($q) => $q->where('company_id', $this->companyId))
            ->when($this->selectedBranchId, fn($q) => $q->where('branch_id', $this->selectedBranchId))
            ->whereBetween('starts_at', [$fourWeeksAgo, $lastWeek])
            ->whereIn('status', ['completed', 'confirmed'])
            ->sum('price') ?? 0;
            
        return $totalRevenue / 4;
    }

    protected function getMonthlyTarget(): float
    {
        // Durchschnitt der letzten 3 Monate als Ziel
        $threeMonthsAgo = Carbon::now()->subMonths(3)->startOfMonth();
        $lastMonth = Carbon::now()->subMonth()->endOfMonth();
        
        $totalRevenue = Appointment::query()
            ->when($this->companyId, fn($q) => $q->where('company_id', $this->companyId))
            ->when($this->selectedBranchId, fn($q) => $q->where('branch_id', $this->selectedBranchId))
            ->whereBetween('starts_at', [$threeMonthsAgo, $lastMonth])
            ->whereIn('status', ['completed', 'confirmed'])
            ->sum('price') ?? 0;
            
        return $totalRevenue / 3;
    }

    protected function formatCurrency(float $amount): string
    {
        return number_format($amount, 2, ',', '.') . ' €';
    }
}