<?php

namespace App\Filament\Widgets;

use App\Models\Company;
use App\Models\Customer;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;

class CompanyGrowthChart extends ChartWidget
{
    protected static ?string $heading = 'Wachstum (30 Tage)';

    protected static ?int $sort = 4;

    protected int | string | array $columnSpan = 1;

    protected function getData(): array
    {
        $labels = [];
        $companies = [];
        $customers = [];

        for ($i = 29; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);

            // Show labels only for every 5th day to avoid crowding
            if ($i % 5 === 0) {
                $labels[] = $date->format('d.m');
            } else {
                $labels[] = '';
            }

            $companies[] = Company::where('created_at', '<=', $date->endOfDay())->count();
            $customers[] = Customer::where('created_at', '<=', $date->endOfDay())->count();
        }

        return [
            'datasets' => [
                [
                    'label' => 'Unternehmen',
                    'data' => $companies,
                    'borderColor' => 'rgb(99, 102, 241)',
                    'backgroundColor' => 'rgba(99, 102, 241, 0.1)',
                    'fill' => true,
                    'tension' => 0.3,
                ],
                [
                    'label' => 'Kunden',
                    'data' => $customers,
                    'borderColor' => 'rgb(34, 197, 94)',
                    'backgroundColor' => 'rgba(34, 197, 94, 0.1)',
                    'fill' => true,
                    'tension' => 0.3,
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
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                ],
            ],
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'bottom',
                ],
            ],
            'interaction' => [
                'intersect' => false,
                'mode' => 'index',
            ],
        ];
    }
}