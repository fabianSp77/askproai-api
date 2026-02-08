<?php

namespace App\Filament\Widgets\Premium;

use App\Filament\Widgets\Premium\Concerns\HasPremiumStyling;
use App\Models\Call;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Illuminate\Support\Facades\Log;

/**
 * Revenue Bar Chart Widget
 *
 * Displays monthly/weekly/yearly revenue as a bar chart.
 * Data sourced from Call model profit calculations.
 */
class RevenueBarChart extends ChartWidget
{
    use InteractsWithPageFilters;
    use HasPremiumStyling;

    protected static ?string $heading = null; // Custom header via view
    protected static bool $isLazy = false;
    protected static ?string $maxHeight = '350px';
    protected int|string|array $columnSpan = 2;

    public string $activeTab = 'monthly';

    protected static string $view = 'filament.widgets.premium.revenue-chart';

    /**
     * Set active tab.
     */
    public function setTab(string $tab): void
    {
        $this->activeTab = $tab;
    }

    /**
     * Get chart data based on active tab.
     * Note: Caching disabled for reactive filter updates.
     */
    protected function getData(): array
    {
        try {
            $companyId = $this->getEffectiveCompanyId();

            return match ($this->activeTab) {
                'weekly' => $this->getWeeklyData($companyId),
                'yearly' => $this->getYearlyData($companyId),
                default => $this->getMonthlyData($companyId),
            };
        } catch (\Throwable $e) {
            Log::error('[RevenueBarChart] getData failed', ['error' => $e->getMessage()]);
            return $this->getEmptyData();
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
     * Get monthly revenue data (last 12 months).
     */
    protected function getMonthlyData(?int $companyId): array
    {
        $labels = [];
        $data = [];
        $colors = $this->getPremiumChartColors();

        // Get last 12 months
        for ($i = 11; $i >= 0; $i--) {
            $month = now()->subMonths($i);
            $labels[] = $month->translatedFormat('M');

            $query = Call::query()
                ->whereMonth('created_at', $month->month)
                ->whereYear('created_at', $month->year);

            if ($companyId) {
                $query->where('company_id', $companyId);
            }

            // Sum total_profit (stored in cents)
            $profit = $query->sum('total_profit') ?? 0;
            $data[] = round($profit / 100, 2);
        }

        return [
            'datasets' => [
                [
                    'label' => 'Umsatz',
                    'data' => $data,
                    'backgroundColor' => $colors['blue'],
                    'borderColor' => $colors['blue'],
                    'borderRadius' => 8,
                    'borderSkipped' => false,
                    'barThickness' => 24,
                ],
            ],
            'labels' => $labels,
        ];
    }

    /**
     * Get weekly revenue data (last 8 weeks).
     */
    protected function getWeeklyData(?int $companyId): array
    {
        $labels = [];
        $data = [];
        $colors = $this->getPremiumChartColors();

        for ($i = 7; $i >= 0; $i--) {
            $weekStart = now()->subWeeks($i)->startOfWeek();
            $weekEnd = now()->subWeeks($i)->endOfWeek();
            $labels[] = 'KW ' . $weekStart->weekOfYear;

            $query = Call::query()
                ->whereBetween('created_at', [$weekStart, $weekEnd]);

            if ($companyId) {
                $query->where('company_id', $companyId);
            }

            $profit = $query->sum('total_profit') ?? 0;
            $data[] = round($profit / 100, 2);
        }

        return [
            'datasets' => [
                [
                    'label' => 'Umsatz',
                    'data' => $data,
                    'backgroundColor' => $colors['purple'],
                    'borderColor' => $colors['purple'],
                    'borderRadius' => 8,
                    'borderSkipped' => false,
                    'barThickness' => 32,
                ],
            ],
            'labels' => $labels,
        ];
    }

    /**
     * Get yearly revenue data (last 5 years).
     */
    protected function getYearlyData(?int $companyId): array
    {
        $labels = [];
        $data = [];
        $colors = $this->getPremiumChartColors();

        for ($i = 4; $i >= 0; $i--) {
            $year = now()->subYears($i)->year;
            $labels[] = (string) $year;

            $query = Call::query()
                ->whereYear('created_at', $year);

            if ($companyId) {
                $query->where('company_id', $companyId);
            }

            $profit = $query->sum('total_profit') ?? 0;
            $data[] = round($profit / 100, 2);
        }

        return [
            'datasets' => [
                [
                    'label' => 'Umsatz',
                    'data' => $data,
                    'backgroundColor' => $colors['green'],
                    'borderColor' => $colors['green'],
                    'borderRadius' => 8,
                    'borderSkipped' => false,
                    'barThickness' => 48,
                ],
            ],
            'labels' => $labels,
        ];
    }

    /**
     * Get empty data structure for error cases.
     */
    protected function getEmptyData(): array
    {
        return [
            'datasets' => [['label' => 'Umsatz', 'data' => []]],
            'labels' => [],
        ];
    }

    /**
     * Get total revenue for header display.
     */
    public function getTotalRevenue(): string
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

            $profit = $query->sum('total_profit') ?? 0;
            return $this->formatCurrency($profit);
        } catch (\Throwable $e) {
            return '0,00 €';
        }
    }

    /**
     * Get revenue change percentage.
     */
    public function getRevenueChange(): float
    {
        try {
            $companyId = $this->getEffectiveCompanyId();
            $currentStart = now()->startOfMonth();
            $previousStart = now()->subMonth()->startOfMonth();
            $previousEnd = now()->subMonth()->endOfMonth();

            $currentQuery = Call::query()->where('created_at', '>=', $currentStart);
            $previousQuery = Call::query()->whereBetween('created_at', [$previousStart, $previousEnd]);

            if ($companyId) {
                $currentQuery->where('company_id', $companyId);
                $previousQuery->where('company_id', $companyId);
            }

            $current = $currentQuery->sum('total_profit') ?? 0;
            $previous = $previousQuery->sum('total_profit') ?? 0;

            if ($previous == 0) {
                return $current > 0 ? 100 : 0;
            }

            return round((($current - $previous) / $previous) * 100, 1);
        } catch (\Throwable $e) {
            return 0;
        }
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getOptions(): array
    {
        return array_merge($this->getPremiumChartOptions(), [
            'plugins' => [
                'legend' => ['display' => false],
                'tooltip' => [
                    'backgroundColor' => '#27272A',
                    'titleColor' => '#FFFFFF',
                    'bodyColor' => '#A1A1AA',
                    'borderColor' => 'rgba(255, 255, 255, 0.1)',
                    'borderWidth' => 1,
                    'cornerRadius' => 8,
                    'displayColors' => false,
                    'callbacks' => [
                        'label' => "function(context) { return context.parsed.y.toLocaleString('de-DE') + ' €'; }",
                    ],
                ],
            ],
            'scales' => [
                'x' => [
                    'grid' => ['display' => false],
                    'ticks' => ['color' => '#71717A', 'font' => ['size' => 11]],
                ],
                'y' => [
                    'grid' => ['color' => 'rgba(255, 255, 255, 0.03)'],
                    'ticks' => [
                        'color' => '#71717A',
                        'font' => ['size' => 11],
                        'callback' => "function(value) { return value.toLocaleString('de-DE') + ' €'; }",
                    ],
                ],
            ],
        ]);
    }
}
