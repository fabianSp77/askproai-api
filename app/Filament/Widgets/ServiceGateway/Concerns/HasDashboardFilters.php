<?php

namespace App\Filament\Widgets\ServiceGateway\Concerns;

use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Livewire\Attributes\On;

/**
 * Shared filter handling for Service Gateway Dashboard widgets.
 *
 * Provides:
 * - Company filter support (multi-tenant isolation)
 * - Time range filter support
 * - Cache key generation with filter hash
 * - Helper methods for applying filters to queries
 * - Event handler for filter updates (cache invalidation)
 *
 * NOTE: Widgets using this trait must also use InteractsWithPageFilters.
 * The dashboard page MUST override getWidgetData() to pass filters to
 * header/footer widgets.
 *
 * ARCHITECTURE: Polling was removed from all widgets because it caused
 * race conditions with Livewire's reactive updates. The #[Reactive] attribute
 * on $filters (from InteractsWithPageFilters) handles filter changes automatically.
 */
trait HasDashboardFilters
{
    /**
     * Handle filter update events from the dashboard.
     * Receives new filter values and triggers widget re-render.
     *
     * CRITICAL: The filters are passed as event payload because #[Reactive]
     * only works on initial mount, not on live updates. Without this,
     * widgets would keep using stale filter values from their initial render.
     */
    #[On('filtersUpdated')]
    public function handleFiltersUpdated(?array $filters = null): void
    {
        // Update widget's filter state with new values from dashboard
        if ($filters !== null) {
            $this->filters = $filters;
        }

        // Livewire will automatically re-render after this method returns,
        // which will call getData()/getStats() with the new filter values.
        // The cache key includes filter values, so new data will be fetched.
    }

    /**
     * Get the effective company ID based on filter or user context.
     * Returns null if "All Companies" is selected (super-admin view).
     */
    protected function getEffectiveCompanyId(): ?int
    {
        // Defensive null-coalescing for filter array
        $filters = $this->filters ?? [];
        $filteredCompanyId = $filters['company_id'] ?? null;

        if ($filteredCompanyId) {
            return (int) $filteredCompanyId;
        }

        $user = Auth::user();
        if ($user && $user->hasAnyRole(['super_admin', 'super-admin', 'Admin', 'reseller_admin'])) {
            return null; // All companies
        }

        return $user?->company_id;
    }

    /**
     * Get the effective time range from filter.
     * Returns: 'today', 'week', 'month', 'quarter', 'all'
     */
    protected function getEffectiveTimeRange(): string
    {
        return $this->filters['time_range'] ?? 'month';
    }

    /**
     * Get the start date for the selected time range.
     * Handles both preset ranges and custom date selections.
     */
    protected function getTimeRangeStart(): ?Carbon
    {
        $timeRange = $this->getEffectiveTimeRange();

        // Handle custom date range
        if ($timeRange === 'custom') {
            $dateFrom = $this->getCustomDateFrom();
            return $dateFrom ? Carbon::parse($dateFrom)->startOfDay() : now()->startOfMonth();
        }

        return match ($timeRange) {
            'today' => now()->startOfDay(),
            'week' => now()->startOfWeek(),
            'month' => now()->startOfMonth(),
            'quarter' => now()->startOfQuarter(),
            'all' => null, // No time restriction
            default => now()->startOfMonth(),
        };
    }

    /**
     * Get the end date for the selected time range.
     * Only relevant for custom date ranges.
     */
    protected function getTimeRangeEnd(): ?Carbon
    {
        $timeRange = $this->getEffectiveTimeRange();

        // Only custom range has an explicit end date
        if ($timeRange === 'custom') {
            $dateTo = $this->getCustomDateTo();
            return $dateTo ? Carbon::parse($dateTo)->endOfDay() : now()->endOfDay();
        }

        // Preset ranges always end at "now"
        return null;
    }

    /**
     * Get custom date_from filter value.
     */
    protected function getCustomDateFrom(): ?string
    {
        $filters = $this->filters ?? [];
        return $filters['date_from'] ?? null;
    }

    /**
     * Get custom date_to filter value.
     */
    protected function getCustomDateTo(): ?string
    {
        $filters = $this->filters ?? [];
        return $filters['date_to'] ?? null;
    }

    /**
     * Get human-readable label for current time range.
     */
    protected function getTimeRangeLabel(): string
    {
        $timeRange = $this->getEffectiveTimeRange();

        if ($timeRange === 'custom') {
            $from = $this->getCustomDateFrom();
            $to = $this->getCustomDateTo();
            if ($from && $to) {
                return Carbon::parse($from)->format('d.m.') . ' - ' . Carbon::parse($to)->format('d.m.Y');
            }
            return 'Benutzerdefiniert';
        }

        return match ($timeRange) {
            'today' => 'Heute',
            'week' => 'Diese Woche',
            'month' => 'Dieser Monat',
            'quarter' => 'Dieses Quartal',
            'all' => 'Gesamt',
            default => 'Dieser Monat',
        };
    }

    /**
     * Generate cache key suffix based on current filters.
     * Ensures cache is invalidated when filters change.
     */
    protected function getFilterCacheKey(): string
    {
        $companyId = $this->getEffectiveCompanyId();
        $timeRange = $this->getEffectiveTimeRange();

        $companyPart = $companyId ? "c{$companyId}" : 'all';
        $timePart = $timeRange;

        // Include custom dates in cache key for proper invalidation
        if ($timeRange === 'custom') {
            $from = $this->getCustomDateFrom() ?? 'null';
            $to = $this->getCustomDateTo() ?? 'null';
            $timePart = "custom_{$from}_{$to}";
        }

        return "{$companyPart}_{$timePart}";
    }

    /**
     * Apply time range filter to a query builder.
     * Handles both start and end dates for custom ranges.
     */
    protected function applyTimeRangeFilter($query, string $column = 'created_at')
    {
        $start = $this->getTimeRangeStart();
        if ($start) {
            $query->where($column, '>=', $start);
        }

        // Apply end date filter for custom ranges
        $end = $this->getTimeRangeEnd();
        if ($end) {
            $query->where($column, '<=', $end);
        }

        return $query;
    }

    /**
     * Apply company filter to a query builder.
     */
    protected function applyCompanyFilter($query)
    {
        $companyId = $this->getEffectiveCompanyId();
        if ($companyId) {
            $query->where('company_id', $companyId);
        }
        return $query;
    }

    /**
     * Apply both company and time range filters.
     */
    protected function applyFilters($query, string $timeColumn = 'created_at')
    {
        $this->applyCompanyFilter($query);
        $this->applyTimeRangeFilter($query, $timeColumn);
        return $query;
    }
}
