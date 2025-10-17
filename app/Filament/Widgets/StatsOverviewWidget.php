<?php
namespace App\Filament\Widgets;

use App\Models\Customer;
use App\Models\Company;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Log;

class StatsOverviewWidget extends BaseWidget
{
    protected static ?int $sort = 0;
    
    protected function getStats(): array
    {
        try {
            $totalCustomers = Customer::count();
            $newCustomersMonth = Customer::whereMonth('created_at', now()->month)->count();
            $totalCompanies = Company::count();

            return [
                Stat::make('Kunden', $totalCustomers)
                    ->description('Gesamt-Anzahl der Kunden')
                    ->descriptionIcon('heroicon-m-users')
                    ->color('success'),

                Stat::make('Neue Kunden', $newCustomersMonth)
                    ->description('In diesem Monat')
                    ->descriptionIcon('heroicon-m-user-plus')
                    ->color('primary'),

                Stat::make('Unternehmen', $totalCompanies)
                    ->description('Registrierte Firmen')
                    ->descriptionIcon('heroicon-m-building-office')
                    ->color('warning'),
            ];
        } catch (QueryException $exception) {
            // Table doesn't exist yet - log and return empty stats instead of crashing dashboard
            if ($exception->getCode() === '42S02') {
                Log::warning('[StatsOverviewWidget] Required table not found. Dashboard tables may still be migrating.', [
                    'error' => $exception->getMessage(),
                ]);
                return [];
            }
            // Re-throw other database exceptions
            throw $exception;
        }
    }
}
