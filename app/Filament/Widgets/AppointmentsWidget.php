<?php

namespace App\Filament\Widgets;

use App\Models\Appointment;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;

class AppointmentsWidget extends ChartWidget
{
    protected static ?string $heading = 'Termine pro Monat';
    protected int|string|array $columnSpan = 'full';

    /**
     * Liefert die Daten für das Balkendiagramm.
     * – gruppiert nach MONAT der Spalte `starts_at`
     * – füllt Monate ohne Termine mit 0
     */
    protected function getData(): array
    {
        $year = Carbon::now()->year;

        // Häufigkeit pro Monat ermitteln  (Key = Monats-Nr.)
        $counts = Appointment::query()
            ->selectRaw('MONTH(starts_at)  as month')
            ->selectRaw('COUNT(*)          as count')
            ->whereYear('starts_at', $year)
            ->groupBy('month')
            ->pluck('count', 'month')      // → [ 3 => 17, 5 => 9, … ]
            ->all();

        // 12-monatiges Array aufbauen, fehlende mit 0 füllen
        $labels = [];
        $data   = [];

        for ($m = 1; $m <= 12; $m++) {
            $labels[] = Carbon::createFromDate($year, $m, 1)
                ->locale(app()->getLocale())          // „de“ in config/app.php
                ->translatedFormat('F');              // März, April, …

            $data[] = $counts[$m] ?? 0;
        }

        return [
            'labels'   => $labels,
            'datasets' => [
                [
                    'label'           => "Termine $year",
                    'data'            => $data,
                    'backgroundColor' => 'rgba(59, 130, 246, 0.50)', // Blau
                ],
            ],
        ];
    }

    /** Diagramm-Typ */
    protected function getType(): string
    {
        return 'bar';
    }
}
