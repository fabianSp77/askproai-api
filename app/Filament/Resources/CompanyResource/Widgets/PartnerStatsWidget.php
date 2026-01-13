<?php

namespace App\Filament\Resources\CompanyResource\Widgets;

use App\Models\Company;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Cache;

class PartnerStatsWidget extends StatsOverviewWidget
{
    protected static ?string $pollingInterval = '60s';

    protected function getStats(): array
    {
        $stats = Cache::remember('partner_stats_widget', 300, function () {
            $totalPartners = Company::where('is_partner', true)->count();

            return [
                'total_partners' => $totalPartners,
                'total_managed' => Company::whereNotNull('managed_by_company_id')->count(),
                'unassigned' => Company::whereNull('managed_by_company_id')
                    ->where('is_partner', false)
                    ->where('is_active', true)
                    ->count(),
                'avg_managed' => $totalPartners > 0
                    ? Company::where('is_partner', true)
                        ->withCount('managedCompanies')
                        ->get()
                        ->avg('managed_companies_count') ?? 0
                    : 0,
            ];
        });

        return [
            Stat::make('Partner', $stats['total_partners'])
                ->description('Partner-Unternehmen')
                ->icon('heroicon-o-star')
                ->color('success'),

            Stat::make('Verwaltet', $stats['total_managed'])
                ->description('Von Partnern verwaltete Firmen')
                ->icon('heroicon-o-arrow-right-circle')
                ->color('info'),

            Stat::make('Ohne Zuordnung', $stats['unassigned'])
                ->description('Aktive Firmen ohne Partner')
                ->icon('heroicon-o-exclamation-triangle')
                ->color($stats['unassigned'] > 5 ? 'warning' : 'gray'),

            Stat::make('Durchschnitt', number_format($stats['avg_managed'], 1))
                ->description('Firmen pro Partner')
                ->icon('heroicon-o-chart-bar')
                ->color('primary'),
        ];
    }
}
