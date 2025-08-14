<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Company;
use App\Models\Customer;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverviewWidget extends BaseWidget
{
    protected static ?int $sort = 0;

    protected function getStats(): array
    {
        return [
            Stat::make('Kunden', Customer::count())
                ->description('Gesamt-Anzahl der Kunden')
                ->descriptionIcon('heroicon-m-users')
                ->color('success'),

            Stat::make('Neue Kunden', Customer::whereMonth('created_at', now()->month)->count())
                ->description('In diesem Monat')
                ->descriptionIcon('heroicon-m-user-plus')
                ->color('primary'),

            Stat::make('Unternehmen', Company::count())
                ->description('Registrierte Firmen')
                ->descriptionIcon('heroicon-m-building-office')
                ->color('warning'),
        ];
    }
}
