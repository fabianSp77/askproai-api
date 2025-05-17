<?php

namespace App\Filament\Pages;

use Filament\Pages\Dashboard as BaseDashboard;
use App\Filament\Widgets\{
    StatsOverview,
    RecentCalls,
    RecentAppointments,
    SystemStatus
};

class Dashboard extends BaseDashboard
{
    public static function getWidgets(): array
    {
        return [
            StatsOverview::class,
            RecentCalls::class,
            RecentAppointments::class,
            SystemStatus::class,
        ];
    }
}
