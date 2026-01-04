<?php

namespace App\Filament\Widgets\ServiceGateway;

use App\Models\ServiceCase;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Cases by Priority Bar Chart
 *
 * Horizontal bar chart showing open cases by priority.
 * SECURITY: All queries explicitly filtered by company_id for multi-tenancy isolation.
 * FEATURE: Supports company filter from dashboard for super-admins.
 */
class CasesByPriorityChart extends ChartWidget
{
    use InteractsWithPageFilters;

    protected static ?string $heading = 'Offene Cases nach Priorität';
    protected static ?string $pollingInterval = '60s';

    protected static bool $isLazy = true;
    protected static ?string $maxHeight = '300px';

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
            $cacheKey = $companyId ? "service_gateway_priority_chart_{$companyId}" : 'service_gateway_priority_chart_all';

            $data = Cache::remember($cacheKey, config('gateway.cache.widget_stats_seconds'), function () use ($companyId) {
            $baseQuery = ServiceCase::query()->open();
            if ($companyId) {
                $baseQuery->where('company_id', $companyId);
            }

            return [
                (clone $baseQuery)->where('priority', ServiceCase::PRIORITY_CRITICAL)->count(),
                (clone $baseQuery)->where('priority', ServiceCase::PRIORITY_HIGH)->count(),
                (clone $baseQuery)->where('priority', ServiceCase::PRIORITY_NORMAL)->count(),
                (clone $baseQuery)->where('priority', ServiceCase::PRIORITY_LOW)->count(),
            ];
        });

        return [
            'datasets' => [
                [
                    'label' => 'Offene Cases',
                    'data' => $data,
                    'backgroundColor' => [
                        '#DC2626', // Critical - Red
                        '#F59E0B', // High - Amber
                        '#3B82F6', // Normal - Blue
                        '#6B7280', // Low - Gray
                    ],
                    'borderRadius' => 4,
                ],
            ],
            'labels' => ['Kritisch', 'Hoch', 'Normal', 'Niedrig'],
        ];
        } catch (\Throwable $e) {
            Log::error('[CasesByPriorityChart] getData failed', [
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
            'indexAxis' => 'y',
            'plugins' => [
                'legend' => [
                    'display' => false,
                ],
            ],
            'scales' => [
                'x' => [
                    'beginAtZero' => true,
                    'grid' => [
                        'display' => true,
                        'color' => 'rgba(0, 0, 0, 0.05)',
                    ],
                ],
                'y' => [
                    'grid' => [
                        'display' => false,
                    ],
                ],
            ],
            'maintainAspectRatio' => false,
        ];
    }
}
