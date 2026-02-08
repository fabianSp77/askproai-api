<?php

namespace App\Filament\Widgets\Premium;

use App\Filament\Widgets\Premium\Concerns\HasPremiumStyling;
use App\Models\Call;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Illuminate\Support\Facades\Log;

/**
 * Spending Donut Chart Widget
 *
 * Displays cost breakdown by category as a donut chart.
 * Categories based on call outcomes/types.
 */
class SpendingDonutChart extends ChartWidget
{
    use InteractsWithPageFilters;
    use HasPremiumStyling;

    protected static ?string $heading = null;
    protected static bool $isLazy = false;
    protected static ?string $maxHeight = '300px';
    protected int|string|array $columnSpan = 1;

    protected static string $view = 'filament.widgets.premium.spending-donut';

    /**
     * Get chart data.
     * Note: Caching disabled for reactive filter updates.
     */
    protected function getData(): array
    {
        try {
            $companyId = $this->getEffectiveCompanyId();
            $colors = $this->getPremiumChartColors();
            $timeRangeStart = $this->getTimeRangeStart();
            $timeRangeEnd = $this->getTimeRangeEnd();

            // Get call counts by status/outcome
            $query = Call::query();
            if ($companyId) {
                $query->where('company_id', $companyId);
            }
            if ($timeRangeStart) {
                $query->where('created_at', '>=', $timeRangeStart);
            }
            if ($timeRangeEnd) {
                $query->where('created_at', '<=', $timeRangeEnd);
            }

            // Group by status or outcome
            $statusCounts = (clone $query)
                ->selectRaw('status, COUNT(*) as count')
                ->groupBy('status')
                ->pluck('count', 'status')
                ->toArray();

            // Map to categories with German labels
            $categories = [
                'completed' => ['label' => 'Erfolgreich', 'color' => $colors['green']],
                'failed' => ['label' => 'Fehlgeschlagen', 'color' => $colors['red']],
                'ongoing' => ['label' => 'Laufend', 'color' => $colors['blue']],
                'in-progress' => ['label' => 'In Bearbeitung', 'color' => $colors['purple']],
                'in_progress' => ['label' => 'In Bearbeitung', 'color' => $colors['purple']],
                'missed' => ['label' => 'Verpasst', 'color' => $colors['yellow']],
                'busy' => ['label' => 'Besetzt', 'color' => $colors['orange']],
                'no_answer' => ['label' => 'Keine Antwort', 'color' => '#6B7280'],
            ];

            $labels = [];
            $data = [];
            $backgroundColor = [];

            foreach ($statusCounts as $status => $count) {
                $config = $categories[$status] ?? ['label' => ucfirst($status), 'color' => '#6B7280'];
                $labels[] = $config['label'];
                $data[] = $count;
                $backgroundColor[] = $config['color'];
            }

            return [
                'datasets' => [
                    [
                        'label' => 'Anrufe',
                        'data' => $data,
                        'backgroundColor' => $backgroundColor,
                        'borderColor' => '#18181B',
                        'borderWidth' => 3,
                        'hoverOffset' => 8,
                    ],
                ],
                'labels' => $labels,
            ];
        } catch (\Throwable $e) {
            Log::error('[SpendingDonutChart] getData failed', ['error' => $e->getMessage()]);
            return ['datasets' => [], 'labels' => []];
        }
    }

    /**
     * Handle filter updates - refresh the chart when filters change.
     */
    public function updatedFilters(): void
    {
        $this->dispatch('updateChartData', data: $this->getCachedData());
    }

    /**
     * Get total calls for center display.
     */
    public function getTotalCalls(): int
    {
        try {
            $companyId = $this->getEffectiveCompanyId();
            $timeRangeStart = $this->getTimeRangeStart();

            $query = Call::query();
            if ($companyId) {
                $query->where('company_id', $companyId);
            }
            if ($timeRangeStart) {
                $query->where('created_at', '>=', $timeRangeStart);
            }

            return $query->count();
        } catch (\Throwable $e) {
            return 0;
        }
    }

    /**
     * Get legend data for custom legend.
     */
    public function getLegendData(): array
    {
        $chartData = $this->getData();
        $legend = [];

        if (!empty($chartData['labels']) && !empty($chartData['datasets'][0]['data'])) {
            $total = array_sum($chartData['datasets'][0]['data']);
            foreach ($chartData['labels'] as $index => $label) {
                $value = $chartData['datasets'][0]['data'][$index];
                $percent = $total > 0 ? round(($value / $total) * 100, 1) : 0;
                $legend[] = [
                    'label' => $label,
                    'value' => $value,
                    'percent' => $percent,
                    'color' => $chartData['datasets'][0]['backgroundColor'][$index],
                ];
            }
        }

        return $legend;
    }

    protected function getType(): string
    {
        return 'doughnut';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => ['display' => false],
                'tooltip' => [
                    'backgroundColor' => '#27272A',
                    'titleColor' => '#FFFFFF',
                    'bodyColor' => '#A1A1AA',
                    'borderColor' => 'rgba(255, 255, 255, 0.1)',
                    'borderWidth' => 1,
                    'cornerRadius' => 8,
                ],
            ],
            'cutout' => '75%',
            'maintainAspectRatio' => false,
        ];
    }
}
