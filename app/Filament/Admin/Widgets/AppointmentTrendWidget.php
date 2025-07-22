<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Appointment;
use Filament\Widgets\ChartWidget;

class AppointmentTrendWidget extends ChartWidget
{
    protected static ?string $heading = 'Termintrend (Letzte 30 Tage)';

    protected static ?int $sort = 2;

    protected static ?string $pollingInterval = '60s';

    protected function getData(): array
    {
        $company = auth()->user()?->company;

        if (! $company) {
            return [
                'datasets' => [],
                'labels' => [],
            ];
        }

        $data = Appointment::where('company_id', $company->id)
            ->where('starts_at', '>=', now()->subDays(30))
            ->selectRaw('DATE(starts_at) as date')
            ->selectRaw('COUNT(*) as total')
            ->selectRaw('SUM(CASE WHEN status = "completed" THEN 1 ELSE 0 END) as completed')
            ->selectRaw('SUM(CASE WHEN status = "cancelled" THEN 1 ELSE 0 END) as cancelled')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $labels = [];
        $totals = [];
        $completed = [];
        $cancelled = [];

        // Fill in missing dates
        $period = now()->subDays(30)->toPeriod(now(), '1 day');

        foreach ($period as $date) {
            $dateStr = $date->format('Y-m-d');
            $dayData = $data->firstWhere('date', $dateStr);

            $labels[] = $date->format('d.m');
            $totals[] = $dayData ? $dayData->total : 0;
            $completed[] = $dayData ? $dayData->completed : 0;
            $cancelled[] = $dayData ? $dayData->cancelled : 0;
        }

        return [
            'datasets' => [
                [
                    'label' => 'Gesamt',
                    'data' => $totals,
                    'backgroundColor' => 'rgba(168, 85, 247, 0.1)',
                    'borderColor' => 'rgb(168, 85, 247)',
                    'borderWidth' => 2,
                    'tension' => 0.3,
                    'fill' => true,
                ],
                [
                    'label' => 'Abgeschlossen',
                    'data' => $completed,
                    'backgroundColor' => 'rgba(16, 185, 129, 0.1)',
                    'borderColor' => 'rgb(16, 185, 129)',
                    'borderWidth' => 2,
                    'tension' => 0.3,
                    'fill' => true,
                ],
                [
                    'label' => 'Abgesagt',
                    'data' => $cancelled,
                    'backgroundColor' => 'rgba(239, 68, 68, 0.1)',
                    'borderColor' => 'rgb(239, 68, 68)',
                    'borderWidth' => 2,
                    'tension' => 0.3,
                    'fill' => true,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'bottom',
                ],
            ],
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'ticks' => [
                        'stepSize' => 1,
                    ],
                ],
            ],
            'maintainAspectRatio' => false,
        ];
    }

    public function getColumnSpan(): int|string|array
    {
        return 'full';
    }

    protected function getHeight(): int
    {
        return 300;
    }
}
