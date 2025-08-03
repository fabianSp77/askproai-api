<?php

namespace App\Filament\Business\Pages;

use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\ChartWidget;
use Illuminate\Contracts\Support\Htmlable;

class Dashboard extends BaseDashboard
{
    protected static ?string $navigationIcon = 'heroicon-o-home';
    
    protected static ?int $navigationSort = -2;
    
    protected static string $view = 'filament.pages.dashboard';
    
    public function getTitle(): string | Htmlable
    {
        return __('Dashboard');
    }
    
    public function getWidgets(): array
    {
        return [
            \App\Filament\Business\Widgets\StatsOverview::class,
            \App\Filament\Business\Widgets\CallsChart::class,
            \App\Filament\Business\Widgets\RecentCalls::class,
            \App\Filament\Business\Widgets\UpcomingAppointments::class,
        ];
    }
    
    public function getColumns(): int | array
    {
        return [
            'md' => 2,
            'xl' => 3,
        ];
    }
}