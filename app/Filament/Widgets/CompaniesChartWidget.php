<?php

namespace App\Filament\Widgets;

use App\Models\Company;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;

class CompaniesChartWidget extends ChartWidget
{
    protected static ?string $heading = 'Unternehmen pro Monat';

    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    protected function getData(): array
    {
        $data = [];
        $labels = [];
        for ($i = 5; $i >= 0; $i--) {
            $month = Carbon::now()->subMonths($i);
            $count = Company::whereYear('created_at', $month->year)
                ->whereMonth('created_at', $month->month)
                ->count();
            $data[] = $count;
            $labels[] = $month->translatedFormat('F Y'); // Monatsnamen automatisch in Deutsch
        }

        return [
            'datasets' => [
                [
                    'label' => 'Neue Unternehmen',
                    'data' => $data,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
