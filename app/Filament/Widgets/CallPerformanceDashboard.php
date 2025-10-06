<?php

namespace App\Filament\Widgets;

use App\Services\ConversionTracker;
use App\Services\DeterministicCustomerMatcher;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class CallPerformanceDashboard extends BaseWidget
{
    protected static ?int $sort = 1;

    protected int | string | array $columnSpan = 'full';

    protected static ?string $pollingInterval = '300s';

    protected function getStats(): array
    {
        // Cache for 5 minutes with 5-minute key granularity
        $cacheMinute = floor(now()->minute / 5) * 5;
        $cacheKey = 'call-performance-' . now()->format('Y-m-d-H') . '-' . str_pad($cacheMinute, 2, '0', STR_PAD_LEFT);

        return Cache::remember($cacheKey, 300, function () {
            return $this->calculateStats();
        });
    }

    private function calculateStats(): array
    {
        try {
            // Get metrics for today
            $todayMetrics = ConversionTracker::getConversionMetrics(
                Carbon::today(),
                Carbon::now()
            );

            // Get metrics for this week
            $weekMetrics = ConversionTracker::getConversionMetrics(
                Carbon::now()->startOfWeek(),
                Carbon::now()
            );

            // Get metrics for this month
            $monthMetrics = ConversionTracker::getConversionMetrics(
                Carbon::now()->startOfMonth(),
                Carbon::now()
            );

            // Get unknown customer stats
            $unknownStats = DeterministicCustomerMatcher::getUnknownCustomerStats();

            // Calculate trends
            $yesterdayMetrics = ConversionTracker::getConversionMetrics(
                Carbon::yesterday(),
                Carbon::yesterday()->endOfDay()
            );

            $conversionTrend = $yesterdayMetrics['overview']['conversion_rate'] > 0
                ? round((($todayMetrics['overview']['conversion_rate'] - $yesterdayMetrics['overview']['conversion_rate'])
                    / $yesterdayMetrics['overview']['conversion_rate']) * 100, 1)
                : 0;

            // Cache agent performance to avoid duplicate calls
            $agentPerformance = ConversionTracker::getAgentPerformance(
                Carbon::now()->startOfWeek(),
                Carbon::now()
            );

            return [
                Stat::make('📞 Anrufe heute', $todayMetrics['overview']['total_calls'])
                    ->description($this->getCallDescription($todayMetrics))
                    ->descriptionIcon('heroicon-o-phone')
                    ->color('primary')
                    ->chart($this->getHourlyCallChart($todayMetrics)),

                Stat::make('🎯 Conversion Rate', $todayMetrics['overview']['conversion_rate'] . '%')
                    ->description($this->getConversionDescription($todayMetrics))
                    ->descriptionIcon($conversionTrend > 0 ? 'heroicon-o-arrow-trending-up' : 'heroicon-o-arrow-trending-down')
                    ->color($todayMetrics['overview']['conversion_rate'] > 30 ? 'success' : 'warning')
                    ->chart($this->getConversionTrendChart()),

                Stat::make('⏱️ Ø Conversion Zeit', $this->formatMinutes($todayMetrics['overview']['avg_conversion_time_minutes']))
                    ->description('Von Anruf zu Termin')
                    ->descriptionIcon('heroicon-o-clock')
                    ->color('info'),

                Stat::make('❓ Unbekannte Kunden', $unknownStats['total_unknown'])
                    ->description($this->getUnknownDescription($unknownStats))
                    ->descriptionIcon('heroicon-o-user-plus')
                    ->color($unknownStats['total_unknown'] > 10 ? 'danger' : 'gray')
                    ->url(route('filament.admin.resources.customers.index', [
                        'tableFilters[customer_type][value]' => 'unknown'
                    ])),

                Stat::make('📊 Woche Conversion', $weekMetrics['overview']['conversion_rate'] . '%')
                    ->description("{$weekMetrics['overview']['converted_calls']} von {$weekMetrics['overview']['total_calls']} Anrufen")
                    ->descriptionIcon('heroicon-o-calendar')
                    ->color('success')
                    ->chart($this->getWeeklyChart($weekMetrics)),

                Stat::make('🏆 Top Agent', $this->getTopAgent($agentPerformance))
                    ->description($this->getTopAgentDescription($agentPerformance))
                    ->descriptionIcon('heroicon-o-trophy')
                    ->color('warning'),
            ];
        } catch (\Exception $e) {
            \Log::error('CallPerformanceDashboard error: ' . $e->getMessage());
            return [
                Stat::make('Fehler', 'Dashboard konnte nicht geladen werden')
                    ->description($e->getMessage())
                    ->color('danger'),
            ];
        }
    }

    private function getCallDescription(array $metrics): string
    {
        $converted = $metrics['overview']['converted_calls'];
        $total = $metrics['overview']['total_calls'];

        if ($converted > 0) {
            return "{$converted} Termine vereinbart";
        }

        return "Noch keine Conversions heute";
    }

    private function getConversionDescription(array $metrics): string
    {
        $rate = $metrics['overview']['conversion_rate'];

        if ($rate > 50) {
            return "Exzellente Performance! 🎉";
        } elseif ($rate > 30) {
            return "Gute Conversion-Rate 👍";
        } elseif ($rate > 15) {
            return "Durchschnittliche Performance";
        } else {
            return "Verbesserung möglich";
        }
    }

    private function getUnknownDescription(array $stats): string
    {
        $multiCall = $stats['unknown_with_multiple_calls'];

        if ($multiCall > 0) {
            return "{$multiCall} mit mehreren Anrufen - Verifizierung empfohlen";
        }

        return "{$stats['unknown_last_24h']} in den letzten 24 Std.";
    }

    private function formatMinutes(float $minutes): string
    {
        if ($minutes < 60) {
            return round($minutes) . ' Min';
        }

        $hours = floor($minutes / 60);
        $mins = round($minutes % 60);

        return "{$hours}h {$mins}m";
    }

    private function getHourlyCallChart(array $metrics): array
    {
        $chart = [];
        for ($i = 0; $i < 24; $i++) {
            $chart[] = $metrics['by_hour'][$i] ?? 0;
        }
        return $chart;
    }

    private function getConversionTrendChart(): array
    {
        // Get last 7 days conversion rates
        $chart = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            $dayMetrics = ConversionTracker::getConversionMetrics(
                $date->startOfDay(),
                $date->endOfDay()
            );
            $chart[] = $dayMetrics['overview']['conversion_rate'];
        }
        return $chart;
    }

    private function getWeeklyChart(array $metrics): array
    {
        $chart = [];
        // Sunday = 1, Saturday = 7
        for ($i = 1; $i <= 7; $i++) {
            $chart[] = $metrics['by_day_of_week'][$i] ?? 0;
        }
        return $chart;
    }

    private function getTopAgent(array $performance): string
    {
        if ($performance['best_performer']) {
            return $performance['best_performer']->name ?? 'Unbekannt';
        }

        return 'Keine Daten';
    }

    private function getTopAgentDescription(array $performance): string
    {
        if ($performance['best_performer']) {
            $agent = $performance['best_performer'];
            return "{$agent->conversion_rate}% Conversion ({$agent->converted_calls}/{$agent->total_calls})";
        }

        return 'Keine Agenten-Daten verfügbar';
    }
}