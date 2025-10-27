<?php

namespace App\Filament\Resources\InvoiceResource\Widgets;

use App\Models\Invoice;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Cache;

class InvoiceStats extends BaseWidget
{
    protected static ?int $sort = 1;

    /**
     * Widget disabled - invoices table doesn't exist in Sept 21 database backup
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
        return Cache::remember('invoice-stats-' . now()->format('Y-m-d-H') . '-' . str_pad($cacheMinute, 2, '0', STR_PAD_LEFT), 300, function () {
            return $this->calculateStats();
        });
    }

    private function calculateStats(): array
    {
        // Optimized single query for all stats
        $stats = Invoice::selectRaw("
            COUNT(*) as total_invoices,
            COUNT(CASE WHEN status = 'draft' THEN 1 END) as draft_invoices,
            COUNT(CASE WHEN status = 'sent' THEN 1 END) as sent_invoices,
            COUNT(CASE WHEN status = 'paid' THEN 1 END) as paid_invoices,
            COUNT(CASE WHEN status = 'overdue' THEN 1 END) as overdue_invoices,
            SUM(CASE WHEN status = 'paid' THEN total_amount ELSE 0 END) as paid_revenue,
            SUM(CASE WHEN status IN ('sent', 'partial') THEN balance_due ELSE 0 END) as outstanding_balance,
            COUNT(CASE WHEN created_at >= ? THEN 1 END) as today_invoices,
            COUNT(CASE WHEN created_at >= ? THEN 1 END) as week_invoices
        ", [today(), now()->subDays(7)])->first();

        // Get trend data for chart
        $trendData = $this->getWeeklyTrend();

        return [
            Stat::make('Rechnungen Gesamt', number_format($stats->total_invoices ?? 0))
                ->description(($stats->paid_invoices ?? 0) . ' bezahlt | ' . ($stats->overdue_invoices ?? 0) . ' überfällig')
                ->descriptionIcon('heroicon-m-document-text')
                ->chart($trendData)
                ->color('primary'),

            Stat::make('Entwürfe', $stats->draft_invoices ?? 0)
                ->description(($stats->sent_invoices ?? 0) . ' versendet')
                ->descriptionIcon('heroicon-m-paper-airplane')
                ->color($stats->draft_invoices > 10 ? 'warning' : 'gray'),

            Stat::make('Umsatz (bezahlt)', '€ ' . number_format($stats->paid_revenue ?? 0, 2, ',', '.'))
                ->description('Ausstehend: € ' . number_format($stats->outstanding_balance ?? 0, 2, ',', '.'))
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('success'),

            Stat::make('Heute', $stats->today_invoices ?? 0)
                ->description('Diese Woche: ' . ($stats->week_invoices ?? 0))
                ->descriptionIcon('heroicon-m-calendar-days')
                ->color('info'),

            Stat::make('Überfällig', $stats->overdue_invoices ?? 0)
                ->description('Erfordern Aufmerksamkeit')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color(($stats->overdue_invoices ?? 0) > 5 ? 'danger' : 'warning'),
        ];
    }

    private function getWeeklyTrend(): array
    {
        // Single optimized query for 7-day trend
        $rawData = Invoice::whereBetween('created_at', [
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
