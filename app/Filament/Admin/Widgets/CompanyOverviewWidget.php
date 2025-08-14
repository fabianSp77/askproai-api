<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Company;
use App\Models\Staff;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class CompanyOverviewWidget extends BaseWidget
{
    protected static ?int $sort = 3;

    protected function getStats(): array
    {
        return [
            Stat::make('Durchschnittliche Mitarbeiter', function () {
                $companies = Company::count();
                $staff = Staff::count();

                return $companies > 0 ? round($staff / $companies, 1) : 0;
            })
                ->description('Pro Unternehmen')
                ->chart([7, 2, 10, 3, 15, 4, 17])
                ->color('info'),

            Stat::make('Neue Unternehmen', Company::whereMonth('created_at', now()->month)->count())
                ->description('In diesem Monat')
                ->chart([3, 5, 7, 12, 15, 15, 12])
                ->color('success'),

            Stat::make('Inaktive Unternehmen', Company::where('active', false)->count())
                ->description('Ohne kürzliche Aktivität')
                ->chart([15, 12, 10, 8, 5, 4, 2])
                ->color('danger'),
        ];
    }
}
