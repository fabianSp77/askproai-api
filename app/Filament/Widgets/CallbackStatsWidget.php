<?php

namespace App\Filament\Widgets;

use App\Models\CallbackRequest;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Cache;

class CallbackStatsWidget extends BaseWidget
{
    protected static ?int $sort = 1;
    protected int | string | array $columnSpan = 'full';

    protected function getStats(): array
    {
        $stats = Cache::remember('callback_stats_widget_data', 60, function () {
            $total = CallbackRequest::count();
            $pending = CallbackRequest::where('status', CallbackRequest::STATUS_PENDING)->count();
            $overdue = CallbackRequest::overdue()->count();
            $urgent = CallbackRequest::where('priority', CallbackRequest::PRIORITY_URGENT)
                ->whereNotIn('status', [CallbackRequest::STATUS_COMPLETED, CallbackRequest::STATUS_CANCELLED])
                ->count();

            // SLA Metrics
            $slaMetrics = Cache::get('callback_sla_metrics', [
                'warning_count' => 0,
                'critical_count' => 0,
                'escalation_count' => 0,
            ]);

            // Calculate response time (average minutes from created to contacted)
            $avgResponseTime = CallbackRequest::whereNotNull('contacted_at')
                ->where('created_at', '>=', now()->subDays(7))
                ->selectRaw('AVG(TIMESTAMPDIFF(MINUTE, created_at, contacted_at)) as avg_minutes')
                ->value('avg_minutes');

            // Calculate conversion rate (contacted or completed / total created last 7 days)
            $recentTotal = CallbackRequest::where('created_at', '>=', now()->subDays(7))->count();
            $recentConverted = CallbackRequest::where('created_at', '>=', now()->subDays(7))
                ->whereIn('status', [CallbackRequest::STATUS_CONTACTED, CallbackRequest::STATUS_COMPLETED])
                ->count();
            $conversionRate = $recentTotal > 0 ? round(($recentConverted / $recentTotal) * 100, 1) : 0;

            return [
                'total' => $total,
                'pending' => $pending,
                'overdue' => $overdue,
                'urgent' => $urgent,
                'sla_warning' => $slaMetrics['warning_count'] ?? 0,
                'sla_critical' => $slaMetrics['critical_count'] ?? 0,
                'avg_response_time' => $avgResponseTime ? round($avgResponseTime, 0) : null,
                'conversion_rate' => $conversionRate,
            ];
        });

        return [
            Stat::make('Ausstehende Rückrufe', $stats['pending'])
                ->description($stats['pending'] > 5 ? 'Hohe Queue-Tiefe' : 'Normal')
                ->descriptionIcon($stats['pending'] > 5 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-minus')
                ->color($stats['pending'] > 10 ? 'danger' : ($stats['pending'] > 5 ? 'warning' : 'success'))
                ->chart([7, 5, 10, 8, 12, $stats['pending']]),

            Stat::make('Überfällige Rückrufe', $stats['overdue'])
                ->description($stats['overdue'] > 0 ? 'Sofort handeln!' : 'Keine überfälligen')
                ->descriptionIcon($stats['overdue'] > 0 ? 'heroicon-m-exclamation-triangle' : 'heroicon-m-check-circle')
                ->color($stats['overdue'] > 0 ? 'danger' : 'success')
                ->url(route('filament.admin.resources.callback-requests.index', ['activeTab' => 'overdue'])),

            Stat::make('Dringende Anfragen', $stats['urgent'])
                ->description($stats['urgent'] > 0 ? 'Hohe Priorität' : 'Keine dringenden')
                ->descriptionIcon($stats['urgent'] > 0 ? 'heroicon-m-fire' : 'heroicon-m-check')
                ->color($stats['urgent'] > 0 ? 'warning' : 'success')
                ->url(route('filament.admin.resources.callback-requests.index', ['activeTab' => 'urgent'])),

            Stat::make('Ø Reaktionszeit', $stats['avg_response_time'] ? $stats['avg_response_time'] . ' Min' : '—')
                ->description('Letzte 7 Tage')
                ->descriptionIcon('heroicon-m-clock')
                ->color(match(true) {
                    !$stats['avg_response_time'] => 'gray',
                    $stats['avg_response_time'] <= 60 => 'success',
                    $stats['avg_response_time'] <= 90 => 'warning',
                    default => 'danger',
                })
                ->chart([120, 90, 75, 60, 55, $stats['avg_response_time'] ?? 0]),

            Stat::make('Conversion Rate', $stats['conversion_rate'] . '%')
                ->description('Kontaktiert/Abgeschlossen')
                ->descriptionIcon('heroicon-m-chart-bar')
                ->color(match(true) {
                    $stats['conversion_rate'] >= 80 => 'success',
                    $stats['conversion_rate'] >= 60 => 'warning',
                    default => 'danger',
                })
                ->chart([65, 70, 75, 80, 78, $stats['conversion_rate']]),

            Stat::make('SLA Status', $stats['sla_critical'] + $stats['sla_warning'])
                ->description($stats['sla_critical'] > 0 ? 'Kritische Breaches' : 'Warnings aktiv')
                ->descriptionIcon($stats['sla_critical'] > 0 ? 'heroicon-m-x-circle' : 'heroicon-m-exclamation-triangle')
                ->color($stats['sla_critical'] > 0 ? 'danger' : ($stats['sla_warning'] > 0 ? 'warning' : 'success'))
                ->extraAttributes([
                    'title' => "Warning: {$stats['sla_warning']}, Critical: {$stats['sla_critical']}",
                ]),
        ];
    }
}
