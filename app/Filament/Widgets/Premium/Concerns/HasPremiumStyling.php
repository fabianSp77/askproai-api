<?php

namespace App\Filament\Widgets\Premium\Concerns;

use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

/**
 * Shared styling and filter handling for Premium Dashboard widgets.
 *
 * Provides:
 * - Role-based access control (super-admin, reseller-admin only)
 * - Company filter support (multi-tenant isolation)
 * - Time range filter support
 * - Premium styling helper methods
 * - Cache key generation with filter hash
 *
 * ARCHITECTURE: Uses InteractsWithPageFilters trait from Filament which
 * automatically synchronizes $this->filters as a reactive prop from parent.
 */
trait HasPremiumStyling
{
    /**
     * Check if current user is Super Admin.
     */
    protected function isSuperAdmin(): bool
    {
        $user = Auth::user();
        if (!$user) {
            return false;
        }

        return $user->hasRole(['super-admin', 'super_admin', 'Super Admin']);
    }

    /**
     * Check if current user is Reseller Admin.
     */
    protected function isResellerAdmin(): bool
    {
        $user = Auth::user();
        if (!$user) {
            return false;
        }

        return $user->hasRole(['reseller_admin', 'reseller_owner']);
    }

    /**
     * Check if user can view Premium Dashboard.
     * Only Super Admin and Reseller Admin roles can access.
     */
    protected function canViewPremiumDashboard(): bool
    {
        return $this->isSuperAdmin() || $this->isResellerAdmin();
    }

    /**
     * Get the effective company ID based on filter or user context.
     */
    protected function getEffectiveCompanyId(): ?int
    {
        $filters = $this->filters ?? [];
        $filteredCompanyId = $filters['company_id'] ?? null;

        if ($filteredCompanyId) {
            return (int) $filteredCompanyId;
        }

        // Super admin without filter sees all companies
        if ($this->isSuperAdmin()) {
            return null;
        }

        // Resellers see their own company
        $user = Auth::user();
        return $user?->company_id;
    }

    /**
     * Get the effective time range from filter.
     */
    protected function getEffectiveTimeRange(): string
    {
        return $this->filters['time_range'] ?? 'month';
    }

    /**
     * Get the start date for the selected time range.
     */
    protected function getTimeRangeStart(): ?Carbon
    {
        $timeRange = $this->getEffectiveTimeRange();

        if ($timeRange === 'custom') {
            $dateFrom = $this->getCustomDateFrom();
            return $dateFrom ? Carbon::parse($dateFrom)->startOfDay() : now()->startOfMonth();
        }

        return match ($timeRange) {
            'today' => now()->startOfDay(),
            'week' => now()->startOfWeek(),
            'month' => now()->startOfMonth(),
            'quarter' => now()->startOfQuarter(),
            'year' => now()->startOfYear(),
            'all' => null,
            default => now()->startOfMonth(),
        };
    }

    /**
     * Get the end date for the selected time range.
     */
    protected function getTimeRangeEnd(): ?Carbon
    {
        $timeRange = $this->getEffectiveTimeRange();

        if ($timeRange === 'custom') {
            $dateTo = $this->getCustomDateTo();
            return $dateTo ? Carbon::parse($dateTo)->endOfDay() : now()->endOfDay();
        }

        return null;
    }

    /**
     * Get custom date_from filter value.
     */
    protected function getCustomDateFrom(): ?string
    {
        return $this->filters['date_from'] ?? null;
    }

    /**
     * Get custom date_to filter value.
     */
    protected function getCustomDateTo(): ?string
    {
        return $this->filters['date_to'] ?? null;
    }

    /**
     * Generate cache key suffix based on current filters.
     */
    protected function getFilterCacheKey(): string
    {
        $companyId = $this->getEffectiveCompanyId();
        $timeRange = $this->getEffectiveTimeRange();
        $roleKey = $this->isSuperAdmin() ? 'super' : 'reseller';

        $companyPart = $companyId ? "c{$companyId}" : 'all';
        $timePart = $timeRange;

        if ($timeRange === 'custom') {
            $from = $this->getCustomDateFrom() ?? 'null';
            $to = $this->getCustomDateTo() ?? 'null';
            $timePart = "custom_{$from}_{$to}";
        }

        return "premium_{$roleKey}_{$companyPart}_{$timePart}";
    }

    /**
     * Apply company filter to a query builder.
     */
    protected function applyCompanyFilter($query, string $column = 'company_id')
    {
        $companyId = $this->getEffectiveCompanyId();
        $user = Auth::user();

        if ($companyId) {
            $query->where($column, $companyId);
        } elseif ($this->isResellerAdmin() && !$this->isSuperAdmin()) {
            // Reseller sees only their customers' data
            $query->whereHas('company', function ($q) use ($user) {
                $q->where('parent_company_id', $user->company_id);
            });
        }
        // Super admin without filter sees all companies

        return $query;
    }

    /**
     * Apply time range filter to a query builder.
     */
    protected function applyTimeRangeFilter($query, string $column = 'created_at')
    {
        $start = $this->getTimeRangeStart();
        if ($start) {
            $query->where($column, '>=', $start);
        }

        $end = $this->getTimeRangeEnd();
        if ($end) {
            $query->where($column, '<=', $end);
        }

        return $query;
    }

    /**
     * Apply both company and time range filters.
     */
    protected function applyFilters($query, string $timeColumn = 'created_at', string $companyColumn = 'company_id')
    {
        $this->applyCompanyFilter($query, $companyColumn);
        $this->applyTimeRangeFilter($query, $timeColumn);
        return $query;
    }

    // =========================================================================
    // Styling Helpers
    // =========================================================================

    /**
     * Get premium card CSS classes.
     */
    protected function getPremiumCardClasses(): string
    {
        return 'premium-card';
    }

    /**
     * Get change badge classes based on value (positive/negative).
     */
    protected function getChangeBadgeClasses(float $change): string
    {
        if ($change > 0) {
            return 'premium-badge premium-badge-success';
        } elseif ($change < 0) {
            return 'premium-badge premium-badge-error';
        }
        return 'premium-badge';
    }

    /**
     * Get change indicator classes.
     */
    protected function getChangeClasses(float $change): string
    {
        if ($change > 0) {
            return 'premium-change premium-change-positive';
        } elseif ($change < 0) {
            return 'premium-change premium-change-negative';
        }
        return 'premium-change premium-change-neutral';
    }

    /**
     * Format change value with arrow.
     */
    protected function formatChange(float $change): string
    {
        $arrow = $change > 0 ? '↑' : ($change < 0 ? '↓' : '→');
        return $arrow . ' ' . number_format(abs($change), 1, ',', '.') . '%';
    }

    /**
     * Format currency (EUR).
     */
    protected function formatCurrency(int|float $cents): string
    {
        return number_format($cents / 100, 2, ',', '.') . ' €';
    }

    /**
     * Format percentage value.
     */
    protected function formatPercent(float $value): string
    {
        return number_format($value, 1, ',', '.') . '%';
    }

    /**
     * Get chart color palette for premium theme.
     */
    protected function getPremiumChartColors(): array
    {
        return [
            'blue' => '#3B82F6',
            'blueLight' => 'rgba(59, 130, 246, 0.3)',
            'green' => '#22C55E',
            'purple' => '#8B5CF6',
            'yellow' => '#FBBF24',
            'orange' => '#F97316',
            'cyan' => '#06B6D4',
            'red' => '#EF4444',
        ];
    }

    /**
     * Get premium chart options (overrides default Filament chart styling).
     */
    protected function getPremiumChartOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'display' => false,
                ],
            ],
            'scales' => [
                'x' => [
                    'grid' => [
                        'display' => false,
                        'drawBorder' => false,
                    ],
                    'ticks' => [
                        'color' => '#A1A1AA',
                        'font' => [
                            'size' => 11,
                        ],
                    ],
                ],
                'y' => [
                    'grid' => [
                        'color' => 'rgba(255, 255, 255, 0.05)',
                        'drawBorder' => false,
                    ],
                    'ticks' => [
                        'color' => '#A1A1AA',
                        'font' => [
                            'size' => 11,
                        ],
                    ],
                ],
            ],
            'maintainAspectRatio' => false,
        ];
    }
}
