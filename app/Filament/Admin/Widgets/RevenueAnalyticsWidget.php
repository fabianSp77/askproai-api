<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Tenant;
use App\Models\Call;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Carbon\Carbon;

class RevenueAnalyticsWidget extends BaseWidget
{
    protected static ?int $sort = 3;
    
    protected int | string | array $columnSpan = 'full';
    
    protected function getStats(): array
    {
        $tenant = Tenant::first();
        $balance = $tenant ? $tenant->balance_cents / 100 : 0;
        
        // Calculate estimated costs based on calls
        $todayCalls = Call::whereDate('created_at', Carbon::today())->count();
        $monthCalls = Call::where('created_at', '>=', Carbon::now()->startOfMonth())->count();
        
        // Estimate: $0.10 per minute average
        $todayMinutes = Call::whereDate('created_at', Carbon::today())
            ->sum('duration_sec') / 60;
        $todayCost = round($todayMinutes * 0.10, 2);
        
        $monthMinutes = Call::where('created_at', '>=', Carbon::now()->startOfMonth())
            ->sum('duration_sec') / 60;
        $monthCost = round($monthMinutes * 0.10, 2);
        
        // Calculate trend
        $lastMonthMinutes = Call::whereBetween('created_at', [
            Carbon::now()->subMonth()->startOfMonth(),
            Carbon::now()->subMonth()->endOfMonth()
        ])->sum('duration_sec') / 60;
        $lastMonthCost = round($lastMonthMinutes * 0.10, 2);
        
        $costTrend = $lastMonthCost > 0 
            ? round((($monthCost - $lastMonthCost) / $lastMonthCost) * 100, 1)
            : 0;
        
        return [
            Stat::make('Account Balance', '$' . number_format($balance, 2))
                ->description($balance > 1000 ? 'Healthy balance' : 'Consider topping up')
                ->descriptionIcon($balance > 1000 ? 'heroicon-m-check-circle' : 'heroicon-m-exclamation-triangle')
                ->color($balance > 1000 ? 'success' : 'warning'),
            
            Stat::make('Today\'s Usage', '$' . number_format($todayCost, 2))
                ->description($todayCalls . ' calls, ' . round($todayMinutes) . ' minutes')
                ->descriptionIcon('heroicon-m-clock')
                ->color('info'),
            
            Stat::make('Monthly Cost', '$' . number_format($monthCost, 2))
                ->description($costTrend >= 0 ? '+' . $costTrend . '% vs last month' : $costTrend . '% vs last month')
                ->descriptionIcon($costTrend >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($costTrend > 20 ? 'warning' : 'success')
                ->chart($this->getMonthlyTrend()),
        ];
    }
    
    protected function getMonthlyTrend(): array
    {
        $data = [];
        for ($i = 6; $i >= 0; $i--) {
            $dayMinutes = Call::whereDate('created_at', Carbon::today()->subDays($i))
                ->sum('duration_sec') / 60;
            $data[] = round($dayMinutes * 0.10, 2);
        }
        return $data;
    }
    
    public static function canView(): bool
    {
        return auth()->user()?->hasRole('Admin') ?? false;
    }
}