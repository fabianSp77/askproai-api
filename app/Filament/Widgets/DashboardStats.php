<?php

namespace App\Filament\Widgets;

use App\Models\Customer;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Schema;

class DashboardStats extends StatsOverviewWidget
{
    //  🔄  Polling ausgeschaltet  ( = null / weglassen )

    protected function getStats(): array
    {
        $total = Customer::count();
        $withBirthday = Schema::hasColumn('customers', 'birthdate')
            ? Customer::whereNotNull('birthdate')->count()
            : 0;

        return [
            Stat::make('Gesamtkunden', $total),
            Stat::make('Mit Geburtsdatum', $withBirthday),
        ];
    }
}
