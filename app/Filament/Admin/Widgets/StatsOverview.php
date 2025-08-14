<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Appointment;
use App\Models\Call;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $callsToday = Call::whereDate('created_at', today())->count();
        $appointmentsToday = Appointment::whereDate('starts_at', today())->count();
        $avgDuration = (int) Call::whereNotNull('duration_sec')->avg('duration_sec');
        $noShowsWeek = Appointment::where('status', 'no-show')
            ->whereBetween('starts_at', [now()->startOfWeek(), now()->endOfWeek()])
            ->count();

        return [
            Stat::make('Anrufe heute', $callsToday),
            Stat::make('Termine heute', $appointmentsToday),
            Stat::make('Ø Gesprächsdauer', $avgDuration.' s'),
            Stat::make('No-Shows diese Woche', $noShowsWeek),
        ];
    }
}
