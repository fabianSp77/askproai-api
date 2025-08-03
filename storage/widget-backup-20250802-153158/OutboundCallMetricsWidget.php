<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Call;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class OutboundCallMetricsWidget extends ChartWidget
{
    protected static ?string $heading = 'Outbound Call Performance';

    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 'full';

    protected static ?string $maxHeight = '300px';

    public ?string $filter = 'week';

    protected function getData(): array
    {
        $days = $this->filter === 'week' ? 7 : 30;
        $startDate = now()->subDays($days);

        $data = Call::where('company_id', auth()->user()->company_id)
            ->where('direction', 'outbound')
            ->where('created_at', '>=', $startDate)
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('COUNT(*) as total'),
                DB::raw('SUM(CASE WHEN status = "completed" THEN 1 ELSE 0 END) as successful'),
                DB::raw('SUM(CASE WHEN status IN ("failed", "no-answer") THEN 1 ELSE 0 END) as failed'),
                DB::raw('AVG(CASE WHEN status = "completed" THEN duration_sec ELSE NULL END) as avg_duration')
            )
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $labels = [];
        $datasets = [
            'total' => [],
            'successful' => [],
            'failed' => [],
            'avg_duration' => [],
        ];

        // Fill in missing dates
        for ($i = $days - 1; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $labels[] = Carbon::parse($date)->format('M d');

            $dayData = $data->firstWhere('date', $date);

            $datasets['total'][] = $dayData?->total ?? 0;
            $datasets['successful'][] = $dayData?->successful ?? 0;
            $datasets['failed'][] = $dayData?->failed ?? 0;
            $datasets['avg_duration'][] = $dayData ? round($dayData->avg_duration ?? 0) : 0;
        }

        return [
            'datasets' => [
                [
                    'label' => 'Total Calls',
                    'data' => $datasets['total'],
                    'backgroundColor' => 'rgba(59, 130, 246, 0.3)',
                    'borderColor' => 'rgb(59, 130, 246)',
                    'type' => 'line',
                    'yAxisID' => 'y',
                ],
                [
                    'label' => 'Successful',
                    'data' => $datasets['successful'],
                    'backgroundColor' => 'rgba(34, 197, 94, 0.3)',
                    'borderColor' => 'rgb(34, 197, 94)',
                    'type' => 'bar',
                    'yAxisID' => 'y',
                ],
                [
                    'label' => 'Failed',
                    'data' => $datasets['failed'],
                    'backgroundColor' => 'rgba(239, 68, 68, 0.3)',
                    'borderColor' => 'rgb(239, 68, 68)',
                    'type' => 'bar',
                    'yAxisID' => 'y',
                ],
                [
                    'label' => 'Avg Duration (sec)',
                    'data' => $datasets['avg_duration'],
                    'backgroundColor' => 'rgba(168, 85, 247, 0.3)',
                    'borderColor' => 'rgb(168, 85, 247)',
                    'type' => 'line',
                    'yAxisID' => 'y1',
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
                    'position' => 'left',
                    'title' => [
                        'display' => true,
                        'text' => 'Number of Calls',
                    ],
                ],
                'y1' => [
                    'beginAtZero' => true,
                    'position' => 'right',
                    'grid' => [
                        'drawOnChartArea' => false,
                    ],
                    'title' => [
                        'display' => true,
                        'text' => 'Duration (seconds)',
                    ],
                ],
            ],
            'interaction' => [
                'mode' => 'index',
                'intersect' => false,
            ],
        ];
    }

    protected function getFilters(): ?array
    {
        return [
            'week' => 'Last 7 days',
            'month' => 'Last 30 days',
        ];
    }
}
