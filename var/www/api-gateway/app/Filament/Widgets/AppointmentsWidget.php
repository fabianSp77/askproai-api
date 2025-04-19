<?php

namespace App\Filament\Widgets;

use App\Models\Appointment;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class AppointmentsWidget extends ChartWidget
{
    protected static ?string $heading = 'Termine pro Monat';
    protected int | string | array $columnSpan = 'full';

    protected function getData(): array
    {
        $appointments = Appointment::select(
            DB::raw('MONTH(start_time) as month'),
            DB::raw('COUNT(*) as count')
        )
        ->whereYear('start_time', now()->year)
        ->groupBy('month')
        ->orderBy('month')
        ->get();

        return [
            'labels' => $appointments->pluck('month')->map(function($month) {
                return date('F', mktime(0, 0, 0, $month, 1));
            }),
            'datasets' => [
                [
                    'label' => 'Termine',
                    'data' => $appointments->pluck('count'),
                    'backgroundColor' => 'rgba(59, 130, 246, 0.5)', // Blau-Akzent
                ]
            ]
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
