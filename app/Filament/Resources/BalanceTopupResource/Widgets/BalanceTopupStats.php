<?php

namespace App\Filament\Resources\BalanceTopupResource\Widgets;

use App\Models\BalanceTopup;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Cache;

class BalanceTopupStats extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        // Cache for 5 minutes with 5-minute key granularity
        $cacheMinute = floor(now()->minute / 5) * 5;
        return Cache::remember('balance-topup-stats-' . now()->format('Y-m-d-H') . '-' . str_pad($cacheMinute, 2, '0', STR_PAD_LEFT), 300, function () {
            return $this->calculateStats();
        });
    }

    private function calculateStats(): array
    {
        // Optimized single query for all stats
        $stats = BalanceTopup::selectRaw("
            COUNT(*) as total_topups,
            COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_topups,
            COUNT(CASE WHEN status = 'succeeded' THEN 1 END) as completed_topups,
            COUNT(CASE WHEN status = 'failed' THEN 1 END) as failed_topups,
            COUNT(CASE WHEN status = 'cancelled' THEN 1 END) as cancelled_topups,
            SUM(CASE WHEN status = 'succeeded' THEN amount ELSE 0 END) as total_completed_amount,
            SUM(CASE WHEN status = 'pending' THEN amount ELSE 0 END) as total_pending_amount,
            COUNT(CASE WHEN created_at >= ? THEN 1 END) as today_topups,
            COUNT(CASE WHEN created_at >= ? THEN 1 END) as week_topups
        ", [today(), now()->subDays(7)])->first();

        // Get trend data for chart
        $trendData = $this->getWeeklyTrend();

        return [
            Stat::make('Aufladungen Gesamt', number_format($stats->total_topups ?? 0))
                ->description(($stats->completed_topups ?? 0) . ' abgeschlossen | ' . ($stats->pending_topups ?? 0) . ' ausstehend')
                ->descriptionIcon('heroicon-m-banknotes')
                ->chart($trendData)
                ->color('primary'),

            Stat::make('Ausstehend', $stats->pending_topups ?? 0)
                ->description('Benötigen Bearbeitung')
                ->descriptionIcon('heroicon-m-clock')
                ->color($stats->pending_topups > 5 ? 'warning' : 'gray'),

            Stat::make('Abgeschlossen', '€ ' . number_format($stats->total_completed_amount ?? 0, 2, ',', '.'))
                ->description(($stats->completed_topups ?? 0) . ' Transaktionen')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),

            Stat::make('Ausstehender Betrag', '€ ' . number_format($stats->total_pending_amount ?? 0, 2, ',', '.'))
                ->description(($stats->pending_topups ?? 0) . ' Aufladungen')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('warning'),

            Stat::make('Fehlgeschlagen', $stats->failed_topups ?? 0)
                ->description(($stats->cancelled_topups ?? 0) . ' storniert')
                ->descriptionIcon('heroicon-m-x-circle')
                ->color(($stats->failed_topups ?? 0) > 0 ? 'danger' : 'gray'),
        ];
    }

    private function getWeeklyTrend(): array
    {
        // Single optimized query for 7-day trend (succeeded topups only)
        $rawData = BalanceTopup::whereBetween('created_at', [
                today()->subDays(6)->startOfDay(),
                today()->endOfDay()
            ])
            ->where('status', 'succeeded')
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
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
}
