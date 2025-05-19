<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Customer;
use App\Models\Call;
use App\Models\Appointment;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class TotalCustomersWidget extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Kunden gesamt', Customer::count())
                ->icon('heroicon-o-user-group'),
            Stat::make('Neue Kunden (30 Tage)', 
                Customer::where('created_at', '>=', now()->subDays(30))->count())
                ->icon('heroicon-o-user-plus'),
            Stat::make('Anrufe letzte 30 Tage', 
                Call::where('created_at', '>=', now()->subDays(30))->count())
                ->icon('heroicon-o-phone'),
            Stat::make('Termine letzte 30 Tage', 
                Appointment::where('created_at', '>=', now()->subDays(30))->count())
                ->icon('heroicon-o-calendar'),
        ];
    }
}
