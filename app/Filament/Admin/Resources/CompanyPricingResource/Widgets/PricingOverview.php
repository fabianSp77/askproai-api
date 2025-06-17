<?php

namespace App\Filament\Admin\Resources\CompanyPricingResource\Widgets;

use App\Models\CompanyPricing;
use App\Models\Company;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class PricingOverview extends BaseWidget
{
    protected static ?string $pollingInterval = null;

    protected function getStats(): array
    {
        $user = Auth::user();
        $query = CompanyPricing::query();
        
        if (!$user->hasRole('Super Admin') && $user->company_id) {
            $query->where('company_id', $user->company_id);
        }
        
        $activePricings = $query->clone()->active()->count();
        $totalCompanies = $user->hasRole('Super Admin') 
            ? Company::count() 
            : 1;
        $companiesWithPricing = $query->clone()
            ->active()
            ->distinct('company_id')
            ->count('company_id');
        
        $avgPrice = $query->clone()
            ->active()
            ->avg('price_per_minute') ?? 0;
            
        $avgIncluded = $query->clone()
            ->active()
            ->avg('included_minutes') ?? 0;

        return [
            Stat::make('Aktive Preismodelle', $activePricings)
                ->description($companiesWithPricing . ' von ' . $totalCompanies . ' Firmen')
                ->color('success')
                ->icon('heroicon-o-currency-euro'),
                
            Stat::make('Ø Minutenpreis', '€' . number_format($avgPrice, 4, ',', '.'))
                ->description('Durchschnittlicher Preis')
                ->color('primary')
                ->icon('heroicon-o-calculator'),
                
            Stat::make('Ø Inklusivminuten', round($avgIncluded) . ' Min')
                ->description('Pro Monat')
                ->color('info')
                ->icon('heroicon-o-gift'),
        ];
    }
}