<?php

namespace App\Filament\Resources\AppointmentResource\Widgets;

use App\Models\Appointment;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class AppointmentStats extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        // Cache for 5 minutes with 5-minute key granularity (aligned with CallStats)
        $cacheMinute = floor(now()->minute / 5) * 5;
        return Cache::remember('appointment-stats-' . now()->format('Y-m-d-H') . '-' . str_pad($cacheMinute, 2, '0', STR_PAD_LEFT), 300, function () {
            return $this->calculateStats();
        });
    }

    private function calculateStats(): array
    {
        $today = today();
        $tomorrow = $today->copy()->addDay();
        $thisWeek = [now()->startOfWeek(), now()->endOfWeek()];
        $thisMonth = [now()->startOfMonth(), now()->endOfMonth()];

        // Optimized single query for all stats
        $stats = Appointment::selectRaw("
            COUNT(CASE WHEN DATE(starts_at) = ? THEN 1 END) as today_count,
            COUNT(CASE WHEN DATE(starts_at) = ? THEN 1 END) as tomorrow_count,
            COUNT(CASE WHEN starts_at BETWEEN ? AND ? THEN 1 END) as week_count,
            COUNT(CASE WHEN starts_at BETWEEN ? AND ? THEN 1 END) as month_count,
            COUNT(CASE WHEN status IN ('confirmed', 'accepted') AND DATE(starts_at) = ? THEN 1 END) as confirmed_today,
            COUNT(CASE WHEN status = 'cancelled' AND DATE(created_at) >= ? THEN 1 END) as cancelled_week,
            COUNT(CASE WHEN status = 'completed' AND starts_at BETWEEN ? AND ? THEN 1 END) as completed_month,
            COUNT(CASE WHEN status = 'no_show' AND starts_at BETWEEN ? AND ? THEN 1 END) as no_show_month,
            SUM(CASE WHEN status = 'completed' AND starts_at BETWEEN ? AND ? THEN COALESCE(price, 0) ELSE 0 END) as total_revenue_month,
            AVG(CASE WHEN status = 'completed' THEN price END) as avg_revenue
        ", [
            $today, $tomorrow,
            $thisWeek[0], $thisWeek[1],
            $thisMonth[0], $thisMonth[1],
            $today,
            now()->subWeek(),
            $thisMonth[0], $thisMonth[1],
            $thisMonth[0], $thisMonth[1],
            $thisMonth[0], $thisMonth[1]
        ])->first();

        // Calculate business metrics
        $monthCount = $stats->month_count ?? 0;
        $completedMonth = $stats->completed_month ?? 0;
        $noShowMonth = $stats->no_show_month ?? 0;
        $totalRevenueMonth = $stats->total_revenue_month ?? 0;

        $completionRate = $monthCount > 0 ? ($completedMonth / $monthCount) * 100 : 0;
        $noShowRate = $monthCount > 0 ? ($noShowMonth / $monthCount) * 100 : 0;

        // Calculate trend data for charts
        $weeklyTrend = $this->getWeeklyTrend();
        $revenueTrend = $this->getRevenueTrend();

        return [
            Stat::make('Heute', $stats->today_count ?? 0)
                ->description(($stats->confirmed_today ?? 0) . ' bestätigt')
                ->descriptionIcon('heroicon-m-check-circle')
                ->chart($weeklyTrend)
                ->color(($stats->today_count ?? 0) > 10 ? 'success' : 'warning'),

            Stat::make('Diese Woche', $stats->week_count ?? 0)
                ->description('Morgen: ' . ($stats->tomorrow_count ?? 0))
                ->descriptionIcon('heroicon-m-calendar-days')
                ->color('primary'),

            Stat::make('Monat Umsatz', '€' . number_format($totalRevenueMonth, 2))
                ->description($completedMonth . ' abgeschlossen | Ø €' . number_format($stats->avg_revenue ?? 0, 2))
                ->descriptionIcon('heroicon-m-currency-euro')
                ->chart($revenueTrend)
                ->color($totalRevenueMonth > 1000 ? 'success' : 'warning'),

            Stat::make('Stornierungen', $stats->cancelled_week ?? 0)
                ->description('Letzte 7 Tage')
                ->descriptionIcon('heroicon-m-x-circle')
                ->color(($stats->cancelled_week ?? 0) > 5 ? 'danger' : 'gray'),

            Stat::make('Abschlussrate', round($completionRate, 1) . '%')
                ->description($completedMonth . ' von ' . $monthCount . ' Terminen')
                ->descriptionIcon('heroicon-m-check-badge')
                ->color($completionRate > 80 ? 'success' : ($completionRate > 60 ? 'warning' : 'danger')),

            Stat::make('No-Show Rate', round($noShowRate, 1) . '%')
                ->description($noShowMonth . ' nicht erschienen')
                ->descriptionIcon('heroicon-m-user-minus')
                ->color($noShowRate > 10 ? 'danger' : ($noShowRate > 5 ? 'warning' : 'success')),
        ];
    }

    private function getWeeklyTrend(): array
    {
        // Single optimized query instead of 7 individual queries
        $rawData = Appointment::whereBetween('starts_at', [
                today()->subDays(6)->startOfDay(),
                today()->endOfDay()
            ])
            ->selectRaw('DATE(starts_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('count', 'date')
            ->toArray();

        $data = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = today()->subDays($i)->format('Y-m-d');
            $data[] = $rawData[$date] ?? 0;
        }
        return $data;
    }

    private function getRevenueTrend(): array
    {
        // Single optimized query instead of 7 individual queries
        $rawData = Appointment::whereBetween('starts_at', [
                today()->subDays(6)->startOfDay(),
                today()->endOfDay()
            ])
            ->where('status', 'completed')
            ->selectRaw('DATE(starts_at) as date, SUM(COALESCE(price, 0)) as revenue')
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('revenue', 'date')
            ->toArray();

        $data = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = today()->subDays($i)->format('Y-m-d');
            $data[] = $rawData[$date] ?? 0;
        }
        return $data;
    }
}