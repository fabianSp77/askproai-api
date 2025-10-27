<?php

namespace App\Filament\Widgets;

use App\Models\CallbackRequest;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class CallbacksByBranchWidget extends BaseWidget
{
    protected static ?int $sort = 0;

    protected static ?string $pollingInterval = '60s';

    /**
     * Get the widget statistics
     */
    protected function getStats(): array
    {
        $stats = $this->getCachedStatistics();

        return [
            Stat::make('Ausstehende Rückrufe', $stats['pending_count'])
                ->description('Alle offenen Rückrufe in allen Filialen')
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning')
                ->chart($stats['pending_trend'])
                ->extraAttributes([
                    'class' => 'cursor-pointer',
                ])
                ->url(route('filament.admin.resources.callback-requests.index', [
                    'tableFilters' => [
                        'status' => [
                            'values' => [CallbackRequest::STATUS_PENDING],
                        ],
                    ],
                ])),

            Stat::make('Überfällige Rückrufe', $stats['overdue_count'])
                ->description($this->getOverdueDescription($stats['overdue_count']))
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color('danger')
                ->chart($stats['overdue_trend'])
                ->extraAttributes([
                    'class' => 'cursor-pointer',
                ])
                ->url(route('filament.admin.resources.callback-requests.index', [
                    'tableFilters' => [
                        'overdue' => true,
                    ],
                ])),

            Stat::make('Heute abgeschlossen', $stats['completed_today'])
                ->description('Erfolgreich bearbeitete Rückrufe')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success')
                ->chart($stats['completed_trend'])
                ->extraAttributes([
                    'class' => 'cursor-pointer',
                ])
                ->url(route('filament.admin.resources.callback-requests.index', [
                    'tableFilters' => [
                        'status' => [
                            'values' => [CallbackRequest::STATUS_COMPLETED],
                        ],
                        'completed_at' => [
                            'from' => now()->startOfDay(),
                            'until' => now()->endOfDay(),
                        ],
                    ],
                ])),

            Stat::make('Ø Reaktionszeit', $stats['avg_response_time'])
                ->description($this->getResponseTimeDescription($stats['avg_response_hours']))
                ->descriptionIcon('heroicon-m-clock')
                ->color($this->getResponseTimeColor($stats['avg_response_hours']))
                ->chart($stats['response_time_trend']),
        ];
    }

    /**
     * Get cached statistics with optimized single query
     */
    protected function getCachedStatistics(): array
    {
        return Cache::remember(
            'callback_stats_widget',
            now()->addMinutes(5),
            function () {
                $now = Carbon::now();
                $sevenDaysAgo = $now->copy()->subDays(7);

                // Single optimized query for all current stats
                // ⚠️ FIXED: assigned_at column doesn't exist in Sept 21 backup
                // Response time calculation disabled until database is restored
                $currentStats = CallbackRequest::query()
                    ->selectRaw('
                        COUNT(CASE WHEN status IN (?, ?) THEN 1 END) as pending_count,
                        COUNT(CASE WHEN expires_at < ? AND status NOT IN (?, ?, ?) THEN 1 END) as overdue_count,
                        COUNT(CASE WHEN status = ? AND DATE(completed_at) = ? THEN 1 END) as completed_today,
                        0 as avg_response_hours
                    ', [
                        CallbackRequest::STATUS_PENDING,
                        CallbackRequest::STATUS_ASSIGNED,
                        $now,
                        CallbackRequest::STATUS_COMPLETED,
                        CallbackRequest::STATUS_EXPIRED,
                        CallbackRequest::STATUS_CANCELLED,
                        CallbackRequest::STATUS_COMPLETED,
                        $now->toDateString(),
                    ])
                    ->first();

                // Get 7-day trends for charts
                $trends = $this->getSevenDayTrends($sevenDaysAgo, $now);

                // Format average response time
                $avgHours = $currentStats->avg_response_hours ?? 0;
                $formattedResponseTime = $this->formatResponseTime($avgHours);

                return [
                    'pending_count' => (int) $currentStats->pending_count,
                    'overdue_count' => (int) $currentStats->overdue_count,
                    'completed_today' => (int) $currentStats->completed_today,
                    'avg_response_hours' => round($avgHours, 1),
                    'avg_response_time' => $formattedResponseTime,
                    'pending_trend' => $trends['pending'],
                    'overdue_trend' => $trends['overdue'],
                    'completed_trend' => $trends['completed'],
                    'response_time_trend' => $trends['response_time'],
                ];
            }
        );
    }

    /**
     * Get 7-day trend data for charts
     */
    protected function getSevenDayTrends(Carbon $startDate, Carbon $endDate): array
    {
        // ⚠️ FIXED: assigned_at column doesn't exist - response time disabled
        $dailyStats = CallbackRequest::query()
            ->selectRaw('
                DATE(created_at) as date,
                COUNT(CASE WHEN status IN (?, ?) THEN 1 END) as pending,
                COUNT(CASE WHEN expires_at < NOW() AND status NOT IN (?, ?, ?) THEN 1 END) as overdue,
                COUNT(CASE WHEN status = ? THEN 1 END) as completed,
                0 as avg_hours
            ', [
                CallbackRequest::STATUS_PENDING,
                CallbackRequest::STATUS_ASSIGNED,
                CallbackRequest::STATUS_COMPLETED,
                CallbackRequest::STATUS_EXPIRED,
                CallbackRequest::STATUS_CANCELLED,
                CallbackRequest::STATUS_COMPLETED,
            ])
            ->whereBetween('created_at', [$startDate, $endDate])
            ->groupBy('date')
            ->orderBy('date', 'asc')
            ->get()
            ->keyBy('date');

        // Fill in missing dates with zeros
        $pendingTrend = [];
        $overdueTrend = [];
        $completedTrend = [];
        $responseTimeTrend = [];

        for ($date = $startDate->copy(); $date->lte($endDate); $date->addDay()) {
            $dateKey = $date->toDateString();
            $stats = $dailyStats->get($dateKey);

            $pendingTrend[] = $stats ? (int) $stats->pending : 0;
            $overdueTrend[] = $stats ? (int) $stats->overdue : 0;
            $completedTrend[] = $stats ? (int) $stats->completed : 0;
            $responseTimeTrend[] = $stats && $stats->avg_hours ? round($stats->avg_hours, 1) : 0;
        }

        return [
            'pending' => $pendingTrend,
            'overdue' => $overdueTrend,
            'completed' => $completedTrend,
            'response_time' => $responseTimeTrend,
        ];
    }

    /**
     * Format response time for display
     */
    protected function formatResponseTime(float $hours): string
    {
        if ($hours < 1) {
            $minutes = round($hours * 60);
            return $minutes . ' Min';
        }

        if ($hours < 24) {
            return round($hours, 1) . ' Std';
        }

        $days = floor($hours / 24);
        $remainingHours = round($hours % 24);

        if ($remainingHours > 0) {
            return $days . 'd ' . $remainingHours . 'h';
        }

        return $days . ' Tage';
    }

    /**
     * Get description for overdue count
     */
    protected function getOverdueDescription(int $count): string
    {
        if ($count === 0) {
            return 'Alle Rückrufe rechtzeitig bearbeitet';
        }

        if ($count === 1) {
            return 'Erfordert sofortige Aufmerksamkeit';
        }

        return 'Erfordern sofortige Aufmerksamkeit';
    }

    /**
     * Get description for response time
     */
    protected function getResponseTimeDescription(float $hours): string
    {
        if ($hours === 0) {
            return 'Keine Daten verfügbar';
        }

        if ($hours < 2) {
            return 'Ausgezeichnete Reaktionszeit';
        }

        if ($hours < 6) {
            return 'Gute Reaktionszeit';
        }

        if ($hours < 24) {
            return 'Durchschnittliche Reaktionszeit';
        }

        return 'Verbesserung erforderlich';
    }

    /**
     * Get color for response time based on hours
     */
    protected function getResponseTimeColor(float $hours): string
    {
        if ($hours === 0) {
            return 'gray';
        }

        if ($hours < 2) {
            return 'success';
        }

        if ($hours < 6) {
            return 'info';
        }

        if ($hours < 24) {
            return 'warning';
        }

        return 'danger';
    }

    /**
     * Can view the widget
     */
    public static function canView(): bool
    {
        return true;
    }
}
