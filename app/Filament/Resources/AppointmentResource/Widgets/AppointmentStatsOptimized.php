<?php

namespace App\Filament\Resources\AppointmentResource\Widgets;

use App\Models\Appointment;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AppointmentStatsOptimized extends BaseWidget
{
    protected static ?int $sort = 1;

    /**
     * Optimized stats calculation with aggressive caching
     * and database-level aggregation
     */
    protected function getStats(): array
    {
        $companyId = auth()->user()->company_id ?? 1;

        // Cache key with company scope and 5-minute granularity
        $cacheKey = sprintf(
            'appt-stats:%d:%s',
            $companyId,
            now()->format('Y-m-d-H') . '-' . (floor(now()->minute / 5) * 5)
        );

        return Cache::tags(['appointments', "company-{$companyId}"])
            ->remember($cacheKey, 300, fn() => $this->calculateOptimizedStats($companyId));
    }

    /**
     * Calculate stats using optimized database queries
     */
    private function calculateOptimizedStats(int $companyId): array
    {
        $today = today();
        $tomorrow = $today->copy()->addDay();
        $weekStart = now()->startOfWeek();
        $weekEnd = now()->endOfWeek();
        $monthStart = now()->startOfMonth();
        $monthEnd = now()->endOfMonth();

        // Single optimized query for all stats
        $stats = DB::table('appointments')
            ->where('company_id', $companyId)
            ->whereNull('deleted_at')
            ->selectRaw("
                -- Today's appointments
                COUNT(CASE WHEN DATE(starts_at) = ? THEN 1 END) as today_count,
                COUNT(CASE WHEN DATE(starts_at) = ? AND status IN ('confirmed', 'accepted') THEN 1 END) as today_confirmed,

                -- Tomorrow's appointments
                COUNT(CASE WHEN DATE(starts_at) = ? THEN 1 END) as tomorrow_count,

                -- Week stats
                COUNT(CASE WHEN starts_at BETWEEN ? AND ? THEN 1 END) as week_count,
                COUNT(CASE WHEN status = 'cancelled' AND created_at >= ? THEN 1 END) as cancelled_week,

                -- Month stats
                COUNT(CASE WHEN starts_at BETWEEN ? AND ? THEN 1 END) as month_count,
                COUNT(CASE WHEN status = 'completed' AND starts_at BETWEEN ? AND ? THEN 1 END) as completed_month,
                COUNT(CASE WHEN status = 'no_show' AND starts_at BETWEEN ? AND ? THEN 1 END) as no_show_month,

                -- Revenue (if price column exists)
                COALESCE(SUM(CASE WHEN status = 'completed' AND starts_at BETWEEN ? AND ? THEN price END), 0) as revenue_month,
                COALESCE(AVG(CASE WHEN status = 'completed' AND starts_at BETWEEN ? AND ? THEN price END), 0) as avg_revenue
            ", [
                $today, $today,  // Today stats
                $tomorrow,       // Tomorrow
                $weekStart, $weekEnd,  // Week range
                now()->subWeek(),      // Cancelled last week
                $monthStart, $monthEnd, // Month range
                $monthStart, $monthEnd, // Completed month
                $monthStart, $monthEnd, // No-show month
                $monthStart, $monthEnd, // Revenue month
                $monthStart, $monthEnd  // Avg revenue
            ])
            ->first();

        // Calculate rates
        $completionRate = $stats->month_count > 0
            ? ($stats->completed_month / $stats->month_count) * 100
            : 0;

        $noShowRate = $stats->month_count > 0
            ? ($stats->no_show_month / $stats->month_count) * 100
            : 0;

        // Get cached trend data
        $weeklyTrend = $this->getCachedWeeklyTrend($companyId);
        $revenueTrend = $this->getCachedRevenueTrend($companyId);

        return [
            Stat::make('Heute', $stats->today_count ?? 0)
                ->description(($stats->today_confirmed ?? 0) . ' bestätigt')
                ->descriptionIcon('heroicon-m-check-circle')
                ->chart($weeklyTrend)
                ->color($stats->today_count > 10 ? 'success' : 'warning'),

            Stat::make('Diese Woche', $stats->week_count ?? 0)
                ->description('Morgen: ' . ($stats->tomorrow_count ?? 0))
                ->descriptionIcon('heroicon-m-calendar-days')
                ->color('primary'),

            Stat::make('Monat Umsatz', '€' . number_format($stats->revenue_month ?? 0, 2))
                ->description($stats->completed_month . ' abgeschlossen | Ø €' . number_format($stats->avg_revenue ?? 0, 2))
                ->descriptionIcon('heroicon-m-currency-euro')
                ->chart($revenueTrend)
                ->color($stats->revenue_month > 1000 ? 'success' : 'warning'),

            Stat::make('Stornierungen', $stats->cancelled_week ?? 0)
                ->description('Letzte 7 Tage')
                ->descriptionIcon('heroicon-m-x-circle')
                ->color($stats->cancelled_week > 5 ? 'danger' : 'gray'),

            Stat::make('Abschlussrate', round($completionRate, 1) . '%')
                ->description($stats->completed_month . ' von ' . $stats->month_count . ' Terminen')
                ->descriptionIcon('heroicon-m-check-badge')
                ->color($completionRate > 80 ? 'success' : ($completionRate > 60 ? 'warning' : 'danger')),

            Stat::make('No-Show Rate', round($noShowRate, 1) . '%')
                ->description($stats->no_show_month . ' nicht erschienen')
                ->descriptionIcon('heroicon-m-user-minus')
                ->color($noShowRate > 10 ? 'danger' : ($noShowRate > 5 ? 'warning' : 'success')),
        ];
    }

    /**
     * Get weekly trend with caching
     */
    private function getCachedWeeklyTrend(int $companyId): array
    {
        $cacheKey = "appt-trend:week:{$companyId}:" . today()->format('Y-m-d');

        return Cache::tags(['appointments', "company-{$companyId}"])
            ->remember($cacheKey, 3600, function() use ($companyId) {
                // Use database aggregation for better performance
                $rawData = DB::table('appointments')
                    ->where('company_id', $companyId)
                    ->whereNull('deleted_at')
                    ->whereBetween('starts_at', [
                        today()->subDays(6)->startOfDay(),
                        today()->endOfDay()
                    ])
                    ->selectRaw('DATE(starts_at) as date, COUNT(*) as count')
                    ->groupBy('date')
                    ->pluck('count', 'date')
                    ->toArray();

                // Fill missing dates with zeros
                $trend = [];
                for ($i = 6; $i >= 0; $i--) {
                    $date = today()->subDays($i)->format('Y-m-d');
                    $trend[] = $rawData[$date] ?? 0;
                }

                return $trend;
            });
    }

    /**
     * Get revenue trend with caching
     */
    private function getCachedRevenueTrend(int $companyId): array
    {
        // Check if price column exists
        $hasPrice = Schema::hasColumn('appointments', 'price');

        if (!$hasPrice) {
            return array_fill(0, 7, 0);
        }

        $cacheKey = "appt-trend:revenue:{$companyId}:" . today()->format('Y-m-d');

        return Cache::tags(['appointments', "company-{$companyId}"])
            ->remember($cacheKey, 3600, function() use ($companyId) {
                $rawData = DB::table('appointments')
                    ->where('company_id', $companyId)
                    ->where('status', 'completed')
                    ->whereNull('deleted_at')
                    ->whereBetween('starts_at', [
                        today()->subDays(6)->startOfDay(),
                        today()->endOfDay()
                    ])
                    ->selectRaw('DATE(starts_at) as date, COALESCE(SUM(price), 0) as revenue')
                    ->groupBy('date')
                    ->pluck('revenue', 'date')
                    ->toArray();

                $trend = [];
                for ($i = 6; $i >= 0; $i--) {
                    $date = today()->subDays($i)->format('Y-m-d');
                    $trend[] = $rawData[$date] ?? 0;
                }

                return $trend;
            });
    }

    /**
     * Clear cache when appointments are modified
     */
    public static function clearCache(int $companyId): void
    {
        Cache::tags(['appointments', "company-{$companyId}"])->flush();
    }

    /**
     * Refresh the widget (for Livewire polling)
     */
    public function refresh(): void
    {
        // Clear current cache to force refresh
        $companyId = auth()->user()->company_id ?? 1;
        Cache::tags(['appointments', "company-{$companyId}"])->forget(
            sprintf(
                'appt-stats:%d:%s',
                $companyId,
                now()->format('Y-m-d-H') . '-' . (floor(now()->minute / 5) * 5)
            )
        );

        $this->emit('$refresh');
    }
}