<?php

namespace App\Filament\Resources\CompanyServicePricingResource\Widgets;

use App\Models\Company;
use App\Models\CompanyServicePricing;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class PricingStatsOverview extends BaseWidget
{
    protected function getStats(): array
    {
        $totalCompanies = Company::whereHas('servicePricings')->count();
        $activePricings = CompanyServicePricing::where('is_active', true)->currentlyValid()->count();
        $expiringSoon = CompanyServicePricing::expiringSoon(30)->count();
        $monthlyRevenue = CompanyServicePricing::where('is_active', true)
            ->currentlyValid()
            ->whereHas('template', fn ($q) => $q->where('pricing_type', 'monthly'))
            ->sum('final_price');

        return [
            Stat::make('Unternehmen mit Preisen', $totalCompanies)
                ->description('Individuelle Preisvereinbarungen')
                ->icon('heroicon-o-building-office')
                ->color('primary'),

            Stat::make('Aktive Preise', $activePricings)
                ->description('Aktuell gültige Vereinbarungen')
                ->icon('heroicon-o-check-circle')
                ->color('success'),

            Stat::make('Läuft bald ab', $expiringSoon)
                ->description('In den nächsten 30 Tagen')
                ->icon('heroicon-o-clock')
                ->color($expiringSoon > 0 ? 'warning' : 'gray'),

            Stat::make('Mtl. Einnahmen', number_format($monthlyRevenue, 0, ',', '.') . ' €')
                ->description('Aus monatlichen Services')
                ->icon('heroicon-o-currency-euro')
                ->color('success'),
        ];
    }
}
