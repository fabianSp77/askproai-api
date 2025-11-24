<?php

namespace App\Filament\Widgets;

use App\Services\Monitoring\CalcomMetricsCollector;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Cache;

/**
 * Appointment Sync Status Widget
 *
 * 🆕 PHASE 3 FIX (2025-11-24): Dashboard widget for appointment sync monitoring
 * Displays real-time sync status, failure rates, and critical alerts
 * Prevents situations like Siebert appointment (pending 5 days undetected)
 */
class AppointmentSyncStatusWidget extends StatsOverviewWidget
{
    protected static ?int $sort = 2;

    protected int | string | array $columnSpan = 'full';

    protected function getHeading(): ?string
    {
        return 'Appointment Sync Status (Cal.com)';
    }

    protected function getStats(): array
    {
        // Collect metrics from CalcomMetricsCollector
        $collector = new CalcomMetricsCollector();
        $metrics = Cache::remember('calcom:appointment_sync:metrics', 60, function() use ($collector) {
            $allMetrics = $collector->collectAllMetrics();
            return $allMetrics['synchronization']['appointments'] ?? [];
        });

        // Extract metrics with safe fallbacks
        $successRate = $metrics['success_rate_24h'] ?? 100;
        $total24h = $metrics['total_24h'] ?? 0;
        $syncedCount = $metrics['synced_24h'] ?? 0;
        $pendingTotal = $metrics['pending_total'] ?? 0;
        $pendingStale = $metrics['pending_stale'] ?? 0;
        $failedTotal = $metrics['failed_total'] ?? 0;
        $failedAncient = $metrics['failed_ancient'] ?? 0;
        $manualReview = $metrics['requires_manual_review'] ?? 0;
        $healthStatus = $metrics['health_status'] ?? 'unknown';
        $alerts = $metrics['alerts'] ?? [];

        // Build stats array
        $stats = [];

        // Overall Health Status
        $stats[] = Stat::make('Sync Health', ucfirst($healthStatus))
            ->description($this->getHealthDescription($healthStatus, count($alerts)))
            ->descriptionIcon($this->getHealthIcon($healthStatus))
            ->color($this->getHealthColor($healthStatus));

        // Success Rate (24h)
        $stats[] = Stat::make('Success Rate (24h)', $successRate . '%')
            ->description("{$syncedCount} of {$total24h} synced successfully")
            ->descriptionIcon($successRate >= 95 ? 'heroicon-m-check-circle' : 'heroicon-m-exclamation-triangle')
            ->color($successRate >= 95 ? 'success' : ($successRate >= 80 ? 'warning' : 'danger'))
            ->chart($this->getSyncRateChart());

        // Pending Appointments
        $stats[] = Stat::make('Pending Sync', $pendingTotal)
            ->description($pendingStale > 0 ? "{$pendingStale} stale (>1h)" : 'All recent')
            ->descriptionIcon($pendingStale > 0 ? 'heroicon-m-clock' : 'heroicon-m-arrow-path')
            ->color($pendingStale > 10 ? 'warning' : ($pendingStale > 0 ? 'info' : 'success'));

        // Failed Appointments
        $stats[] = Stat::make('Failed Sync', $failedTotal)
            ->description($failedAncient > 0 ? "{$failedAncient} ancient (>24h)" : ($failedTotal > 0 ? 'Recent failures' : 'None'))
            ->descriptionIcon($failedTotal > 0 ? 'heroicon-m-x-circle' : 'heroicon-m-check')
            ->color($failedAncient > 0 ? 'danger' : ($failedTotal > 0 ? 'warning' : 'success'));

        // Manual Review Required (CRITICAL)
        $stats[] = Stat::make('Manual Review', $manualReview)
            ->description($manualReview > 0 ? '⚠️ Requires attention!' : 'None')
            ->descriptionIcon($manualReview > 0 ? 'heroicon-m-exclamation-circle' : 'heroicon-m-check-badge')
            ->color($manualReview > 0 ? 'danger' : 'success');

        // Active Alerts
        $stats[] = Stat::make('Active Alerts', count($alerts))
            ->description($this->getAlertsDescription($alerts))
            ->descriptionIcon(count($alerts) > 0 ? 'heroicon-m-bell-alert' : 'heroicon-m-bell-slash')
            ->color(count($alerts) > 0 ? 'danger' : 'success');

        return $stats;
    }

    /**
     * Get health description based on status
     */
    private function getHealthDescription(string $status, int $alertCount): string
    {
        return match($status) {
            'healthy' => 'All systems operational',
            'degraded' => 'Some sync issues detected',
            'critical' => "⚠️ {$alertCount} critical issue(s)",
            default => 'Status unknown'
        };
    }

    /**
     * Get health icon based on status
     */
    private function getHealthIcon(string $status): string
    {
        return match($status) {
            'healthy' => 'heroicon-m-check-circle',
            'degraded' => 'heroicon-m-exclamation-triangle',
            'critical' => 'heroicon-m-x-circle',
            default => 'heroicon-m-question-mark-circle'
        };
    }

    /**
     * Get health color based on status
     */
    private function getHealthColor(string $status): string
    {
        return match($status) {
            'healthy' => 'success',
            'degraded' => 'warning',
            'critical' => 'danger',
            default => 'gray'
        };
    }

    /**
     * Get alerts description
     */
    private function getAlertsDescription(array $alerts): string
    {
        if (empty($alerts)) {
            return 'No issues detected';
        }

        $criticalCount = collect($alerts)->where('severity', 'critical')->count();
        $warningCount = collect($alerts)->where('severity', 'warning')->count();

        $parts = [];
        if ($criticalCount > 0) $parts[] = "{$criticalCount} critical";
        if ($warningCount > 0) $parts[] = "{$warningCount} warning";

        return implode(', ', $parts);
    }

    /**
     * Get sync rate chart (last 7 data points from cache)
     */
    protected function getSyncRateChart(): array
    {
        $history = Cache::get('calcom:appointment_sync:history', []);

        // Pad with 100s if insufficient data
        while (count($history) < 7) {
            array_unshift($history, 100);
        }

        return array_slice($history, -7);
    }

    /**
     * Polling interval - refresh every 5 minutes
     */
    protected static ?string $pollingInterval = '300s';
}
