<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Call;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PerformanceMetricsWidget extends ChartWidget
{
    protected static ?string $heading = 'Performance Metrics';
    
    protected static ?int $sort = 2;
    
    protected int | string | array $columnSpan = 'full';
    
    protected static ?string $maxHeight = '300px';
    
    public ?string $filter = '7';
    
    protected function getData(): array
    {
        $days = (int) $this->filter;
        $startDate = Carbon::now()->subDays($days);
        
        // Fixed: Using duration_sec not duration_seconds - updated 2025-09-09
        $callsData = Call::selectRaw('DATE(created_at) as date, COUNT(*) as count, AVG(duration_sec) as avg_duration')
            ->where('created_at', '>=', $startDate)
            ->groupBy('date')
            ->orderBy('date')
            ->get();
        
        $labels = [];
        $callCounts = [];
        $avgDurations = [];
        
        for ($i = $days - 1; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            $dateStr = $date->format('Y-m-d');
            $labels[] = $date->format('M d');
            
            $dayData = $callsData->firstWhere('date', $dateStr);
            $callCounts[] = $dayData ? $dayData->count : 0;
            $avgDurations[] = $dayData ? round($dayData->avg_duration / 60, 1) : 0;
        }
        
        return [
            'datasets' => [
                [
                    'label' => 'Calls',
                    'data' => $callCounts,
                    'backgroundColor' => 'rgba(59, 130, 246, 0.3)',
                    'borderColor' => 'rgb(59, 130, 246)',
                    'yAxisID' => 'y',
                ],
                [
                    'label' => 'Avg Duration (min)',
                    'data' => $avgDurations,
                    'backgroundColor' => 'rgba(16, 185, 129, 0.3)',
                    'borderColor' => 'rgb(16, 185, 129)',
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
    
    protected function getFilters(): ?array
    {
        return [
            '7' => 'Last 7 days',
            '14' => 'Last 14 days',
            '30' => 'Last 30 days',
            '90' => 'Last 90 days',
        ];
    }
    
    protected function getOptions(): array
    {
        return [
            'scales' => [
                'y' => [
                    'type' => 'linear',
                    'display' => true,
                    'position' => 'left',
                    'title' => [
                        'display' => true,
                        'text' => 'Number of Calls',
                    ],
                ],
                'y1' => [
                    'type' => 'linear',
                    'display' => true,
                    'position' => 'right',
                    'title' => [
                        'display' => true,
                        'text' => 'Duration (minutes)',
                    ],
                    'grid' => [
                        'drawOnChartArea' => false,
                    ],
                ],
            ],
        ];
    }
    
    public static function canView(): bool
    {
        return auth()->user()?->hasRole('Admin') ?? false;
    }
}