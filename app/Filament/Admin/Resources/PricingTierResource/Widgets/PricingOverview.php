<?php

namespace App\Filament\Admin\Resources\PricingTierResource\Widgets;

use App\Models\SecureCompanyPricingTier as CompanyPricingTier;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class PricingOverview extends BaseWidget
{
    protected function getStats(): array
    {
        $user = auth()->user();
        
        // Base query
        $query = CompanyPricingTier::query();
        
        // Filter by company for non-super admins
        if (!$user->hasRole('super_admin')) {
            $query->where('company_id', $user->company_id);
        }
        
        $activeCount = (clone $query)->where('is_active', true)->count();
        $totalCount = (clone $query)->count();
        
        // Calculate average margin
        $avgMargin = 0;
        if ($activeCount > 0) {
            $activeTiers = (clone $query)->where('is_active', true)->get();
            $totalMargin = 0;
            $validTiers = 0;
            
            foreach ($activeTiers as $tier) {
                if ($tier->cost_price > 0) {
                    $margin = (($tier->sell_price - $tier->cost_price) / $tier->cost_price) * 100;
                    $totalMargin += $margin;
                    $validTiers++;
                }
            }
            
            $avgMargin = $validTiers > 0 ? round($totalMargin / $validTiers, 1) : 0;
        }
        
        return [
            Stat::make('Aktive Preismodelle', $activeCount)
                ->description("von {$totalCount} gesamt")
                ->icon('heroicon-o-currency-euro')
                ->color('success'),
                
            Stat::make('Durchschnittliche Marge', $avgMargin . '%')
                ->description('Über alle aktiven Preise')
                ->icon('heroicon-o-chart-bar')
                ->color($avgMargin > 20 ? 'success' : 'warning'),
                
            Stat::make('Pricing Typen', (clone $query)->distinct('pricing_type')->count('pricing_type'))
                ->description('Verschiedene Abrechnungsarten')
                ->icon('heroicon-o-tag')
                ->color('primary'),
        ];
    }
    
    /**
     * Polling interval in seconds (null = no polling)
     */
    protected static ?string $pollingInterval = null;
}