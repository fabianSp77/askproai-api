<?php

namespace App\Filament\Resources\NotificationQueueResource\Widgets;

use App\Models\NotificationQueue;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

class NotificationStats extends BaseWidget
{
    protected static ?string $pollingInterval = '30s';

    protected function getStats(): array
    {
        // SECURITY FIX (SEC-003): Add company isolation to prevent cross-tenant data exposure
        $companyId = auth()->user()->company_id;
        $today = now()->startOfDay();
        $yesterday = now()->subDay()->startOfDay();

        // Get today's stats
        $todayStats = NotificationQueue::query()
            ->where('company_id', $companyId)
            ->where('created_at', '>=', $today)
            ->selectRaw('
                COUNT(*) as total,
                SUM(CASE WHEN status = "sent" THEN 1 ELSE 0 END) as sent,
                SUM(CASE WHEN status = "delivered" THEN 1 ELSE 0 END) as delivered,
                SUM(CASE WHEN status = "failed" THEN 1 ELSE 0 END) as failed,
                SUM(CASE WHEN status = "pending" THEN 1 ELSE 0 END) as pending
            ')
            ->first();

        // Get yesterday's stats for comparison
        $yesterdayStats = NotificationQueue::query()
            ->where('company_id', $companyId)
            ->whereBetween('created_at', [$yesterday, $today])
            ->selectRaw('
                COUNT(*) as total,
                SUM(CASE WHEN status = "delivered" THEN 1 ELSE 0 END) as delivered
            ')
            ->first();

        // Calculate delivery rate
        $deliveryRate = $todayStats->total > 0
            ? round(($todayStats->delivered / $todayStats->total) * 100, 1)
            : 0;

        // Calculate changes
        $totalChange = $todayStats->total - $yesterdayStats->total;
        $deliveredChange = $todayStats->delivered - $yesterdayStats->delivered;

        // Get channel distribution
        $channelStats = NotificationQueue::query()
            ->where('company_id', $companyId)
            ->where('created_at', '>=', $today)
            ->selectRaw('channel, COUNT(*) as count')
            ->groupBy('channel')
            ->pluck('count', 'channel')
            ->toArray();

        return [
            Stat::make('Benachrichtigungen heute', number_format($todayStats->total))
                ->description($this->formatChange($totalChange) . ' zum Vortag')
                ->descriptionIcon($totalChange >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($totalChange >= 0 ? 'success' : 'warning')
                ->chart($this->getHourlyChart()),

            Stat::make('Zustellrate', $deliveryRate . '%')
                ->description(number_format($todayStats->delivered) . ' zugestellt')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color($deliveryRate >= 90 ? 'success' : ($deliveryRate >= 70 ? 'warning' : 'danger')),

            Stat::make('Ausstehend', number_format($todayStats->pending))
                ->description('In Warteschlange')
                ->descriptionIcon('heroicon-m-clock')
                ->color($todayStats->pending > 100 ? 'warning' : 'primary'),

            Stat::make('Fehlgeschlagen', number_format($todayStats->failed))
                ->description($todayStats->total > 0 ? round(($todayStats->failed / $todayStats->total) * 100, 1) . '% Fehlerrate' : '0% Fehlerrate')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color($todayStats->failed > 0 ? 'danger' : 'success'),

            Stat::make('E-Mail', number_format($channelStats['email'] ?? 0))
                ->description('Heute gesendet')
                ->descriptionIcon('heroicon-m-envelope')
                ->color('primary'),

            Stat::make('SMS/WhatsApp', number_format(($channelStats['sms'] ?? 0) + ($channelStats['whatsapp'] ?? 0)))
                ->description('Heute gesendet')
                ->descriptionIcon('heroicon-m-device-phone-mobile')
                ->color('success'),
        ];
    }

    protected function formatChange(int $change): string
    {
        if ($change === 0) {
            return 'Keine Änderung';
        }

        $sign = $change > 0 ? '+' : '';
        return $sign . number_format($change);
    }

    protected function getHourlyChart(): array
    {
        $companyId = auth()->user()->company_id;

        $hourlyData = NotificationQueue::query()
            ->where('company_id', $companyId)
            ->where('created_at', '>=', now()->subHours(24))
            ->selectRaw('HOUR(created_at) as hour, COUNT(*) as count')
            ->groupBy('hour')
            ->orderBy('hour')
            ->pluck('count')
            ->toArray();

        // Fill missing hours with 0
        $chart = array_fill(0, 24, 0);
        foreach ($hourlyData as $hour => $count) {
            $chart[$hour] = $count;
        }

        // Return last 12 hours
        $currentHour = now()->hour;
        $result = [];
        for ($i = 11; $i >= 0; $i--) {
            $hour = ($currentHour - $i + 24) % 24;
            $result[] = $chart[$hour];
        }

        return $result;
    }
}