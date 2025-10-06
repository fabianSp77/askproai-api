<?php

namespace App\Filament\Widgets;

use App\Models\Call;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;

class RecentCallsChart extends ChartWidget
{
    protected static ?string $heading = 'Anrufstatistik (7 Tage)';

    protected static ?int $sort = 3;

    protected int | string | array $columnSpan = 1;

    protected function getData(): array
    {
        $data = [];
        $labels = [];
        $inbound = [];
        $outbound = [];

        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            $labels[] = $date->format('d.m');

            $inbound[] = Call::whereDate('created_at', $date)
                ->where('direction', 'inbound')
                ->count();

            $outbound[] = Call::whereDate('created_at', $date)
                ->where('direction', 'outbound')
                ->count();
        }

        return [
            'datasets' => [
                [
                    'label' => 'Eingehend',
                    'data' => $inbound,
                    'backgroundColor' => 'rgba(34, 197, 94, 0.3)',
                    'borderColor' => 'rgb(34, 197, 94)',
                ],
                [
                    'label' => 'Ausgehend',
                    'data' => $outbound,
                    'backgroundColor' => 'rgba(59, 130, 246, 0.3)',
                    'borderColor' => 'rgb(59, 130, 246)',
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getOptions(): array
    {
        return [
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'ticks' => [
                        'stepSize' => 1,
                    ],
                ],
            ],
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'bottom',
                ],
            ],
        ];
    }
}