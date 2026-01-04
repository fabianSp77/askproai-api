<?php

namespace App\Filament\Widgets\ServiceGateway;

use App\Models\ServiceCase;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Aging Analysis Widget
 *
 * ServiceNow-style aging analysis showing open cases by age buckets.
 * Color-coded from green (fresh) to red (stale) for quick visual assessment.
 * SECURITY: All queries explicitly filtered by company_id for multi-tenancy isolation.
 * FEATURE: Supports company filter from dashboard for super-admins.
 */
class AgingAnalysisWidget extends ChartWidget
{
    use InteractsWithPageFilters;

    protected static ?string $heading = 'Case Aging Analysis';
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
            $cacheKey = $companyId ? "service_gateway_aging_analysis_{$companyId}" : 'service_gateway_aging_analysis_all';

            $data = Cache::remember($cacheKey, config('gateway.cache.widget_stats_seconds'), function () use ($companyId) {
            $now = now();
            $baseQuery = ServiceCase::query()->open();
            if ($companyId) {
                $baseQuery->where('company_id', $companyId);
            }

            $bucket0to4h = (clone $baseQuery)
                ->where('created_at', '>=', $now->copy()->subHours(4))
                ->count();

            $bucket4to24h = (clone $baseQuery)
                ->where('created_at', '<', $now->copy()->subHours(4))
                ->where('created_at', '>=', $now->copy()->subHours(24))
                ->count();

            $bucket1to3d = (clone $baseQuery)
                ->where('created_at', '<', $now->copy()->subHours(24))
                ->where('created_at', '>=', $now->copy()->subDays(3))
                ->count();

            $bucket3dPlus = (clone $baseQuery)
                ->where('created_at', '<', $now->copy()->subDays(3))
                ->count();

            return [
                'buckets' => [$bucket0to4h, $bucket4to24h, $bucket1to3d, $bucket3dPlus],
                'total' => $bucket0to4h + $bucket4to24h + $bucket1to3d + $bucket3dPlus,
            ];
        });

        return [
            'datasets' => [
                [
                    'label' => 'Offene Cases',
                    'data' => $data['buckets'],
                    'backgroundColor' => [
                        '#10B981', // 0-4h - Green (fresh)
                        '#F59E0B', // 4-24h - Amber (same day)
                        '#F97316', // 1-3d - Orange (getting stale)
                        '#EF4444', // 3d+ - Red (critical)
                    ],
                    'borderColor' => [
                        '#059669',
                        '#D97706',
                        '#EA580C',
                        '#DC2626',
                    ],
                    'borderWidth' => 1,
                    'borderRadius' => 4,
                ],
            ],
            'labels' => ['0-4 Std', '4-24 Std', '1-3 Tage', '3+ Tage'],
        ];
        } catch (\Throwable $e) {
            Log::error('[AgingAnalysisWidget] getData failed', [
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
                    'display' => false,
                ],
                'tooltip' => [
                    'callbacks' => [
                        'label' => "function(context) {
                            return context.raw + ' Cases';
                        }",
                    ],
                ],
            ],
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'ticks' => [
                        'stepSize' => 1,
                    ],
                    'title' => [
                        'display' => true,
                        'text' => 'Anzahl Cases',
                    ],
                ],
                'x' => [
                    'title' => [
                        'display' => true,
                        'text' => 'Alter',
                    ],
                ],
            ],
            'maintainAspectRatio' => false,
        ];
    }

    public function getDescription(): ?string
    {
        $companyId = $this->getEffectiveCompanyId();
        $cacheKey = $companyId ? "service_gateway_aging_analysis_{$companyId}" : 'service_gateway_aging_analysis_all';
        $total = Cache::get($cacheKey)['total'] ?? 0;
        return "{$total} offene Cases nach Alter";
    }
}
