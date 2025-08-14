<?php

namespace App\Filament\Widgets;

use App\Models\Appointment;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class AppointmentsWidget extends ChartWidget
{
    protected static ?string $heading = 'Termine pro Monat';

    protected int|string|array $columnSpan = 'full';

    protected function getData(): array
    {
        $appointments = Appointment::select(
            DB::raw('MONTH(starts_at) as month'),
            DB::raw('COUNT(*) as count')
        )
            ->whereYear('starts_at', now()->year)
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        return [
            'labels' => $appointments->pluck('month')->map(function ($month) {
                // Monatsnamen auf Deutsch ausgeben
                $de = [
                    1 => 'Januar', 2 => 'Februar', 3 => 'März', 4 => 'April',
                    5 => 'Mai', 6 => 'Juni', 7 => 'Juli', 8 => 'August',
                    9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Dezember',
                ];

                return $de[intval($month)] ?? $month;
            }),
            'datasets' => [
                [
                    'label' => 'Termine',
                    'data' => $appointments->pluck('count'),
                ],
            ],
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
