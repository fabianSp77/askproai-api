<?php

namespace App\Filament\Resources\PlatformCostResource\Widgets;

use App\Models\PlatformCost;
use App\Models\MonthlyCostReport;
use App\Models\Call;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Carbon\Carbon;

class PlatformCostOverview extends BaseWidget
{
    protected static ?string $pollingInterval = '60s';

    /**
     * Widget disabled - platform_costs table doesn't exist in Sept 21 database backup
     * TODO: Re-enable when database is fully restored
     */
    public static function canView(): bool
    {
        return false;
    }

    protected function getStats(): array
    {
        $startOfMonth = Carbon::now()->startOfMonth();
        $endOfMonth = Carbon::now()->endOfMonth();

        // Get monthly costs by platform
        $retellCosts = PlatformCost::where('platform', 'retell')
            ->whereBetween('period_start', [$startOfMonth, $endOfMonth])
            ->sum('amount_cents');

        $twilioCosts = PlatformCost::where('platform', 'twilio')
            ->whereBetween('period_start', [$startOfMonth, $endOfMonth])
            ->sum('amount_cents');

        $calcomCosts = PlatformCost::where('platform', 'calcom')
            ->whereBetween('period_start', [$startOfMonth, $endOfMonth])
            ->sum('amount_cents');

        $totalExternalCosts = $retellCosts + $twilioCosts + $calcomCosts;

        // Get revenue
        $revenue = Call::whereBetween('created_at', [$startOfMonth, $endOfMonth])
            ->sum('customer_cost');

        // Calculate profit
        $profit = $revenue - $totalExternalCosts;
        $profitMargin = $revenue > 0 ? ($profit / $revenue) * 100 : 0;

        // Get today's costs
        $todayCosts = PlatformCost::whereDate('period_start', Carbon::today())
            ->sum('amount_cents');

        // Get yesterday's costs for comparison
        $yesterdayCosts = PlatformCost::whereDate('period_start', Carbon::yesterday())
            ->sum('amount_cents');

        $costChange = $yesterdayCosts > 0
            ? (($todayCosts - $yesterdayCosts) / $yesterdayCosts) * 100
            : 0;

        return [
            Stat::make('Retell.ai Kosten', number_format($retellCosts / 100, 2, ',', '.') . ' €')
                ->description('Monat: ' . Carbon::now()->format('F Y'))
                ->color('info')
                ->icon('heroicon-o-phone')
                ->chart($this->getWeeklyChart('retell')),

            Stat::make('Twilio Kosten', number_format($twilioCosts / 100, 2, ',', '.') . ' €')
                ->description('Monat: ' . Carbon::now()->format('F Y'))
                ->color('warning')
                ->icon('heroicon-o-signal')
                ->chart($this->getWeeklyChart('twilio')),

            Stat::make('Cal.com Kosten', number_format($calcomCosts / 100, 2, ',', '.') . ' €')
                ->description('Monat: ' . Carbon::now()->format('F Y'))
                ->color('success')
                ->icon('heroicon-o-calendar')
                ->chart($this->getWeeklyChart('calcom')),

            Stat::make('Gesamte externe Kosten', number_format($totalExternalCosts / 100, 2, ',', '.') . ' €')
                ->description('Monat: ' . Carbon::now()->format('F Y'))
                ->color('danger')
                ->icon('heroicon-o-currency-euro')
                ->chart($this->getWeeklyTotalChart()),

            Stat::make('Monats-Umsatz', number_format($revenue / 100, 2, ',', '.') . ' €')
                ->description('Bruttoeinnahmen')
                ->color('success')
                ->icon('heroicon-o-arrow-trending-up'),

            Stat::make('Bruttogewinn', number_format($profit / 100, 2, ',', '.') . ' €')
                ->description('Marge: ' . number_format($profitMargin, 1, ',', '.') . '%')
                ->color($profit > 0 ? 'success' : 'danger')
                ->icon($profit > 0 ? 'heroicon-o-check-circle' : 'heroicon-o-x-circle'),

            Stat::make('Heutige Kosten', number_format($todayCosts / 100, 2, ',', '.') . ' €')
                ->description($costChange >= 0
                    ? '+' . number_format($costChange, 1, ',', '.') . '% vs. gestern'
                    : number_format($costChange, 1, ',', '.') . '% vs. gestern'
                )
                ->descriptionIcon($costChange >= 0
                    ? 'heroicon-m-arrow-trending-up'
                    : 'heroicon-m-arrow-trending-down'
                )
                ->color($costChange < 0 ? 'success' : 'warning')
                ->icon('heroicon-o-calendar-days'),

            Stat::make('Aktive Dienste', $this->getActiveServicesCount())
                ->description('Plattformen mit Kosten heute')
                ->color('primary')
                ->icon('heroicon-o-server-stack'),
        ];
    }

    private function getWeeklyChart(string $platform): array
    {
        $data = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            $costs = PlatformCost::where('platform', $platform)
                ->whereDate('period_start', $date)
                ->sum('amount_cents');
            $data[] = $costs / 100; // Convert to euros
        }
        return $data;
    }

    private function getWeeklyTotalChart(): array
    {
        $data = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            $costs = PlatformCost::whereDate('period_start', $date)
                ->sum('amount_cents');
            $data[] = $costs / 100; // Convert to euros
        }
        return $data;
    }

    private function getActiveServicesCount(): int
    {
        return PlatformCost::whereDate('period_start', Carbon::today())
            ->distinct('platform')
            ->count('platform');
    }
}