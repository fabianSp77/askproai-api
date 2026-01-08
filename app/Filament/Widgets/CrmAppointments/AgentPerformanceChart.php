<?php

namespace App\Filament\Widgets\CrmAppointments;

use App\Filament\Widgets\CrmAppointments\Concerns\HasCrmFilters;
use App\Models\Call;
use App\Models\RetellAgent;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Agent Performance Chart
 *
 * Horizontal bar chart comparing agent performance.
 * Shows total calls vs conversions per agent, sorted by conversion rate.
 * SECURITY: All queries filtered by company_id for multi-tenancy.
 * FEATURE: Supports company and time_range filters.
 */
class AgentPerformanceChart extends ChartWidget
{
    use InteractsWithPageFilters;
    use HasCrmFilters;

    protected static ?string $heading = 'Agent Performance';
    protected static bool $isLazy = true;
    protected static ?string $maxHeight = '350px';

    protected int|string|array $columnSpan = [
        'default' => 1,
        'md' => 1,
        'xl' => 1,
    ];

    protected function getData(): array
    {
        try {
            $companyId = $this->getEffectiveCompanyId();
            $timeRangeStart = $this->getTimeRangeStart();
            $cacheKey = "crm_agent_performance_{$this->getFilterCacheKey()}";

            $data = Cache::remember($cacheKey, 60, function () use ($companyId, $timeRangeStart) {
                $query = Call::query()->successful()
                    ->select(
                        'retell_agent_id',
                        DB::raw('COUNT(*) as total_calls'),
                        DB::raw('SUM(CASE WHEN has_appointment THEN 1 ELSE 0 END) as conversions')
                    )
                    ->groupBy('retell_agent_id')
                    ->havingRaw('COUNT(*) >= 1');

                if ($companyId) {
                    $query->where('company_id', $companyId);
                }
                if ($timeRangeStart) {
                    $query->where('created_at', '>=', $timeRangeStart);
                }

                // Sort by conversion rate descending
                $query->orderByRaw('SUM(CASE WHEN has_appointment THEN 1 ELSE 0 END) / COUNT(*) DESC');

                return $query->limit(10)->get();
            });

            // Get agent names
            $agentIds = $data->pluck('retell_agent_id')->toArray();
            $agents = RetellAgent::whereIn('agent_id', $agentIds)
                ->pluck('name', 'agent_id')
                ->toArray();

            $labels = [];
            $callCounts = [];
            $conversionCounts = [];

            foreach ($data as $row) {
                $agentName = $agents[$row->retell_agent_id] ?? 'Agent ' . substr($row->retell_agent_id, 0, 8);
                $rate = $row->total_calls > 0 ? round(($row->conversions / $row->total_calls) * 100) : 0;
                $labels[] = $this->truncateLabel($agentName) . " ({$rate}%)";
                $callCounts[] = $row->total_calls - $row->conversions; // Non-converted
                $conversionCounts[] = $row->conversions;
            }

            return [
                'datasets' => [
                    [
                        'label' => 'Termine',
                        'data' => $conversionCounts,
                        'backgroundColor' => '#10B981',
                        'borderRadius' => 4,
                    ],
                    [
                        'label' => 'Ohne Termin',
                        'data' => $callCounts,
                        'backgroundColor' => '#94A3B8',
                        'borderRadius' => 4,
                    ],
                ],
                'labels' => $labels,
            ];
        } catch (\Throwable $e) {
            Log::error('[AgentPerformanceChart] getData failed', ['error' => $e->getMessage()]);
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
            'indexAxis' => 'y',
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
                    'beginAtZero' => true,
                    'grid' => ['color' => 'rgba(128, 128, 128, 0.1)'], // Dark mode adaptive
                ],
                'y' => [
                    'stacked' => true,
                    'grid' => ['display' => false],
                ],
            ],
            'maintainAspectRatio' => false,
        ];
    }

    protected function truncateLabel(string $label, int $maxLength = 15): string
    {
        return mb_strlen($label) <= $maxLength ? $label : mb_substr($label, 0, $maxLength - 3) . '...';
    }
}
