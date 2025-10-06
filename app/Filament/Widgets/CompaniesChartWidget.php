<?php
namespace App\Filament\Widgets;

use App\Models\Company;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;

class CompaniesChartWidget extends ChartWidget
{
    protected static ?string $heading = 'Unternehmen pro Monat';
    protected static ?int $sort = 2;
    protected int | string | array $columnSpan = 'full';

    protected function getData(): array
    {
        try {
        $data = [];
        $labels = [];

        // Letzten 6 Monate anzeigen
        for ($i = 5; $i >= 0; $i--) {
            $month = Carbon::now()->subMonths($i);
            $count = Company::whereYear('created_at', $month->year)
                ->whereMonth('created_at', $month->month)
                ->count();
            
            $data[] = $count;
            $labels[] = $month->format('M Y');
        }

        } catch (\Exception $e) {
            \Log::error('CompaniesChartWidget Error: ' . $e->getMessage());
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
                    'label' => 'Neue Unternehmen',
                    'data' => $data,
                    'backgroundColor' => '#f59e0b',
                    'borderColor' => '#f59e0b',
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
