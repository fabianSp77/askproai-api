<?php

namespace App\Filament\Reseller\Pages;

use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Widgets\AccountWidget;
use Filament\Widgets\FilamentInfoWidget;

class Dashboard extends BaseDashboard
{
    protected static ?string $navigationIcon = 'heroicon-o-home';
    
    protected static string $view = 'filament.reseller.pages.dashboard';
    
    public function getHeading(): string
    {
        $reseller = app('current_reseller');
        return "Willkommen, {$reseller->name}!";
    }
    
    public function getSubheading(): ?string
    {
        return 'Ihr Reseller Dashboard - Übersicht über Kunden, Umsätze und Provisionen';
    }
    
    public function getWidgets(): array
    {
        return [
            \App\Filament\Reseller\Widgets\ResellerStatsOverview::class,
            \App\Filament\Reseller\Widgets\RevenueChart::class,
            \App\Filament\Reseller\Widgets\CustomerGrowthChart::class,
            \App\Filament\Reseller\Widgets\CommissionStatus::class,
            \App\Filament\Reseller\Widgets\RecentCustomerActivity::class,
        ];
    }
    
    public function getColumns(): int | string | array
    {
        return [
            'md' => 2,
            'xl' => 3,
        ];
    }
}