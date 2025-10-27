<?php

namespace App\Filament\Widgets;

use App\Filament\Concerns\HasSecurePolymorphicQueries;
use App\Models\NotificationQueue;
use App\Models\NotificationConfiguration;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

class NotificationAnalyticsWidget extends BaseWidget
{
    use HasSecurePolymorphicQueries; // SECURITY FIX (SEC-003)

    protected static ?int $sort = 9;

    protected static ?string $pollingInterval = '30s';

    /**
     * Widget disabled - notification_queue table doesn't exist in Sept 21 database backup
     * TODO: Re-enable when database is fully restored
     */
    public static function canView(): bool
    {
        return false;
    }

    protected function getStats(): array
    {
        $companyId = auth()->user()->company_id;

        // SECURITY FIX (SEC-003): Direct company_id filtering instead of complex polymorphic traversal
        // NotificationQueue has company_id, so we filter directly for performance and security

        // 1. Total Notifications Sent (last 30 days)
        $totalSent = NotificationQueue::where('company_id', $companyId)
            ->where('created_at', '>=', now()->subDays(30))
            ->whereIn('status', ['sent', 'delivered'])
            ->count();

        // 2. Delivery Rate
        $totalAttempted = NotificationQueue::where('company_id', $companyId)
            ->where('created_at', '>=', now()->subDays(30))
            ->count();

        $deliveryRate = $totalAttempted > 0
            ? round(($totalSent / $totalAttempted) * 100, 1)
            : 100;

        // 3. Failed Notifications
        $totalFailed = NotificationQueue::where('company_id', $companyId)
            ->where('created_at', '>=', now()->subDays(30))
            ->where('status', 'failed')
            ->count();

        // 4. Average Delivery Time (in seconds)
        $avgDeliveryTime = NotificationQueue::where('company_id', $companyId)
            ->where('created_at', '>=', now()->subDays(30))
            ->whereIn('status', ['sent', 'delivered'])
            ->whereNotNull('sent_at')
            ->select(DB::raw('AVG(TIMESTAMPDIFF(SECOND, created_at, sent_at)) as avg_time'))
            ->value('avg_time');

        $avgDeliveryTimeFormatted = $avgDeliveryTime
            ? round($avgDeliveryTime, 0) . 's'
            : 'N/A';

        // 5. Active Configurations
        // SECURITY FIX (SEC-003): Use whitelist from secure trait instead of hardcoded types
        $activeConfigs = NotificationConfiguration::whereHasMorph(
                'configurable',
                $this->allowedConfigurableTypes, // From HasSecurePolymorphicQueries trait
                function ($query) use ($companyId) {
                    $query->where('company_id', $companyId);
                }
            )
            ->where('is_enabled', true)
            ->count();

        // 6. Most Used Channel
        $channelStats = NotificationQueue::where('company_id', $companyId)
            ->where('created_at', '>=', now()->subDays(30))
            ->select('channel', DB::raw('COUNT(*) as count'))
            ->groupBy('channel')
            ->orderByDesc('count')
            ->first();

        $mostUsedChannel = $channelStats
            ? ucfirst($channelStats->channel) . " ({$channelStats->count})"
            : 'N/A';

        return [
            Stat::make('Gesendete Benachrichtigungen', $totalSent)
                ->description('Erfolgreich zugestellt (30 Tage)')
                ->descriptionIcon('heroicon-m-paper-airplane')
                ->color('success')
                ->chart($this->getSentNotificationsChart($companyId)),

            Stat::make('Zustellrate', "{$deliveryRate}%")
                ->description($totalAttempted > 0 ? "{$totalSent} von {$totalAttempted} zugestellt" : 'Keine Versuche')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color($deliveryRate >= 95 ? 'success' : ($deliveryRate >= 85 ? 'warning' : 'danger')),

            Stat::make('Fehlgeschlagene', $totalFailed)
                ->description('Fehler bei der Zustellung')
                ->descriptionIcon('heroicon-m-x-circle')
                ->color($totalFailed > 10 ? 'danger' : 'warning'),

            Stat::make('Ø Zustellzeit', $avgDeliveryTimeFormatted)
                ->description('Durchschnittliche Verarbeitungszeit')
                ->descriptionIcon('heroicon-m-clock')
                ->color($avgDeliveryTime && $avgDeliveryTime < 300 ? 'success' : 'warning'),

            Stat::make('Aktive Konfigurationen', $activeConfigs)
                ->description('Aktivierte Benachrichtigungen')
                ->descriptionIcon('heroicon-m-bell')
                ->color('info'),

            Stat::make('Meist genutzter Kanal', $mostUsedChannel)
                ->description('Häufigster Benachrichtigungskanal')
                ->descriptionIcon('heroicon-m-megaphone')
                ->color('primary'),
        ];
    }

    /**
     * Get chart data for sent notifications over last 7 days
     */
    protected function getSentNotificationsChart(int $companyId): array
    {
        $data = [];

        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i);
            // SECURITY FIX (SEC-003): Direct company_id filtering
            $count = NotificationQueue::where('company_id', $companyId)
                ->whereDate('created_at', $date)
                ->whereIn('status', ['sent', 'delivered'])
                ->count();

            $data[] = $count;
        }

        return $data;
    }

    public function getColumnSpan(): string | array | int
    {
        return 'full';
    }
}
