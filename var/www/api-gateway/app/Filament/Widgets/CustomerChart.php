<?php

namespace App\Filament\Widgets;

use App\Models\Customer;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class CustomerStats extends BaseWidget
{
    protected static ?int $sort = 3;

    protected function getStats(): array
    {
        return [
            Stat::make('Kunden (Gesamt)', Customer::count())
                ->description('Alle registrierten Kunden')
                ->chart([mt_rand(1, 10), mt_rand(1, 10), mt_rand(1, 10), mt_rand(1, 10), mt_rand(1, 10), mt_rand(1, 10), mt_rand(1, 10)])
                ->color('success'),
        ];
    }
}
