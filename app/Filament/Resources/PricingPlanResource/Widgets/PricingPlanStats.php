<?php

namespace App\Filament\Resources\PricingPlanResource\Widgets;

use App\Models\PricingPlan;
use App\Models\Tenant;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class PricingPlanStats extends BaseWidget
{
    protected static ?int $sort = 1;

    /**
     * Widget disabled - pricing_plans table doesn't exist in Sept 21 database backup
     * TODO: Re-enable when database is fully restored
     */
    public static function canView(): bool
    {
        return false;
    }

    protected function getStats(): array
    {
        // Cache for 5 minutes with 5-minute key granularity
        $cacheMinute = floor(now()->minute / 5) * 5;
        return Cache::remember('pricing-plan-stats-' . now()->format('Y-m-d-H') . '-' . str_pad($cacheMinute, 2, '0', STR_PAD_LEFT), 300, function () {
            return $this->calculateStats();
        });
    }

    private function calculateStats(): array
    {
        // Optimized query for plan stats
        $planStats = PricingPlan::selectRaw("
            COUNT(*) as total_plans,
            COUNT(CASE WHEN is_active = 1 THEN 1 END) as active_plans,
            COUNT(CASE WHEN is_popular = 1 THEN 1 END) as popular_plans,
            AVG(CASE WHEN is_active = 1 THEN price_monthly END) as avg_monthly_price
        ")->first();

        // Optimized query for subscription stats
        $subscriptionStats = Tenant::selectRaw("
            COUNT(*) as total_subscriptions,
            COUNT(CASE WHEN is_active = 1 THEN 1 END) as active_subscriptions,
            pricing_plan
        ")
            ->where('is_active', true)
            ->groupBy('pricing_plan')
            ->get();

        // Calculate MRR by joining pricing plans - group by plan to calculate correctly
        $mrrData = DB::table('tenants')
            ->join('pricing_plans', 'tenants.pricing_plan', '=', 'pricing_plans.internal_name')
            ->where('tenants.is_active', true)
            ->where('pricing_plans.is_active', true)
            ->selectRaw('pricing_plans.internal_name, COUNT(tenants.id) * pricing_plans.price_monthly as mrr')
            ->groupBy('pricing_plans.internal_name', 'pricing_plans.price_monthly')
            ->get();

        $totalMRR = $mrrData->sum('mrr');
        $totalActiveSubscriptions = $subscriptionStats->sum('active_subscriptions');

        // Find most popular plan
        $mostPopularPlan = $subscriptionStats->sortByDesc('active_subscriptions')->first();
        $popularPlanName = $mostPopularPlan ?
            PricingPlan::where('internal_name', $mostPopularPlan->pricing_plan)->value('name') ?? $mostPopularPlan->pricing_plan
            : 'N/A';

        return [
            Stat::make('Preispläne Gesamt', number_format($planStats->total_plans ?? 0))
                ->description(($planStats->active_plans ?? 0) . ' aktiv | ' . ($planStats->popular_plans ?? 0) . ' beliebt')
                ->descriptionIcon('heroicon-m-tag')
                ->color('primary'),

            Stat::make('Aktive Abonnements', number_format($totalActiveSubscriptions))
                ->description('Über alle Preispläne')
                ->descriptionIcon('heroicon-m-users')
                ->color('success'),

            Stat::make('Monatlicher Umsatz (MRR)', '€ ' . number_format($totalMRR, 2, ',', '.'))
                ->description('Aus ' . $totalActiveSubscriptions . ' Abos')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('success'),

            Stat::make('Ø Preis/Monat', '€ ' . number_format($planStats->avg_monthly_price ?? 0, 2, ',', '.'))
                ->description('Durchschnitt aktiver Pläne')
                ->descriptionIcon('heroicon-m-calculator')
                ->color('info'),

            Stat::make('Beliebtester Plan', $popularPlanName)
                ->description(($mostPopularPlan->active_subscriptions ?? 0) . ' Abonnenten')
                ->descriptionIcon('heroicon-m-star')
                ->color('warning'),
        ];
    }
}
