<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class DashboardStats extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Anzahl Kunden', \App\Models\Customer::count())
                ->icon('heroicon-o-users')
                ->color('success'),

            Stat::make(
                'Neue Kunden (diesen Monat)',
                \App\Models\Customer::whereMonth('created_at', now()->month)->count()
            )
                ->icon('heroicon-o-user-plus')
                ->color('primary'),

            Stat::make('Durchschnittliches Alter', function () {
                $customers = \App\Models\Customer::whereNotNull('birthdate')->get();
                if ($customers->isEmpty()) {
                    return 'N/A';
                }

                $avg = $customers
                    ->map(fn ($c) => now()->diffInYears($c->birthdate))
                    ->avg();

                return round($avg) . ' Jahre';
            })
                ->icon('heroicon-o-calendar')
                ->color('warning'),
        ];
    }
}
