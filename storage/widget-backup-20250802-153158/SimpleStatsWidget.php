<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Customer;
use App\Models\Appointment;
use App\Models\Call;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class SimpleStatsWidget extends StatsOverviewWidget
{
    protected static ?int $sort = 1;
    
    protected function getStats(): array
    {
        // Direct database counts without any scopes or filters
        $customerCount = Customer::withoutGlobalScopes()->count();
        $appointmentCount = Appointment::withoutGlobalScopes()->count();
        $callCount = Call::withoutGlobalScopes()->count();
        
        return [
            Stat::make('Total Customers (Raw)', $customerCount)
                ->description('Direct DB count')
                ->color('success'),
                
            Stat::make('Total Appointments (Raw)', $appointmentCount)
                ->description('Direct DB count')
                ->color('primary'),
                
            Stat::make('Total Calls (Raw)', $callCount)
                ->description('Direct DB count')
                ->color('warning'),
        ];
    }
}