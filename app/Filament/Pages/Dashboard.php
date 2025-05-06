<?php

namespace App\Filament\Pages;

use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    /** Widgets, die im Header erscheinen */
    protected static ?array $widgets = [
        \App\Filament\Widgets\DashboardStats::class,
        // weitere Widgets hier ergänzen …
    ];
}
