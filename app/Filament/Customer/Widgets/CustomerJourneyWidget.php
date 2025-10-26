<?php

namespace App\Filament\Customer\Widgets;

use App\Models\Customer;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class CustomerJourneyWidget extends ChartWidget
{
    protected static ?string $heading = 'Kundenstatus Verteilung';

    protected static ?int $sort = 5;

    protected static ?string $maxHeight = '300px';

    protected int | string | array $columnSpan = 'full';

    protected static ?string $pollingInterval = '300s';

    protected function getData(): array
    {
        $companyId = auth()->user()->company_id;

        // Get customer journey distribution for this company
        $journeyData = Customer::select('journey_status', DB::raw('COUNT(*) as count'))
            ->where('company_id', $companyId)
            ->groupBy('journey_status')
            ->orderBy('count', 'desc')
            ->get();

        $labels = [];
        $values = [];
        $colors = [];

        // Status mapping with German labels and colors
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

        // If no data, show empty state
        if (empty($values)) {
            $labels = ['Keine Daten'];
            $values = [1];
            $colors = ['#E5E7EB'];
        }

        return [
            'datasets' => [
                [
                    'label' => 'Kunden',
                    'data' => $values,
                    'backgroundColor' => $colors,
                    'borderColor' => $colors,
                    'borderWidth' => 2,
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
                    'labels' => [
                        'padding' => 15,
                        'usePointStyle' => true,
                        'font' => [
                            'size' => 12,
                        ],
                    ],
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

    protected function getFooter(): ?string
    {
        $companyId = auth()->user()->company_id;

        // Total customers
        $totalCustomers = Customer::where('company_id', $companyId)->count();

        // Active customers (not inactive or no-show)
        $activeCustomers = Customer::where('company_id', $companyId)
            ->whereNotIn('journey_status', ['inactive', 'no_show'])
            ->count();

        // VIP + Regular customers
        $valuableCustomers = Customer::where('company_id', $companyId)
            ->whereIn('journey_status', ['vip_customer', 'regular_customer'])
            ->count();

        $activePercentage = $totalCustomers > 0 ? round(($activeCustomers / $totalCustomers) * 100, 1) : 0;
        $valuablePercentage = $totalCustomers > 0 ? round(($valuableCustomers / $totalCustomers) * 100, 1) : 0;

        return "Gesamt: {$totalCustomers} Kunden | Aktiv: {$activeCustomers} ({$activePercentage}%) | Stammkunden + VIP: {$valuableCustomers} ({$valuablePercentage}%)";
    }
}
