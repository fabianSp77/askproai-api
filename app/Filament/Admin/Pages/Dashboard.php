<?php
namespace App\Filament\Admin\Pages;

use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    protected static ?string $navigationIcon = 'heroicon-o-home';
    protected static ?string $title = 'Dashboard';
    protected static ?string $navigationLabel = 'Dashboard';
    // Use default Filament dashboard view
    // protected static string $view = 'filament.admin.pages.dashboard-fixed';
    
    // Temporarily disable widgets to isolate issue
    // public function getWidgets(): array
    // {
    //     return [
    //         \App\Filament\Admin\Widgets\StatsOverviewWidget::class,
    //     ];
    // }
}
