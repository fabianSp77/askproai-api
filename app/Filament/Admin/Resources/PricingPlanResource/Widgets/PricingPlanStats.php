<?php

namespace App\Filament\Admin\Resources\PricingPlanResource\Widgets;

use App\Models\PricingPlan;
use App\Models\Tenant;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class PricingPlanStats extends BaseWidget
{
    protected function getStats(): array
    {
        $totalPlans = PricingPlan::count();
        $activePlans = PricingPlan::where('is_active', true)->count();
        $defaultPlan = PricingPlan::where('is_default', true)->first();
        $tenantsWithPlans = Tenant::whereHas('pricingPlan')->count();
        
        // Beliebtester Plan
        $mostPopularPlan = PricingPlan::withCount('tenants')
            ->orderBy('tenants_count', 'desc')
            ->first();
        
        // Durchschnittlicher Minutenpreis
        $avgPricePerMinute = PricingPlan::where('is_active', true)
            ->avg('price_per_minute_cents') ?? 0;
        
        return [
            Stat::make('Aktive Preismodelle', $activePlans . ' / ' . $totalPlans)
                ->description($totalPlans - $activePlans . ' inaktiv')
                ->descriptionIcon('heroicon-m-currency-euro')
                ->color('success'),
            
            Stat::make('Standard-Plan', $defaultPlan ? $defaultPlan->name : 'Keiner')
                ->description($defaultPlan ? number_format($defaultPlan->price_per_minute_cents / 100, 2) . ' €/Min' : 'Nicht definiert')
                ->descriptionIcon('heroicon-m-star')
                ->color($defaultPlan ? 'warning' : 'danger'),
            
            Stat::make('Beliebtester Plan', $mostPopularPlan ? $mostPopularPlan->name : 'Keine Daten')
                ->description($mostPopularPlan ? $mostPopularPlan->tenants_count . ' Tenants' : 'Noch keine Zuweisungen')
                ->descriptionIcon('heroicon-m-chart-bar')
                ->color('info'),
            
            Stat::make('Ø Minutenpreis', number_format($avgPricePerMinute / 100, 2) . ' €')
                ->description('Über alle aktiven Pläne')
                ->descriptionIcon('heroicon-m-calculator')
                ->color('primary'),
        ];
    }
}