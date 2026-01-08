<?php

namespace App\Filament\Widgets\ServiceGateway;

use App\Filament\Widgets\ServiceGateway\Concerns\HasDashboardFilters;
use App\Models\AssignmentGroup;
use App\Models\ServiceCase;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Workload by Assignment Group Chart
 *
 * Bar chart showing case distribution across assignment groups.
 * SECURITY: All queries explicitly filtered by company_id for multi-tenancy isolation.
 * PERFORMANCE: Uses GROUP BY query instead of N+1 queries per group.
 * FEATURE: Supports company and time_range filters from dashboard.
 */
class WorkloadByGroupChart extends ChartWidget
{
    use InteractsWithPageFilters;
    use HasDashboardFilters;

    protected static ?string $heading = 'Workload nach Team';
    // Polling removed - Reactive via InteractsWithPageFilters handles filter updates

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
            $cacheKey = "service_gateway_workload_chart_{$this->getFilterCacheKey()}";

            $data = Cache::remember($cacheKey, config('gateway.cache.widget_stats_seconds'), function () use ($companyId, $timeRangeStart) {
            // Get assignment groups
            $groupsQuery = AssignmentGroup::query()->where('is_active', true)->ordered();
            if ($companyId) {
                $groupsQuery->where('company_id', $companyId);
            }
            $groups = $groupsQuery->get();

            // Single optimized query - count all cases by group and status
            $caseQuery = ServiceCase::query()
                ->whereIn('status', [ServiceCase::STATUS_NEW, ServiceCase::STATUS_OPEN, ServiceCase::STATUS_PENDING])
                ->select('assigned_group_id', 'status', DB::raw('COUNT(*) as count'))
                ->groupBy('assigned_group_id', 'status');

            if ($companyId) {
                $caseQuery->where('company_id', $companyId);
            }
            if ($timeRangeStart) {
                $caseQuery->where('created_at', '>=', $timeRangeStart);
            }

            $caseCounts = $caseQuery->get()->groupBy('assigned_group_id');

            $labels = [];
            $openCounts = [];
            $inProgressCounts = [];

            foreach ($groups as $group) {
                $labels[] = $group->name;
                $groupCases = $caseCounts->get($group->id, collect());

                $openCounts[] = $groupCases
                    ->whereIn('status', [ServiceCase::STATUS_NEW, ServiceCase::STATUS_OPEN])
                    ->sum('count');

                $inProgressCounts[] = $groupCases
                    ->where('status', ServiceCase::STATUS_PENDING)
                    ->sum('count');
            }

            // Add unassigned
            $labels[] = 'Nicht zugewiesen';
            $unassignedCases = $caseCounts->get(null, collect());
            $openCounts[] = $unassignedCases
                ->whereIn('status', [ServiceCase::STATUS_NEW, ServiceCase::STATUS_OPEN])
                ->sum('count');
            $inProgressCounts[] = $unassignedCases
                ->where('status', ServiceCase::STATUS_PENDING)
                ->sum('count');

            return compact('labels', 'openCounts', 'inProgressCounts');
        });

        return [
            'datasets' => [
                [
                    'label' => 'Neu/Offen',
                    'data' => $data['openCounts'],
                    'backgroundColor' => '#3B82F6',
                    'borderRadius' => 4,
                ],
                [
                    'label' => 'Wartend',
                    'data' => $data['inProgressCounts'],
                    'backgroundColor' => '#8B5CF6',
                    'borderRadius' => 4,
                ],
            ],
            'labels' => $data['labels'],
        ];
        } catch (\Throwable $e) {
            Log::error('[WorkloadByGroupChart] getData failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'datasets' => [],
                'labels' => [],
            ];
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
                    'grid' => [
                        'display' => false,
                    ],
                ],
                'y' => [
                    'stacked' => true,
                    'beginAtZero' => true,
                    'grid' => [
                        'color' => 'rgba(128, 128, 128, 0.1)', // Dark mode adaptive
                    ],
                ],
            ],
            'maintainAspectRatio' => false,
        ];
    }
}
