<?php

namespace App\Filament\Widgets;

use App\Models\PolicyConfiguration;
use App\Models\AppointmentModificationStat;
use App\Models\Appointment;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PolicyChartsWidget extends ChartWidget
{
    protected static ?string $heading = 'Policy-Verstöße nach Typ';

    protected static ?int $sort = 2;

    protected static ?string $pollingInterval = '60s';

    protected int | string | array $columnSpan = 'full';

    /**
     * Widget disabled - metadata column doesn't exist in appointment_modification_stats table (Sept 21 backup)
     * TODO: Re-enable when database is fully restored
     */
    public static function canView(): bool
    {
        return false;
    }

    /**
     * Get chart type
     */
    protected function getType(): string
    {
        return 'bar';
    }

    /**
     * Get chart data
     */
    protected function getData(): array
    {
        $companyId = auth()->user()->company_id;

        // Get violations by policy type (last 30 days)
        $violationsByType = AppointmentModificationStat::whereHas('customer', function ($query) use ($companyId) {
                $query->where('company_id', $companyId);
            })
            ->where('stat_type', 'violation')
            ->where('created_at', '>=', now()->subDays(30))
            ->select(
                DB::raw('JSON_EXTRACT(metadata, "$.policy_type") as policy_type'),
                DB::raw('SUM(count) as total_violations')
            )
            ->groupBy('policy_type')
            ->orderByDesc('total_violations')
            ->get();

        $labels = [];
        $data = [];
        $backgroundColors = [
            'rgba(255, 99, 132, 0.8)',   // Red
            'rgba(54, 162, 235, 0.8)',   // Blue
            'rgba(255, 206, 86, 0.8)',   // Yellow
            'rgba(75, 192, 192, 0.8)',   // Green
            'rgba(153, 102, 255, 0.8)',  // Purple
            'rgba(255, 159, 64, 0.8)',   // Orange
        ];

        foreach ($violationsByType as $index => $violation) {
            $policyType = str_replace('"', '', $violation->policy_type);
            $labels[] = ucfirst(str_replace('_', ' ', $policyType));
            $data[] = $violation->total_violations;
        }

        // If no data, show empty state
        if (empty($data)) {
            $labels = ['Keine Verstöße'];
            $data = [0];
        }

        return [
            'datasets' => [
                [
                    'label' => 'Verstöße',
                    'data' => $data,
                    'backgroundColor' => array_slice($backgroundColors, 0, count($data)),
                    'borderColor' => array_slice($backgroundColors, 0, count($data)),
                    'borderWidth' => 2,
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
                    'display' => false,
                ],
                'tooltip' => [
                    'enabled' => true,
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
