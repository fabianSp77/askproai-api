<?php

namespace App\Filament\Admin\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Services\TieredPricingService;
use Carbon\Carbon;

class ResellerMarginWidget extends BaseWidget
{
    protected static ?string $pollingInterval = '30s';
    
    protected static ?int $sort = 10;
    
    public static function canView(): bool
    {
        $user = auth()->user();
        return $user->company && $user->company->isReseller();
    }

    protected function getStats(): array
    {
        $company = auth()->user()->company;
        $pricingService = new TieredPricingService();
        
        $currentMonth = Carbon::now();
        $lastMonth = Carbon::now()->subMonth();
        
        // Get current month margin report
        $currentReport = $pricingService->getMarginReport(
            $company,
            $currentMonth->copy()->startOfMonth(),
            $currentMonth->copy()->endOfMonth()
        );
        
        // Get last month for comparison
        $lastReport = $pricingService->getMarginReport(
            $company,
            $lastMonth->copy()->startOfMonth(),
            $lastMonth->copy()->endOfMonth()
        );
        
        return [
            Stat::make('Umsatz (Monat)', $this->formatCurrency($currentReport['totals']['revenue']))
                ->description($this->getPercentageChange($currentReport['totals']['revenue'], $lastReport['totals']['revenue']))
                ->descriptionIcon($this->getTrendIcon($currentReport['totals']['revenue'], $lastReport['totals']['revenue']))
                ->color($this->getTrendColor($currentReport['totals']['revenue'], $lastReport['totals']['revenue']))
                ->chart($this->getMonthlyRevenueChart()),
                
            Stat::make('Marge (Monat)', $this->formatCurrency($currentReport['totals']['margin']))
                ->description($currentReport['totals']['margin_percentage'] . '% Marge')
                ->descriptionIcon('heroicon-m-currency-euro')
                ->color('success')
                ->chart($this->getMonthlyMarginChart()),
                
            Stat::make('Aktive Kunden', $company->childCompanies()->count())
                ->description($company->childCompanies()->whereHas('calls', function ($q) {
                    $q->whereDate('created_at', '>=', now()->subDays(30));
                })->count() . ' mit Anrufen (30 Tage)')
                ->descriptionIcon('heroicon-m-building-office')
                ->color('primary'),
                
            Stat::make('Durchschn. Marge/Kunde', $this->calculateAverageMarginPerClient($currentReport))
                ->description('Pro aktivem Kunden')
                ->descriptionIcon('heroicon-m-chart-bar')
                ->color('warning'),
        ];
    }
    
    private function formatCurrency($amount): string
    {
        return number_format($amount, 2, ',', '.') . ' €';
    }
    
    private function getPercentageChange($current, $previous): string
    {
        if ($previous == 0) {
            return $current > 0 ? '+100%' : '0%';
        }
        
        $change = (($current - $previous) / $previous) * 100;
        $sign = $change > 0 ? '+' : '';
        
        return $sign . round($change, 1) . '% zum Vormonat';
    }
    
    private function getTrendIcon($current, $previous): string
    {
        if ($current > $previous) {
            return 'heroicon-m-trending-up';
        } elseif ($current < $previous) {
            return 'heroicon-m-trending-down';
        }
        
        return 'heroicon-m-minus';
    }
    
    private function getTrendColor($current, $previous): string
    {
        if ($current > $previous) {
            return 'success';
        } elseif ($current < $previous) {
            return 'danger';
        }
        
        return 'gray';
    }
    
    private function calculateAverageMarginPerClient($report): string
    {
        $activeClients = count(array_filter($report['clients'], fn($c) => $c['revenue'] > 0));
        
        if ($activeClients == 0) {
            return $this->formatCurrency(0);
        }
        
        return $this->formatCurrency($report['totals']['margin'] / $activeClients);
    }
    
    private function getMonthlyRevenueChart(): array
    {
        // Simple chart data for the last 7 days
        $data = [];
        for ($i = 6; $i >= 0; $i--) {
            $data[] = rand(800, 1500); // Replace with actual data
        }
        return $data;
    }
    
    private function getMonthlyMarginChart(): array
    {
        // Simple chart data for the last 7 days
        $data = [];
        for ($i = 6; $i >= 0; $i--) {
            $data[] = rand(200, 400); // Replace with actual data
        }
        return $data;
    }
}