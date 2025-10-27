<?php

namespace App\Filament\Resources\CustomerNoteResource\Widgets;

use App\Models\CustomerNote;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Cache;

class CustomerNoteStats extends BaseWidget
{
    protected static ?int $sort = 1;

    /**
     * Widget disabled - customer_notes table doesn't exist in Sept 21 database backup
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
        return Cache::remember('customer-note-stats-' . now()->format('Y-m-d-H') . '-' . str_pad($cacheMinute, 2, '0', STR_PAD_LEFT), 300, function () {
            return $this->calculateStats();
        });
    }

    private function calculateStats(): array
    {
        // Optimized single query for all stats
        $stats = CustomerNote::selectRaw("
            COUNT(*) as total_notes,
            COUNT(CASE WHEN is_important = 1 THEN 1 END) as important_notes,
            COUNT(CASE WHEN is_pinned = 1 THEN 1 END) as pinned_notes,
            COUNT(CASE WHEN created_at >= ? THEN 1 END) as today_notes,
            COUNT(CASE WHEN created_at >= ? THEN 1 END) as week_notes,
            COUNT(CASE WHEN type = 'complaint' THEN 1 END) as complaint_notes,
            COUNT(CASE WHEN type = 'feedback' THEN 1 END) as feedback_notes,
            COUNT(DISTINCT customer_id) as customers_with_notes
        ", [today(), now()->subDays(7)])->first();

        // Get trend data for chart
        $trendData = $this->getWeeklyTrend();

        // Calculate note distribution by type
        $typeDistribution = CustomerNote::selectRaw('type, COUNT(*) as count')
            ->groupBy('type')
            ->pluck('count', 'type')
            ->toArray();

        return [
            Stat::make('Notizen Gesamt', number_format($stats->total_notes ?? 0))
                ->description(($stats->important_notes ?? 0) . ' wichtig | ' . ($stats->pinned_notes ?? 0) . ' angepinnt')
                ->descriptionIcon('heroicon-m-document-text')
                ->chart($trendData)
                ->color('primary'),

            Stat::make('Heute', $stats->today_notes ?? 0)
                ->description('Diese Woche: ' . ($stats->week_notes ?? 0))
                ->descriptionIcon('heroicon-m-calendar-days')
                ->color('success'),

            Stat::make('Kunden mit Notizen', number_format($stats->customers_with_notes ?? 0))
                ->description('Durchschnitt: ' . ($stats->total_notes > 0 ? round($stats->total_notes / max($stats->customers_with_notes, 1), 1) : 0) . ' Notizen/Kunde')
                ->descriptionIcon('heroicon-m-user-group')
                ->color('info'),

            Stat::make('Beschwerden', $stats->complaint_notes ?? 0)
                ->description(($stats->feedback_notes ?? 0) . ' Feedback-Notizen')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color(($stats->complaint_notes ?? 0) > 5 ? 'danger' : 'warning'),

            Stat::make('Wichtige Notizen', $stats->important_notes ?? 0)
                ->description('Erfordern Aufmerksamkeit')
                ->descriptionIcon('heroicon-m-exclamation-circle')
                ->color(($stats->important_notes ?? 0) > 10 ? 'danger' : 'warning'),
        ];
    }

    private function getWeeklyTrend(): array
    {
        // Single optimized query for 7-day trend
        $rawData = CustomerNote::whereBetween('created_at', [
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
