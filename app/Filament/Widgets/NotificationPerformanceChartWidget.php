<?php

namespace App\Filament\Widgets;

use App\Models\NotificationQueue;
use Filament\Widgets\ChartWidget;

class NotificationPerformanceChartWidget extends ChartWidget
{
    protected static ?string $heading = 'Benachrichtigungsleistung nach Kanal';

    protected static ?int $sort = 10;

    protected static ?string $pollingInterval = '60s';

    protected int | string | array $columnSpan = 'full';

    /**
     * Widget disabled - notification_queue table doesn't exist in Sept 21 database backup
     * TODO: Re-enable when database is fully restored
     */
    public static function canView(): bool
    {
        return false;
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getData(): array
    {
        $companyId = auth()->user()->company_id;

        // SECURITY FIX (SEC-003): Direct company_id filtering instead of complex polymorphic traversal
        // Get notification counts by channel and status
        $channels = ['email', 'sms', 'whatsapp', 'push'];
        $sentData = [];
        $failedData = [];
        $labels = [];

        foreach ($channels as $channel) {
            $sent = NotificationQueue::where('company_id', $companyId)
                ->where('channel', $channel)
                ->where('created_at', '>=', now()->subDays(30))
                ->whereIn('status', ['sent', 'delivered'])
                ->count();

            $failed = NotificationQueue::where('company_id', $companyId)
                ->where('channel', $channel)
                ->where('created_at', '>=', now()->subDays(30))
                ->where('status', 'failed')
                ->count();

            if ($sent > 0 || $failed > 0) {
                $labels[] = ucfirst($channel);
                $sentData[] = $sent;
                $failedData[] = $failed;
            }
        }

        if (empty($labels)) {
            return [
                'datasets' => [
                    [
                        'label' => 'Keine Daten',
                        'data' => [0],
                        'backgroundColor' => 'rgba(156, 163, 175, 0.8)',
                    ],
                ],
                'labels' => ['Keine Benachrichtigungen'],
            ];
        }

        return [
            'datasets' => [
                [
                    'label' => 'Erfolgreich',
                    'data' => $sentData,
                    'backgroundColor' => 'rgba(16, 185, 129, 0.8)',
                    'borderColor' => 'rgb(16, 185, 129)',
                    'borderWidth' => 2,
                ],
                [
                    'label' => 'Fehlgeschlagen',
                    'data' => $failedData,
                    'backgroundColor' => 'rgba(239, 68, 68, 0.8)',
                    'borderColor' => 'rgb(239, 68, 68)',
                    'borderWidth' => 2,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'bottom',
                ],
                'tooltip' => [
                    'enabled' => true,
                ],
            ],
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'ticks' => [
                        'precision' => 0,
                    ],
                ],
            ],
        ];
    }

    public function getColumnSpan(): string | array | int
    {
        return 'full';
    }
}
