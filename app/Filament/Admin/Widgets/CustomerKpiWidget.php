<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Appointment;
use App\Models\Customer;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Number;

class CustomerKpiWidget extends BaseWidget
{
    protected static ?string $pollingInterval = '30s';

    protected function getStats(): array
    {
        $company = auth()->user()?->company;

        if (! $company) {
            return [];
        }

        // Total customers
        $totalCustomers = Customer::where('company_id', $company->id)->count();

        // New customers this month
        $newCustomersMonth = Customer::where('company_id', $company->id)
            ->where('created_at', '>=', now()->startOfMonth())
            ->count();

        // Active customers (with appointments in last 90 days)
        $activeCustomers = Customer::where('company_id', $company->id)
            ->whereHas('appointments', function ($query) {
                $query->where('starts_at', '>=', now()->subDays(90));
            })
            ->count();

        // Average appointments per customer
        $totalAppointments = Appointment::where('company_id', $company->id)->count();
        $avgAppointments = $totalCustomers > 0
            ? round($totalAppointments / $totalCustomers, 1)
            : 0;

        return [
            Stat::make('Gesamtkunden', Number::format($totalCustomers))
                ->description('Registrierte Kunden')
                ->descriptionIcon('heroicon-m-user-group')
                ->color('primary')
                ->extraAttributes([
                    'class' => 'customer-stat-gradient-primary',
                ]),

            Stat::make('Neue Kunden', $newCustomersMonth)
                ->description('Diesen Monat')
                ->descriptionIcon('heroicon-m-user-plus')
                ->color('success')
                ->extraAttributes([
                    'class' => 'customer-stat-gradient-success',
                ]),

            Stat::make('Aktive Kunden', $activeCustomers)
                ->description('Letzte 90 Tage')
                ->descriptionIcon('heroicon-m-chart-bar-square')
                ->color('info')
                ->extraAttributes([
                    'class' => 'customer-stat-gradient-info',
                ]),

            Stat::make('Ø Termine/Kunde', $avgAppointments)
                ->description('Durchschnittlich')
                ->descriptionIcon('heroicon-m-calendar')
                ->color($avgAppointments >= 3 ? 'success' : ($avgAppointments >= 1.5 ? 'warning' : 'danger'))
                ->extraAttributes([
                    'class' => $avgAppointments >= 3 ? 'customer-stat-gradient-success' : ($avgAppointments >= 1.5 ? 'customer-stat-gradient-warning' : 'stat-card-gradient-danger'),
                ]),
        ];
    }

    protected function getColumns(): int
    {
        return 4;
    }
}
