<?php

namespace App\Filament\Widgets;

use App\Models\AppointmentModificationStat;
use Filament\Widgets\ChartWidget;
use Carbon\Carbon;

class PolicyTrendWidget extends ChartWidget
{
    protected static ?string $heading = 'Policy-Compliance Trend';

    protected static ?int $sort = 3;

    protected static ?string $pollingInterval = '60s';

    protected int | string | array $columnSpan = 'full';

    /**
     * Get chart type
     */
    protected function getType(): string
    {
        return 'line';
    }

    /**
     * Filter options
     */
    public ?string $filter = 'month';

    /**
     * Get chart data
     */
    protected function getData(): array
    {
        $companyId = auth()->user()->company_id;

        // Determine date range based on filter
        $days = match($this->filter) {
            'week' => 7,
            'month' => 30,
            'quarter' => 90,
            default => 30,
        };

        $labels = [];
        $violationsData = [];
        $cancellationsData = [];
        $reschedulesData = [];

        for ($i = $days - 1; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $labels[] = $date->format($days <= 7 ? 'D, M d' : 'M d');

            // Get violations for this day
            $violations = AppointmentModificationStat::whereHas('customer', function ($query) use ($companyId) {
                    $query->where('company_id', $companyId);
                })
                ->where('stat_type', 'violation')
                ->whereDate('created_at', $date)
                ->sum('count');

            $violationsData[] = $violations;

            // Get cancellations for this day
            $cancellations = AppointmentModificationStat::whereHas('customer', function ($query) use ($companyId) {
                    $query->where('company_id', $companyId);
                })
                ->where('stat_type', 'cancellation')
                ->whereDate('created_at', $date)
                ->sum('count');

            $cancellationsData[] = $cancellations;

            // Get reschedules for this day
            $reschedules = AppointmentModificationStat::whereHas('customer', function ($query) use ($companyId) {
                    $query->where('company_id', $companyId);
                })
                ->where('stat_type', 'reschedule')
                ->whereDate('created_at', $date)
                ->sum('count');

            $reschedulesData[] = $reschedules;
        }

        return [
            'datasets' => [
                [
                    'label' => 'Verstöße',
                    'data' => $violationsData,
                    'borderColor' => 'rgb(239, 68, 68)',
                    'backgroundColor' => 'rgba(239, 68, 68, 0.1)',
                    'fill' => true,
                    'tension' => 0.4,
                ],
                [
                    'label' => 'Stornierungen',
                    'data' => $cancellationsData,
                    'borderColor' => 'rgb(251, 191, 36)',
                    'backgroundColor' => 'rgba(251, 191, 36, 0.1)',
                    'fill' => true,
                    'tension' => 0.4,
                ],
                [
                    'label' => 'Umplanungen',
                    'data' => $reschedulesData,
                    'borderColor' => 'rgb(59, 130, 246)',
                    'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                    'fill' => true,
                    'tension' => 0.4,
                ],
            ],
            'labels' => $labels,
        ];
    }

    /**
     * Get chart options
     */
    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'bottom',
                ],
                'tooltip' => [
                    'enabled' => true,
                    'mode' => 'index',
                    'intersect' => false,
                ],
            ],
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'ticks' => [
                        'precision' => 0,
                    ],
                ],
            ],
            'interaction' => [
                'mode' => 'nearest',
                'axis' => 'x',
                'intersect' => false,
            ],
        ];
    }

    /**
     * Get filter options
     */
    protected function getFilters(): ?array
    {
        return [
            'week' => '7 Tage',
            'month' => '30 Tage',
            'quarter' => '90 Tage',
        ];
    }

    /**
     * Get column span
     */
    public function getColumnSpan(): string | array | int
    {
        return 'full';
    }
}
