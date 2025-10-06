<?php

namespace App\Filament\Widgets;

use App\Models\Customer;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class CustomerChartWidget extends ChartWidget
{
    protected static ?string $heading = 'Kunden pro Monat';
    protected int | string | array $columnSpan = 'full';
    protected static ?int $sort = 3;

    protected function getData(): array
    {
        try {
        $data = Customer::select(DB::raw('MONTH(created_at) as month'), DB::raw('COUNT(*) as count'))
            ->whereYear('created_at', date('Y'))
            ->groupBy('month')
            ->orderBy('month')
            ->get()
            ->toArray();

        $labels = [];
        $counts = [];

        for ($i = 1; $i <= 12; $i++) {
            $labels[] = Carbon::create()->month($i)->format('F');
            $monthData = collect($data)->firstWhere('month', $i);
            $counts[] = $monthData ? $monthData['count'] : 0;
        }

        } catch (\Exception $e) {
            \Log::error('CustomerChartWidget Error: ' . $e->getMessage());
            return [
                'datasets' => [
                    [
                        'label' => 'Fehler beim Laden',
                        'data' => [],
                        'backgroundColor' => '#FF6384',
                    ],
                ],
                'labels' => [],
            ];
        }

        return [
            'datasets' => [
                [
                    'label' => 'Neue Kunden',
                    'data' => $counts,
                    'backgroundColor' => '#36A2EB',
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}#
