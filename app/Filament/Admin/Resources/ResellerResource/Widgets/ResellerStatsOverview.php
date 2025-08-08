<?php

namespace App\Filament\Admin\Resources\ResellerResource\Widgets;

use App\Models\Company;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ResellerStatsOverview extends BaseWidget
{
    protected function getStats(): array
    {
        // Use cached aggregated stats from service
        $metricsService = app(\App\Services\ResellerMetricsService::class);
        $stats = $metricsService->getAggregatedStats();
        
        $totalResellers = $stats['total_resellers'] ?? 0;
        $activeResellers = $stats['active_resellers'] ?? 0;
        $totalClients = $stats['total_clients'] ?? 0;
        $totalRevenue = $stats['total_revenue'] ?? 0;

        return [
            Stat::make('Total Resellers', $totalResellers)
                ->description($activeResellers . ' active')
                ->descriptionIcon($activeResellers > 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($activeResellers > 0 ? 'success' : 'danger')
                ->chart([7, 2, 10, 3, 15, 4, 17]),

            Stat::make('Total Clients', $totalClients)
                ->description('Across all resellers')
                ->descriptionIcon('heroicon-m-users')
                ->color('primary')
                ->chart([15, 4, 10, 2, 12, 4, 12]),

            Stat::make('YTD Revenue', '€' . number_format($totalRevenue, 2))
                ->description('Total revenue from resellers')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('success')
                ->chart([2, 10, 5, 22, 8, 32, 18]),

            Stat::make('Avg. Clients per Reseller', $totalResellers > 0 ? round($totalClients / $totalResellers, 1) : 0)
                ->description('Distribution efficiency')
                ->descriptionIcon('heroicon-m-chart-bar')
                ->color('warning'),
        ];
    }

    protected function getColumns(): int
    {
        return 4;
    }
}