<?php

namespace App\Filament\Widgets;

use App\Models\Customer;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class CustomerJourneyChart extends ChartWidget
{
    protected static ?string $heading = 'Customer Journey Verteilung';

    protected static ?int $sort = 2;

    protected static ?string $maxHeight = '300px';

    protected int | string | array $columnSpan = 'full';

    protected static ?string $pollingInterval = '300s';

    /**
     * Widget disabled - journey_status column doesn't exist in Sept 21 database backup
     * TODO: Re-enable when database is fully restored
     */
    public static function canView(): bool
    {
        return false;
    }

    protected function getData(): array
    {
        // ⚠️ DISABLED: journey_status column doesn't exist
        // Get customer journey distribution
        $journeyData = Customer::select('journey_status', DB::raw('COUNT(*) as count'))
            ->groupBy('journey_status')
            ->orderBy('count', 'desc')
            ->get();

        $labels = [];
        $values = [];
        $colors = [];

        $statusMapping = [
            'initial_contact' => ['label' => 'Erstkontakt', 'color' => '#6B7280'],
            'appointment_scheduled' => ['label' => 'Termin vereinbart', 'color' => '#3B82F6'],
            'appointment_completed' => ['label' => 'Termin wahrgenommen', 'color' => '#10B981'],
            'regular_customer' => ['label' => 'Stammkunde', 'color' => '#8B5CF6'],
            'vip_customer' => ['label' => 'VIP Kunde', 'color' => '#F59E0B'],
            'inactive' => ['label' => 'Inaktiv', 'color' => '#EF4444'],
            'no_show' => ['label' => 'Nicht erschienen', 'color' => '#DC2626'],
        ];

        foreach ($journeyData as $item) {
            if (isset($statusMapping[$item->journey_status])) {
                $labels[] = $statusMapping[$item->journey_status]['label'] . " ({$item->count})";
                $values[] = $item->count;
                $colors[] = $statusMapping[$item->journey_status]['color'];
            }
        }

        return [
            'datasets' => [
                [
                    'label' => 'Kunden',
                    'data' => $values,
                    'backgroundColor' => $colors,
                    'borderColor' => $colors,
                    'borderWidth' => 0,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'right',
                ],
                'tooltip' => [
                    'callbacks' => [
                        'label' => 'function(context) {
                            let label = context.label || "";
                            if (label) {
                                const total = context.chart.data.datasets[0].data.reduce((a, b) => a + b, 0);
                                const value = context.parsed;
                                const percentage = ((value / total) * 100).toFixed(1);
                                return label + ": " + percentage + "%";
                            }
                            return label;
                        }',
                    ],
                ],
            ],
            'responsive' => true,
            'maintainAspectRatio' => false,
            'cutout' => '60%',
        ];
    }
}