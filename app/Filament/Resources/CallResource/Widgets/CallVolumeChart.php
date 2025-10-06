<?php

namespace App\Filament\Resources\CallResource\Widgets;

use App\Models\Call;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class CallVolumeChart extends ChartWidget
{
    protected static ?string $heading = 'Anrufvolumen letzte 30 Tage';

    protected static ?int $sort = 2;

    protected int | string | array $columnSpan = 'full';

    protected static ?string $maxHeight = '300px';

    protected function getData(): array
    {
        // Cache for 5 minutes with 5-minute key granularity (aligned with TTL)
        $cacheMinute = floor(now()->minute / 5) * 5;
        return Cache::remember('call-volume-chart-' . now()->format('Y-m-d-H') . '-' . str_pad($cacheMinute, 2, '0', STR_PAD_LEFT), 300, function () {
            return $this->calculateChartData();
        });
    }

    private function calculateChartData(): array
    {
        // Single optimized query for all data
        $rawData = Call::whereBetween('created_at', [
                Carbon::now()->subDays(29)->startOfDay(),
                Carbon::now()->endOfDay()
            ])
            ->selectRaw('
                DATE(created_at) as date,
                COUNT(*) as total_count,
                SUM(CASE WHEN call_successful = 1 THEN 1 ELSE 0 END) as successful_count,
                SUM(CASE WHEN appointment_made = 1 THEN 1 ELSE 0 END) as appointment_count
            ')
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->keyBy('date');

        $labels = [];
        $data = [
            'calls' => [],
            'successful' => [],
            'appointments' => []
        ];

        // Build the chart data arrays
        for ($i = 29; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            $dateKey = $date->format('Y-m-d');
            $labels[] = $date->format('d.m');

            $dayData = $rawData->get($dateKey);

            $data['calls'][] = $dayData ? $dayData->total_count : 0;
            $data['successful'][] = $dayData ? $dayData->successful_count : 0;
            $data['appointments'][] = $dayData ? $dayData->appointment_count : 0;
        }

        // Calculate description from cached data
        $yesterdayKey = Carbon::yesterday()->format('Y-m-d');
        $todayKey = Carbon::today()->format('Y-m-d');

        $yesterdayCount = $rawData->get($yesterdayKey)?->total_count ?? 0;
        $todayCount = $rawData->get($todayKey)?->total_count ?? 0;

        $trend = $todayCount > $yesterdayCount ? '↑' : ($todayCount < $yesterdayCount ? '↓' : '→');
        $diff = abs($todayCount - $yesterdayCount);

        $description = "Heute: {$todayCount} Anrufe {$trend} ({$diff} im Vergleich zu gestern)";

        return [
            'datasets' => [
                [
                    'label' => 'Gesamt Anrufe',
                    'data' => $data['calls'],
                    'borderColor' => 'rgb(59, 130, 246)',
                    'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                    'tension' => 0.4,
                ],
                [
                    'label' => 'Erfolgreich',
                    'data' => $data['successful'],
                    'borderColor' => 'rgb(34, 197, 94)',
                    'backgroundColor' => 'rgba(34, 197, 94, 0.1)',
                    'tension' => 0.4,
                ],
                [
                    'label' => 'Termine vereinbart',
                    'data' => $data['appointments'],
                    'borderColor' => 'rgb(168, 85, 247)',
                    'backgroundColor' => 'rgba(168, 85, 247, 0.1)',
                    'tension' => 0.4,
                ],
            ],
            'labels' => $labels,
            'description' => $description,
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
                'tooltip' => [
                    'mode' => 'index',
                    'intersect' => false,
                ],
            ],
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'grid' => [
                        'display' => true,
                    ],
                    'ticks' => [
                        'precision' => 0,
                    ],
                ],
                'x' => [
                    'grid' => [
                        'display' => false,
                    ],
                ],
            ],
            'interaction' => [
                'mode' => 'nearest',
                'axis' => 'x',
                'intersect' => false,
            ],
            'maintainAspectRatio' => false,
            'responsive' => true,
        ];
    }

    public function getDescription(): ?string
    {
        try {
            // Retrieve from cached data instead of new queries
            return $this->getData()['description'] ?? null;
        } catch (\Exception $e) {
            return null;
        }
    }
}