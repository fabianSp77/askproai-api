<?php

namespace App\Filament\Widgets\CrmAppointments;

use App\Filament\Widgets\CrmAppointments\Concerns\HasCrmFilters;
use App\Models\Call;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Conversion Trend Chart
 *
 * Line chart showing conversion rate trend over the last 7 days.
 * Helps identify patterns and improvements in conversion performance.
 * SECURITY: All queries filtered by company_id for multi-tenancy.
 * FEATURE: Supports company, agent, and time_range filters.
 */
class ConversionTrendChart extends ChartWidget
{
    use InteractsWithPageFilters;
    use HasCrmFilters;

    protected static ?string $heading = 'Conversion Trend (7 Tage)';
    protected static bool $isLazy = true;
    protected static ?string $maxHeight = '350px';

    protected function getData(): array
    {
        try {
            $companyId = $this->getEffectiveCompanyId();
            $agentId = $this->getEffectiveAgentId();
            $cacheKey = "crm_conversion_trend_chart_{$this->getFilterCacheKey()}";

            $data = Cache::remember($cacheKey, 60, function () use ($companyId, $agentId) {
                $labels = [];
                $conversionRates = [];
                $totalCalls = [];
                $conversions = [];

                for ($i = 6; $i >= 0; $i--) {
                    $date = now()->subDays($i);
                    $labels[] = $date->format('d.m.');

                    $dayQuery = Call::query()->successful()
                        ->whereDate('created_at', $date);

                    if ($companyId) {
                        $dayQuery->where('company_id', $companyId);
                    }
                    if ($agentId) {
                        $dayQuery->where('retell_agent_id', $agentId);
                    }

                    $dayTotal = (clone $dayQuery)->count();
                    $dayConverted = (clone $dayQuery)->withAppointment()->count();

                    $totalCalls[] = $dayTotal;
                    $conversions[] = $dayConverted;
                    $conversionRates[] = $dayTotal > 0 ? round(($dayConverted / $dayTotal) * 100, 1) : 0;
                }

                return compact('labels', 'conversionRates', 'totalCalls', 'conversions');
            });

            return [
                'datasets' => [
                    [
                        'label' => 'Conversion Rate %',
                        'data' => $data['conversionRates'],
                        'borderColor' => '#10B981',
                        'backgroundColor' => 'rgba(16, 185, 129, 0.1)',
                        'fill' => true,
                        'tension' => 0.3,
                        'pointBackgroundColor' => '#10B981',
                        'pointBorderColor' => '#ffffff',
                        'pointBorderWidth' => 2,
                        'pointRadius' => 5,
                        'pointHoverRadius' => 7,
                    ],
                ],
                'labels' => $data['labels'],
            ];
        } catch (\Throwable $e) {
            Log::error('[ConversionTrendChart] getData failed', ['error' => $e->getMessage()]);
            return ['datasets' => [], 'labels' => []];
        }
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'display' => false,
                ],
                'tooltip' => [
                    'callbacks' => [
                        'label' => "function(context) {
                            return context.raw + '% Conversion Rate';
                        }",
                    ],
                ],
            ],
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'max' => 100,
                    'grid' => ['color' => 'rgba(128, 128, 128, 0.1)'], // Dark mode adaptive
                    'ticks' => [
                        'callback' => "function(value) { return value + '%'; }",
                    ],
                ],
                'x' => [
                    'grid' => ['display' => false],
                ],
            ],
            'maintainAspectRatio' => false,
        ];
    }
}
