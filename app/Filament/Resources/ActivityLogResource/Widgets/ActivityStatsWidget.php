<?php

namespace App\Filament\Resources\ActivityLogResource\Widgets;

use App\Models\ActivityLog;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ActivityStatsWidget extends StatsOverviewWidget
{
    protected static ?string $pollingInterval = '30s';

    protected function getStats(): array
    {
        $today = ActivityLog::today()->count();
        $yesterday = ActivityLog::whereDate('created_at', today()->subDay())->count();

        $todayTrend = $yesterday > 0
            ? round((($today - $yesterday) / $yesterday) * 100)
            : 0;

        $logins = ActivityLog::today()
            ->where('event', ActivityLog::EVENT_LOGIN)
            ->count();

        $errors = ActivityLog::today()
            ->where('type', ActivityLog::TYPE_ERROR)
            ->count();

        $highSeverity = ActivityLog::today()
            ->highSeverity()
            ->count();

        $apiCalls = ActivityLog::today()
            ->where('type', ActivityLog::TYPE_API)
            ->count();

        $uniqueUsers = ActivityLog::today()
            ->distinct('user_id')
            ->whereNotNull('user_id')
            ->count('user_id');

        return [
            Stat::make('Heutige Aktivitäten', $today)
                ->description($todayTrend >= 0
                    ? "+{$todayTrend}% gegenüber gestern"
                    : "{$todayTrend}% gegenüber gestern")
                ->descriptionIcon($todayTrend >= 0
                    ? 'heroicon-m-arrow-trending-up'
                    : 'heroicon-m-arrow-trending-down')
                ->color($todayTrend >= 0 ? 'success' : 'warning')
                ->chart($this->getActivityChart()),

            Stat::make('Anmeldungen heute', $logins)
                ->description('Erfolgreiche Logins')
                ->descriptionIcon('heroicon-m-user-circle')
                ->color('primary'),

            Stat::make('Fehler heute', $errors)
                ->description($errors > 0 ? 'Überprüfung erforderlich' : 'Keine Fehler')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color($errors > 0 ? 'danger' : 'success'),

            Stat::make('Kritische Ereignisse', $highSeverity)
                ->description($highSeverity > 0 ? 'Sofortige Überprüfung' : 'Alles in Ordnung')
                ->descriptionIcon('heroicon-m-shield-exclamation')
                ->color($highSeverity > 0 ? 'danger' : 'success'),

            Stat::make('API-Aufrufe', $apiCalls)
                ->description('Heute verarbeitet')
                ->descriptionIcon('heroicon-m-server')
                ->color('info'),

            Stat::make('Aktive Benutzer', $uniqueUsers)
                ->description('Heute aktiv')
                ->descriptionIcon('heroicon-m-users')
                ->color('warning'),
        ];
    }

    private function getActivityChart(): array
    {
        $data = [];
        for ($i = 6; $i >= 0; $i--) {
            $count = ActivityLog::whereDate('created_at', today()->subDays($i))->count();
            $data[] = $count;
        }
        return $data;
    }
}