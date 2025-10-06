<?php

namespace App\Filament\Widgets;

use App\Models\Call;
use App\Services\CostCalculator;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

class ProfitOverviewWidget extends StatsOverviewWidget
{
    protected static ?string $pollingInterval = '30s';
    protected static ?int $sort = 1;

    public static function canView(): bool
    {
        $user = auth()->user();
        if (!$user) return false;

        return $user->hasRole(['super-admin', 'super_admin', 'Super Admin']) ||
               $user->hasRole(['reseller_admin', 'reseller_owner', 'reseller_support']);
    }

    protected function getStats(): array
    {
        $user = auth()->user();
        $isSuperAdmin = $user->hasRole(['super-admin', 'super_admin', 'Super Admin']);
        $isReseller = $user->hasRole(['reseller_admin', 'reseller_owner', 'reseller_support']);

        $calculator = new CostCalculator();
        $cacheKey = 'profit-widget-' . ($isSuperAdmin ? 'super' : 'reseller') . '-' . $user->id;

        return Cache::remember($cacheKey, 60, function () use ($user, $isSuperAdmin, $isReseller, $calculator) {
            $query = Call::query();

            if ($isReseller && !$isSuperAdmin) {
                $query->whereHas('company', function ($q) use ($user) {
                    $q->where('parent_company_id', $user->company_id);
                });
            }

            // Today's profit
            $todayCalls = (clone $query)->whereDate('created_at', today())->get();
            $todayProfit = 0;
            $todayPlatformProfit = 0;
            $todayResellerProfit = 0;

            foreach ($todayCalls as $call) {
                $profitData = $calculator->getDisplayProfit($call, $user);
                if ($profitData['type'] !== 'none') {
                    $todayProfit += $profitData['profit'];

                    if ($isSuperAdmin && isset($profitData['breakdown'])) {
                        $todayPlatformProfit += $profitData['breakdown']['platform'];
                        $todayResellerProfit += $profitData['breakdown']['reseller'];
                    }
                }
            }

            // Yesterday comparison
            $yesterdayCalls = (clone $query)->whereDate('created_at', today()->subDay())->get();
            $yesterdayProfit = 0;

            foreach ($yesterdayCalls as $call) {
                $profitData = $calculator->getDisplayProfit($call, $user);
                if ($profitData['type'] !== 'none') {
                    $yesterdayProfit += $profitData['profit'];
                }
            }

            // Month stats
            $monthProfit = 0;
            $monthCalls = (clone $query)->whereMonth('created_at', now()->month)
                                       ->whereYear('created_at', now()->year)
                                       ->get();

            foreach ($monthCalls as $call) {
                $profitData = $calculator->getDisplayProfit($call, $user);
                if ($profitData['type'] !== 'none') {
                    $monthProfit += $profitData['profit'];
                }
            }

            // Calculate trends
            $profitChange = $yesterdayProfit > 0
                ? round((($todayProfit - $yesterdayProfit) / $yesterdayProfit) * 100, 1)
                : 0;

            $avgMargin = $todayCalls->count() > 0
                ? round($todayCalls->sum('profit_margin_total') / $todayCalls->count(), 1)
                : 0;

            // Build stats array
            $stats = [];

            // Today's Profit
            $stats[] = Stat::make('Profit Heute', number_format($todayProfit / 100, 2, ',', '.') . ' €')
                ->description($profitChange > 0
                    ? "↑ {$profitChange}% vs. Gestern"
                    : ($profitChange < 0 ? "↓ " . abs($profitChange) . "% vs. Gestern" : "→ Keine Änderung"))
                ->descriptionIcon($profitChange > 0 ? 'heroicon-m-arrow-trending-up' :
                    ($profitChange < 0 ? 'heroicon-m-arrow-trending-down' : 'heroicon-m-minus'))
                ->color($profitChange > 0 ? 'success' : ($profitChange < 0 ? 'danger' : 'gray'))
                ->chart($this->getLast7DaysChart($query, $calculator, $user));

            // Month's Profit
            $stats[] = Stat::make('Profit Monat', number_format($monthProfit / 100, 2, ',', '.') . ' €')
                ->description($monthCalls->count() . ' Anrufe')
                ->descriptionIcon('heroicon-m-calendar-days')
                ->color('primary');

            // Average Margin
            $stats[] = Stat::make('⌀ Marge Heute', $avgMargin . '%')
                ->description($todayCalls->count() . ' Anrufe heute')
                ->descriptionIcon('heroicon-m-chart-pie')
                ->color($avgMargin > 50 ? 'success' : ($avgMargin > 20 ? 'warning' : 'danger'));

            // Platform/Reseller split (Super Admin only)
            if ($isSuperAdmin) {
                $stats[] = Stat::make('Platform vs. Mandant',
                    number_format($todayPlatformProfit / 100, 2, ',', '.') . ' € / ' .
                    number_format($todayResellerProfit / 100, 2, ',', '.') . ' €')
                    ->description('Platform / Mandanten Split')
                    ->descriptionIcon('heroicon-m-arrows-right-left')
                    ->color('info');
            }

            return $stats;
        });
    }

    private function getLast7DaysChart($baseQuery, $calculator, $user): array
    {
        $chart = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $calls = (clone $baseQuery)->whereDate('created_at', $date)->get();

            $dayProfit = 0;
            foreach ($calls as $call) {
                $profitData = $calculator->getDisplayProfit($call, $user);
                if ($profitData['type'] !== 'none') {
                    $dayProfit += $profitData['profit'];
                }
            }

            $chart[] = round($dayProfit / 100, 2);
        }

        return $chart;
    }
}