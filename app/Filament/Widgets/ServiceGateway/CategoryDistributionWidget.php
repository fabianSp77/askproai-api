<?php

namespace App\Filament\Widgets\ServiceGateway;

use App\Models\ServiceCase;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Category Distribution Widget
 *
 * ServiceNow-style pie chart showing cases by category.
 * Shows top categories with "Andere" grouping for remaining.
 * SECURITY: All queries explicitly filtered by company_id for multi-tenancy isolation.
 * PERFORMANCE: Uses single query with JOIN instead of N+1 queries.
 * FEATURE: Supports company filter from dashboard for super-admins.
 */
class CategoryDistributionWidget extends ChartWidget
{
    use InteractsWithPageFilters;

    protected static ?string $heading = 'Cases nach Kategorie';
    protected static ?string $pollingInterval = '60s';

    protected static bool $isLazy = true;
    protected static ?string $maxHeight = '300px';

    protected array $categoryColors = [
        '#3B82F6', '#10B981', '#F59E0B', '#EF4444', '#8B5CF6',
        '#EC4899', '#06B6D4', '#84CC16', '#F97316', '#6B7280',
    ];

    protected function getEffectiveCompanyId(): ?int
    {
        $filteredCompanyId = $this->filters['company_id'] ?? null;
        if ($filteredCompanyId) {
            return (int) $filteredCompanyId;
        }

        $user = Auth::user();
        if ($user && $user->hasAnyRole(['super_admin', 'super-admin', 'Admin', 'reseller_admin'])) {
            return null;
        }

        return $user?->company_id;
    }

    protected function getData(): array
    {
        try {
            $companyId = $this->getEffectiveCompanyId();
            $cacheKey = $companyId ? "service_gateway_category_distribution_{$companyId}" : 'service_gateway_category_distribution_all';

            $data = Cache::remember($cacheKey, config('gateway.cache.widget_stats_seconds'), function () use ($companyId) {
            $baseQuery = ServiceCase::query()->open();
            if ($companyId) {
                $baseQuery->where('service_cases.company_id', $companyId);
            }

            $topCategories = (clone $baseQuery)
                ->join('service_case_categories', 'service_cases.category_id', '=', 'service_case_categories.id')
                ->select('service_cases.category_id', 'service_case_categories.name', DB::raw('COUNT(*) as case_count'))
                ->groupBy('service_cases.category_id', 'service_case_categories.name')
                ->orderByDesc('case_count')
                ->limit(9)
                ->get();

            $labels = [];
            $counts = [];

            foreach ($topCategories as $item) {
                $labels[] = $this->truncateLabel($item->name);
                $counts[] = $item->case_count;
            }

            $totalOpenQuery = ServiceCase::query()->open();
            if ($companyId) {
                $totalOpenQuery->where('company_id', $companyId);
            }
            $totalOpen = $totalOpenQuery->count();

            $topCategoriesTotal = array_sum($counts);
            $otherCount = $totalOpen - $topCategoriesTotal;

            if ($otherCount > 0) {
                $labels[] = 'Andere';
                $counts[] = $otherCount;
            }

            return [
                'labels' => $labels,
                'counts' => $counts,
                'total' => $totalOpen,
            ];
        });

        $colors = array_slice($this->categoryColors, 0, count($data['counts']));

        return [
            'datasets' => [
                [
                    'label' => 'Cases',
                    'data' => $data['counts'],
                    'backgroundColor' => $colors,
                    'borderColor' => '#ffffff',
                    'borderWidth' => 2,
                ],
            ],
            'labels' => $data['labels'],
        ];
        } catch (\Throwable $e) {
            Log::error('[CategoryDistributionWidget] getData failed', [
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
        return 'doughnut';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'position' => 'right',
                    'labels' => [
                        'usePointStyle' => true,
                        'padding' => 12,
                        'font' => ['size' => 11],
                    ],
                ],
                'tooltip' => [
                    'callbacks' => [
                        'label' => "function(context) {
                            var total = context.dataset.data.reduce((a, b) => a + b, 0);
                            var percentage = Math.round((context.raw / total) * 100);
                            return context.label + ': ' + context.raw + ' (' + percentage + '%)';
                        }",
                    ],
                ],
            ],
            'cutout' => '60%',
            'maintainAspectRatio' => false,
        ];
    }

    protected function truncateLabel(string $label, int $maxLength = 20): string
    {
        return mb_strlen($label) <= $maxLength ? $label : mb_substr($label, 0, $maxLength - 3) . '...';
    }

    public function getDescription(): ?string
    {
        $companyId = $this->getEffectiveCompanyId();
        $cacheKey = $companyId ? "service_gateway_category_distribution_{$companyId}" : 'service_gateway_category_distribution_all';
        $total = Cache::get($cacheKey)['total'] ?? 0;
        return "{$total} offene Cases";
    }
}
