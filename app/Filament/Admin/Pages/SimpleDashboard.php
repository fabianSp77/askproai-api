<?php

namespace App\Filament\Admin\Pages;

use Filament\Pages\Dashboard;

class SimpleDashboard extends Dashboard
{
    protected static ?string $navigationIcon = 'heroicon-o-home';
    protected static ?string $title = 'Dashboard';
    protected static ?string $navigationLabel = 'Dashboard';
    protected static ?int $navigationSort = -2;
    
    public function getWidgets(): array
    {
        return [
            \App\Filament\Admin\Widgets\GlobalTenantFilter::class,
            \App\Filament\Admin\Widgets\ApiHealthOverview::class,
            \App\Filament\Admin\Widgets\StatsOverview::class,
            \App\Filament\Admin\Widgets\SystemStatsOverview::class,
            \App\Filament\Admin\Widgets\RecentAppointments::class,
            \App\Filament\Admin\Widgets\RecentCalls::class,
            \App\Filament\Admin\Widgets\RecentActivityWidget::class,
            \App\Filament\Admin\Widgets\ActivityLogWidget::class,
        ];
    }
    
    public function getColumns(): int | string | array
    {
        return [
            'default' => 1,
            'sm' => 2,
            'lg' => 3,
        ];
    }
}