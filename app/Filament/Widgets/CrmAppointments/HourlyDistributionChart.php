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
 * Hourly Distribution Chart
 *
 * Bar chart showing call distribution by hour of day.
 * Helps identify peak call times for resource planning.
 * SECURITY: All queries filtered by company_id for multi-tenancy.
 * FEATURE: Supports company, agent, and time_range filters.
 */
class HourlyDistributionChart extends ChartWidget
{
    use InteractsWithPageFilters;
    use HasCrmFilters;

    protected static ?string $heading = 'Anrufe nach Tageszeit';
    protected static bool $isLazy = true;
    protected static ?string $maxHeight = '350px';

    protected function getData(): array
    {
        try {
            $companyId = $this->getEffectiveCompanyId();
            $agentId = $this->getEffectiveAgentId();
            $timeRangeStart = $this->getTimeRangeStart();
            $cacheKey = "crm_hourly_distribution_{$this->getFilterCacheKey()}";

            $data = Cache::remember($cacheKey, 60, function () use ($companyId, $agentId, $timeRangeStart) {
                $query = Call::query()
                    ->select(
                        DB::raw('HOUR(created_at) as hour'),
                        DB::raw('COUNT(*) as total'),
                        DB::raw("SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as successful"),
                        DB::raw("SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed")
                    )
                    ->groupBy(DB::raw('HOUR(created_at)'));

                if ($companyId) {
                    $query->where('company_id', $companyId);
                }
                if ($agentId) {
                    $query->where('retell_agent_id', $agentId);
                }
                if ($timeRangeStart) {
                    $query->where('created_at', '>=', $timeRangeStart);
                }

                return $query->get()->keyBy('hour');
            });

            // Build 24-hour labels and data
            $labels = [];
            $successfulCounts = [];
            $failedCounts = [];

            for ($hour = 0; $hour < 24; $hour++) {
                $labels[] = sprintf('%02d:00', $hour);
                $hourData = $data->get($hour);
                $successfulCounts[] = $hourData?->successful ?? 0;
                $failedCounts[] = $hourData?->failed ?? 0;
            }

            return [
                'datasets' => [
                    [
                        'label' => 'Erfolgreich',
                        'data' => $successfulCounts,
                        'backgroundColor' => '#10B981',
                        'borderRadius' => 4,
                    ],
                    [
                        'label' => 'Fehlgeschlagen',
                        'data' => $failedCounts,
                        'backgroundColor' => '#EF4444',
                        'borderRadius' => 4,
                    ],
                ],
                'labels' => $labels,
            ];
        } catch (\Throwable $e) {
            Log::error('[HourlyDistributionChart] getData failed', ['error' => $e->getMessage()]);
            return ['datasets' => [], 'labels' => []];
        }
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'position' => 'bottom',
                    'labels' => [
                        'usePointStyle' => true,
                        'padding' => 15,
                    ],
                ],
            ],
            'scales' => [
                'x' => [
                    'stacked' => true,
                    'grid' => ['display' => false],
                    'ticks' => [
                        'maxRotation' => 0,
                        'autoSkip' => true,
                        'maxTicksLimit' => 12,
                    ],
                ],
                'y' => [
                    'stacked' => true,
                    'beginAtZero' => true,
                    'grid' => ['color' => 'rgba(128, 128, 128, 0.1)'], // Dark mode adaptive
                ],
            ],
            'maintainAspectRatio' => false,
        ];
    }
}
