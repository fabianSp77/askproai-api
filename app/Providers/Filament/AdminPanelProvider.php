<?php

namespace App\Providers\Filament;

use Filament\Panel;
use Filament\PanelProvider;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('admin')->path('admin')
            ->login()->default()
            ->authGuard('web')->middleware(['web'])
            ->discoverPages(
                in: app_path('Filament/Admin/Pages'),
                for: 'App\\Filament\\Admin\\Pages'
            )
            ->discoverResources(
                in: app_path('Filament/Admin/Resources'),
                for: 'App\\Filament\\Admin\\Resources'
            )
            // State-of-the-Art: Reihenfolge selbst festlegen!
            ->widgets([
                \App\Filament\Widgets\StatsOverviewWidget::class,
                \App\Filament\Widgets\SystemStatus::class,
                \App\Filament\Widgets\AppointmentsWidget::class,
                \App\Filament\Widgets\CustomerChartWidget::class,
                \App\Filament\Widgets\CompaniesChartWidget::class,
                \App\Filament\Widgets\LatestCustomersWidget::class,
                \App\Filament\Widgets\RecentAppointments::class,
                \App\Filament\Widgets\RecentCalls::class,
                \App\Filament\Widgets\ActivityLogWidget::class,
            ]);
    }
}
