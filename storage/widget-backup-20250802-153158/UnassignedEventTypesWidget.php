<?php

namespace App\Filament\Admin\Widgets;

use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\CalcomEventType;
use App\Models\Branch;

class UnassignedEventTypesWidget extends StatsOverviewWidget
{
    protected static ?int $sort = 2;

    protected function getStats(): array
    {
        $totalEventTypes = CalcomEventType::count();
        $activeBranches = Branch::count();
        
        return [
            Stat::make('Gesamt Event Types', $totalEventTypes)
                ->description('Alle importierten Event Types')
                ->icon('heroicon-o-calendar-days')
                ->color('primary'),
                
            Stat::make('Aktive Branches', $activeBranches)
                ->description('Mit Event Types')
                ->icon('heroicon-o-building-office')
                ->color('success'),
        ];
    }
}
