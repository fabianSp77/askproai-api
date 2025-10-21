<?php

namespace App\Filament\Resources\TransactionResource\Widgets;

use App\Models\Transaction;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Cache;

class TransactionStats extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        // Cache for 5 minutes with 5-minute key granularity
        $cacheMinute = floor(now()->minute / 5) * 5;
        return Cache::remember('transaction-stats-' . now()->format('Y-m-d-H') . '-' . str_pad($cacheMinute, 2, '0', STR_PAD_LEFT), 300, function () {
            return $this->calculateStats();
        });
    }

    private function calculateStats(): array
    {
        // Optimized single query for all stats
        $stats = Transaction::selectRaw("
            COUNT(*) as total_transactions,
            COUNT(CASE WHEN type = 'credit' THEN 1 END) as credit_transactions,
            COUNT(CASE WHEN type = 'debit' THEN 1 END) as debit_transactions,
            SUM(CASE WHEN type = 'credit' THEN amount_cents ELSE 0 END) as total_credits,
            SUM(CASE WHEN type = 'debit' THEN amount_cents ELSE 0 END) as total_debits,
            COUNT(CASE WHEN created_at >= ? THEN 1 END) as today_transactions,
            COUNT(CASE WHEN created_at >= ? THEN 1 END) as week_transactions,
            COUNT(DISTINCT tenant_id) as active_tenants
        ", [today(), now()->subDays(7)])->first();

        // Get trend data for chart
        $trendData = $this->getWeeklyTrend();

        // Calculate net balance change
        $netBalance = ($stats->total_credits ?? 0) - ($stats->total_debits ?? 0);

        return [
            Stat::make('Transaktionen Gesamt', number_format($stats->total_transactions ?? 0))
                ->description(($stats->credit_transactions ?? 0) . ' Gutschriften | ' . ($stats->debit_transactions ?? 0) . ' Belastungen')
                ->descriptionIcon('heroicon-m-arrows-right-left')
                ->chart($trendData)
                ->color('primary'),

            Stat::make('Gutschriften', '€ ' . number_format(($stats->total_credits ?? 0) / 100, 2, ',', '.'))
                ->description(($stats->credit_transactions ?? 0) . ' Transaktionen')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success'),

            Stat::make('Belastungen', '€ ' . number_format(($stats->total_debits ?? 0) / 100, 2, ',', '.'))
                ->description(($stats->debit_transactions ?? 0) . ' Transaktionen')
                ->descriptionIcon('heroicon-m-arrow-trending-down')
                ->color('danger'),

            Stat::make('Netto-Saldo', '€ ' . number_format($netBalance / 100, 2, ',', '.'))
                ->description('Gutschriften - Belastungen')
                ->descriptionIcon($netBalance >= 0 ? 'heroicon-m-arrow-up' : 'heroicon-m-arrow-down')
                ->color($netBalance >= 0 ? 'success' : 'danger'),

            Stat::make('Heute', $stats->today_transactions ?? 0)
                ->description('Diese Woche: ' . ($stats->week_transactions ?? 0))
                ->descriptionIcon('heroicon-m-calendar-days')
                ->color('info'),
        ];
    }

    private function getWeeklyTrend(): array
    {
        // Single optimized query for 7-day trend
        $rawData = Transaction::whereBetween('created_at', [
                today()->subDays(6)->startOfDay(),
                today()->endOfDay()
            ])
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
