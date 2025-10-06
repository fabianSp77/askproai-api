<?php

namespace App\Filament\Widgets;

use App\Models\Integration;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Cache;

class IntegrationHealthWidget extends BaseWidget
{
    protected static ?int $sort = 2;

    protected int | string | array $columnSpan = 'full';

    protected function getStats(): array
    {
        try {
        $stats = Cache::remember('integration_health_stats', 60, function () {
            return [
                'total' => Integration::count(),
                'active' => Integration::where('is_active', true)->count(),
                'healthy' => Integration::where('health_status', Integration::HEALTH_HEALTHY)->count(),
                'degraded' => Integration::where('health_status', Integration::HEALTH_DEGRADED)->count(),
                'unhealthy' => Integration::where('health_status', Integration::HEALTH_UNHEALTHY)->count(),
                'errors' => Integration::where('status', Integration::STATUS_ERROR)->count(),
                'syncing' => Integration::where('status', Integration::STATUS_SYNCING)->count(),
                'total_api_calls' => Integration::sum('api_calls_count'),
                'total_records' => Integration::sum('records_synced'),
                'recent_syncs' => Integration::where('last_sync_at', '>=', now()->subHour())->count(),
            ];
        });

        } catch (\Exception $e) {
            \Log::error('IntegrationHealthWidget Error: ' . $e->getMessage());
            return [
                Stat::make('Fehler', 'Widget-Fehler')
                    ->description('Integration Widget konnte nicht geladen werden')
                    ->color('danger'),
            ];
        }

        return [
            Stat::make('Gesamt Integrationen', $stats['total'])
                ->description("{$stats['active']} aktiv")
                ->descriptionIcon('heroicon-o-check-circle')
                ->color($stats['active'] > 0 ? 'success' : 'gray')
                ->chart($this->getIntegrationTrend()),

            Stat::make('Gesundheitsstatus', $this->getHealthPercentage($stats))
                ->description($this->getHealthDescription($stats))
                ->descriptionIcon($this->getHealthIcon($stats))
                ->color($this->getHealthColor($stats))
                ->chart($this->getHealthTrend()),

            Stat::make('API Aufrufe', number_format($stats['total_api_calls']))
                ->description($this->getApiCallsDescription($stats))
                ->descriptionIcon('heroicon-o-arrow-trending-up')
                ->color('info')
                ->chart($this->getApiCallsTrend()),

            Stat::make('Synchronisierte Datensätze', number_format($stats['total_records']))
                ->description("{$stats['recent_syncs']} kürzliche Syncs")
                ->descriptionIcon('heroicon-o-arrow-path')
                ->color($stats['recent_syncs'] > 0 ? 'warning' : 'gray')
                ->chart($this->getSyncTrend()),
        ];
    }

    protected function getHealthPercentage(array $stats): string
    {
        if ($stats['total'] == 0) {
            return '0%';
        }

        $healthyPercentage = round(($stats['healthy'] / $stats['total']) * 100);
        return "{$healthyPercentage}% Gesund";
    }

    protected function getHealthDescription(array $stats): string
    {
        $parts = [];

        if ($stats['degraded'] > 0) {
            $parts[] = "{$stats['degraded']} beeinträchtigt";
        }

        if ($stats['unhealthy'] > 0) {
            $parts[] = "{$stats['unhealthy']} ungesund";
        }

        if ($stats['errors'] > 0) {
            $parts[] = "{$stats['errors']} Fehler";
        }

        return empty($parts) ? 'Alle systeme funktionieren' : implode(', ', $parts);
    }

    protected function getHealthIcon(array $stats): string
    {
        if ($stats['errors'] > 0 || $stats['unhealthy'] > 0) {
            return 'heroicon-o-exclamation-triangle';
        }

        if ($stats['degraded'] > 0) {
            return 'heroicon-o-exclamation-circle';
        }

        return 'heroicon-o-check-circle';
    }

    protected function getHealthColor(array $stats): string
    {
        if ($stats['errors'] > 0 || $stats['unhealthy'] > 0) {
            return 'danger';
        }

        if ($stats['degraded'] > 0) {
            return 'warning';
        }

        return 'success';
    }

    protected function getApiCallsDescription(array $stats): string
    {
        $hourlyAvg = Integration::where('last_sync_at', '>=', now()->subDay())
            ->avg('api_calls_count') ?? 0;

        return round($hourlyAvg) . ' Ø pro Stunde';
    }

    protected function getIntegrationTrend(): array
    {
        // Mock data - would fetch real trend data
        return [1, 2, 3, 4, 5, 6, 7];
    }

    protected function getHealthTrend(): array
    {
        // Mock data - would fetch real health trend
        return [100, 95, 90, 85, 88, 92, 95];
    }

    protected function getApiCallsTrend(): array
    {
        // Mock data - would fetch real API calls trend
        return [10, 15, 12, 18, 22, 25, 20];
    }

    protected function getSyncTrend(): array
    {
        // Mock data - would fetch real sync trend
        return [5, 8, 6, 10, 12, 15, 14];
    }
}