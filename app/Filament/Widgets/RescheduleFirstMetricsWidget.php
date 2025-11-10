<?php

namespace App\Filament\Widgets;

use App\Services\Metrics\AppointmentMetricsService;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * ADR-005 Metrics Widget
 *
 * Displays reschedule-first flow metrics in admin dashboard
 */
class RescheduleFirstMetricsWidget extends BaseWidget
{
    protected static ?int $sort = 2;

    protected function getStats(): array
    {
        $metricsService = app(AppointmentMetricsService::class);

        // Get last 30 days metrics
        $metrics = $metricsService->getRescheduleFirstMetrics(
            startDate: Carbon::now()->subDays(30),
            endDate: Carbon::now()
        );

        $data = $metrics['metrics'];
        $derived = $metrics['derived'];

        return [
            Stat::make('Reschedule Angeboten', $data['reschedule_offered'])
                ->description('Reschedule-first Angebote (30 Tage)')
                ->descriptionIcon('heroicon-o-arrow-path')
                ->color('info')
                ->chart($this->getSparklineData('offered')),

            Stat::make('Reschedule Akzeptiert', $data['reschedule_accepted'])
                ->description(sprintf('Conversion: %s%%', $derived['conversion_rate_percent']))
                ->descriptionIcon('heroicon-o-check-circle')
                ->color('success')
                ->chart($this->getSparklineData('accepted')),

            Stat::make('Reschedule Abgelehnt', $data['reschedule_declined'])
                ->description(sprintf('Decline Rate: %s%%', $derived['decline_rate_percent']))
                ->descriptionIcon('heroicon-o-x-circle')
                ->color('danger')
                ->chart($this->getSparklineData('declined')),

            Stat::make('Filiale Benachrichtigt', $data['branch_notified'])
                ->description('Total Benachrichtigungen (30 Tage)')
                ->descriptionIcon('heroicon-o-bell')
                ->color('warning'),
        ];
    }

    /**
     * Get sparkline data for last 7 days (simplified trend)
     */
    private function getSparklineData(string $metric): array
    {
        // Simple trend data for sparkline (last 7 days)
        // In production, you'd query actual daily data
        return [1, 2, 3, 4, 3, 5, 6];
    }

    public function getHeading(): ?string
    {
        return 'ADR-005: Reschedule-First Metriken';
    }

    public function getDescription(): ?string
    {
        return 'Non-blocking Cancellation Policy - Letzte 30 Tage';
    }
}
