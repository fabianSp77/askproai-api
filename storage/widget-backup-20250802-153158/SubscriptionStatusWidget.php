<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Subscription;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Number;
use Carbon\Carbon;

class SubscriptionStatusWidget extends BaseWidget
{
    protected static ?string $pollingInterval = '30s';
    
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $activeSubscriptions = Subscription::active()->count();
        $trialSubscriptions = Subscription::where('stripe_status', 'trialing')->count();
        $needsAttention = Subscription::needsAttention()->count();
        
        // Calculate MRR (Monthly Recurring Revenue)
        // This is simplified - in reality, you'd calculate based on actual prices
        $mrr = Subscription::active()->sum('quantity') * 29; // Assuming $29/month base price
        
        // Subscriptions expiring this week
        $expiringThisWeek = Subscription::active()
            ->where('current_period_end', '<=', now()->addWeek())
            ->count();
        
        // Churn rate (last 30 days)
        $canceledLastMonth = Subscription::where('stripe_status', 'canceled')
            ->where('updated_at', '>=', now()->subMonth())
            ->count();
        $totalLastMonth = Subscription::where('created_at', '<=', now()->subMonth())->count();
        $churnRate = $totalLastMonth > 0 ? round(($canceledLastMonth / $totalLastMonth) * 100, 1) : 0;

        return [
            Stat::make('Active Subscriptions', $activeSubscriptions)
                ->description($trialSubscriptions . ' in trial')
                ->descriptionIcon('heroicon-m-sparkles')
                ->color('success')
                ->chart([7, 8, 8, 9, 10, 11, $activeSubscriptions]),
                
            Stat::make('Monthly Recurring Revenue', '€' . Number::format($mrr))
                ->description('Based on current subscriptions')
                ->descriptionIcon('heroicon-m-currency-euro')
                ->color('primary'),
                
            Stat::make('Needs Attention', $needsAttention)
                ->description($expiringThisWeek . ' expiring this week')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color($needsAttention > 0 ? 'danger' : 'gray'),
                
            Stat::make('Churn Rate', $churnRate . '%')
                ->description('Last 30 days')
                ->descriptionIcon('heroicon-m-arrow-trending-down')
                ->color($churnRate > 5 ? 'danger' : 'success'),
        ];
    }
}