<?php

namespace App\Filament\Widgets\ServiceGateway;

use App\Models\ServiceCase;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Cases by Status Pie Chart
 *
 * Donut chart showing distribution of cases by status.
 * SECURITY: All queries explicitly filtered by company_id for multi-tenancy isolation.
 * FEATURE: Supports company filter from dashboard for super-admins.
 */
class CasesByStatusChart extends ChartWidget
{
    use InteractsWithPageFilters;

    protected static ?string $heading = 'Cases nach Status';
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
            $cacheKey = $companyId ? "service_gateway_status_chart_{$companyId}" : 'service_gateway_status_chart_all';

            $data = Cache::remember($cacheKey, config('gateway.cache.widget_stats_seconds'), function () use ($companyId) {
            $query = ServiceCase::query();
            if ($companyId) {
                $query->where('company_id', $companyId);
            }

            return [
                (clone $query)->where('status', ServiceCase::STATUS_NEW)->count(),
                (clone $query)->where('status', ServiceCase::STATUS_OPEN)->count(),
                (clone $query)->where('status', ServiceCase::STATUS_PENDING)->count(),
                (clone $query)->where('status', ServiceCase::STATUS_RESOLVED)->count(),
                (clone $query)->where('status', ServiceCase::STATUS_CLOSED)->count(),
            ];
        });

        return [
            'datasets' => [
                [
                    'label' => 'Cases',
                    'data' => $data,
                    'backgroundColor' => [
                        '#EF4444', // New - Red
                        '#3B82F6', // Open - Blue
                        '#8B5CF6', // Pending - Purple
                        '#10B981', // Resolved - Green
                        '#6B7280', // Closed - Gray
                    ],
                    'borderColor' => '#ffffff',
                    'borderWidth' => 2,
                ],
            ],
            'labels' => ['Neu', 'Offen', 'Wartend', 'Gelöst', 'Geschlossen'],
        ];
        } catch (\Throwable $e) {
            Log::error('[CasesByStatusChart] getData failed', [
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
                    'position' => 'bottom',
                    'labels' => [
                        'usePointStyle' => true,
                        'padding' => 15,
                    ],
                ],
            ],
            'maintainAspectRatio' => false,
        ];
    }
}
