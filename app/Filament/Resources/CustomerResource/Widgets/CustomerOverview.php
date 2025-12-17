<?php

namespace App\Filament\Resources\CustomerResource\Widgets;

use App\Models\Customer;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class CustomerOverview extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        // Cache for 5 minutes with 5-minute key granularity (aligned with other widgets)
        $cacheMinute = floor(now()->minute / 5) * 5;
        return Cache::remember('customer-overview-stats-' . now()->format('Y-m-d-H') . '-' . str_pad($cacheMinute, 2, '0', STR_PAD_LEFT), 300, function () {
            return $this->calculateStats();
        });
    }

    private function calculateStats(): array
    {
        // Optimized single query for all customer stats
        $stats = Customer::selectRaw("
            COUNT(*) as total_customers,
            COUNT(CASE WHEN journey_status = 'customer' OR journey_status = 'regular' OR journey_status = 'vip' THEN 1 END) as active_customers,
            COUNT(CASE WHEN created_at >= ? THEN 1 END) as new_this_month,
            COUNT(CASE WHEN journey_status = 'at_risk' THEN 1 END) as at_risk_count,
            COUNT(CASE WHEN journey_status = 'churned' THEN 1 END) as churned_count,
            AVG(total_revenue) as avg_lifetime_value,
            SUM(total_revenue) as total_revenue,
            COUNT(CASE WHEN is_vip = 1 THEN 1 END) as vip_count
        ", [now()->startOfMonth()])->first();

        // Calculate growth rate
        $lastMonthCount = Cache::remember('customer-last-month-count', 3600, function () {
            return Customer::where('created_at', '<', now()->startOfMonth())
                ->where('created_at', '>=', now()->subMonth()->startOfMonth())
                ->count();
        });

        $growthRate = $lastMonthCount > 0
            ? round((($stats->new_this_month - $lastMonthCount) / $lastMonthCount) * 100, 1)
            : 0;

        // Get journey distribution for chart
        $journeyData = $this->getJourneyDistribution();

        // Calculate retention rate (customers with activity in last 90 days)
        $retentionRate = $stats->total_customers > 0
            ? round((($stats->active_customers / $stats->total_customers) * 100), 1)
            : 0;

        return [
            Stat::make('Gesamtkunden', number_format($stats->total_customers))
                ->description(($growthRate >= 0 ? '+' : '') . $growthRate . '% diesen Monat')
                ->descriptionIcon($growthRate >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->chart($journeyData)
                ->color($growthRate >= 0 ? 'success' : 'danger'),

            Stat::make('Aktive Kunden', number_format($stats->active_customers))
                ->description($stats->vip_count . ' VIP-Kunden')
                ->descriptionIcon('heroicon-m-star')
                ->color('primary'),

            Stat::make('Durchschnittlicher Umsatz', '€' . number_format($stats->avg_lifetime_value ?? 0, 2))
                ->description('Lifetime Value | Total: €' . number_format($stats->total_revenue ?? 0, 2))
                ->descriptionIcon('heroicon-m-currency-euro')
                ->color('success'),

            Stat::make('Gefährdete Kunden', $stats->at_risk_count)
                ->description($stats->churned_count . ' verloren')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color($stats->at_risk_count > 10 ? 'danger' : 'warning'),

            Stat::make('Retention Rate', $retentionRate . '%')
                ->description(number_format($stats->active_customers) . ' von ' . number_format($stats->total_customers) . ' aktiv')
                ->descriptionIcon('heroicon-m-arrow-path')
                ->color($retentionRate > 80 ? 'success' : ($retentionRate > 60 ? 'warning' : 'danger')),
        ];
    }

    private function getJourneyDistribution(): array
    {
        $data = Customer::selectRaw('journey_status, COUNT(*) as count')
            ->groupBy('journey_status')
            ->pluck('count')
            ->toArray();

        return array_values($data);
    }
}