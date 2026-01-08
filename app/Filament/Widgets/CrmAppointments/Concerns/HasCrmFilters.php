<?php

namespace App\Filament\Widgets\CrmAppointments\Concerns;

use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;

/**
 * Shared filter handling for CRM Appointments Dashboard widgets.
 *
 * Provides:
 * - Company filter support (multi-tenant isolation)
 * - Agent filter support (filter by Retell agent)
 * - Time range filter support
 * - Cache key generation with filter hash
 * - Helper methods for applying filters to Call queries
 *
 * NOTE: Widgets using this trait must also use InteractsWithPageFilters.
 * The dashboard page MUST override getWidgetData() to pass filters to
 * header/footer widgets.
 *
 * ARCHITECTURE: No polling - uses event-driven updates via 'crmFiltersUpdated'.
 */
trait HasCrmFilters
{
    /**
     * Handle filter update events from the dashboard.
     * Receives new filter values and triggers widget re-render.
     */
    #[On('crmFiltersUpdated')]
    public function handleFiltersUpdated(?array $filters = null): void
    {
        if ($filters !== null) {
            $this->filters = $filters;
        }
    }

    /**
     * Get the effective company ID based on filter or user context.
     * Returns null if "All Companies" is selected (super-admin view).
     */
    protected function getEffectiveCompanyId(): ?int
    {
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
     * Get the effective agent ID from filter.
     * Returns null if "All Agents" is selected.
     */
    protected function getEffectiveAgentId(): ?string
    {
        $filters = $this->filters ?? [];
        return $filters['agent_id'] ?? null;
    }

    /**
     * Get the effective time range from filter.
     * Returns: 'today', 'week', 'month', 'quarter', 'all'
     */
    protected function getEffectiveTimeRange(): string
    {
        $filters = $this->filters ?? [];
        return $filters['time_range'] ?? 'today';
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
            'all' => null,
            default => now()->startOfDay(),
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
            default => 'Heute',
        };
    }

    /**
     * Generate cache key suffix based on current filters.
     * Ensures cache is invalidated when filters change.
     */
    protected function getFilterCacheKey(): string
    {
        $companyId = $this->getEffectiveCompanyId();
        $agentId = $this->getEffectiveAgentId();
        $timeRange = $this->getEffectiveTimeRange();

        $companyPart = $companyId ? "c{$companyId}" : 'all';
        $agentPart = $agentId ? "a{$agentId}" : 'all';
        $timePart = $timeRange;

        // Include custom dates in cache key for proper invalidation
        if ($timeRange === 'custom') {
            $from = $this->getCustomDateFrom() ?? 'null';
            $to = $this->getCustomDateTo() ?? 'null';
            $timePart = "custom_{$from}_{$to}";
        }

        return "{$companyPart}_{$agentPart}_{$timePart}";
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
     * Apply agent filter to a query builder.
     * Uses retell_agent_id column on calls table.
     */
    protected function applyAgentFilter($query)
    {
        $agentId = $this->getEffectiveAgentId();
        if ($agentId) {
            $query->where('retell_agent_id', $agentId);
        }
        return $query;
    }

    /**
     * Apply all CRM filters (company, agent, time range).
     */
    protected function applyFilters($query, string $timeColumn = 'created_at')
    {
        $this->applyCompanyFilter($query);
        $this->applyAgentFilter($query);
        $this->applyTimeRangeFilter($query, $timeColumn);
        return $query;
    }

    /**
     * Get comparison date for trend calculations.
     * Returns the equivalent period before current range.
     */
    protected function getComparisonStart(): ?Carbon
    {
        return match ($this->getEffectiveTimeRange()) {
            'today' => now()->subDay()->startOfDay(),
            'week' => now()->subWeek()->startOfWeek(),
            'month' => now()->subMonth()->startOfMonth(),
            'quarter' => now()->subQuarter()->startOfQuarter(),
            'all' => null,
            default => now()->subDay()->startOfDay(),
        };
    }

    /**
     * Get comparison end date for trend calculations.
     */
    protected function getComparisonEnd(): ?Carbon
    {
        return match ($this->getEffectiveTimeRange()) {
            'today' => now()->subDay()->endOfDay(),
            'week' => now()->subWeek()->endOfWeek(),
            'month' => now()->subMonth()->endOfMonth(),
            'quarter' => now()->subQuarter()->endOfQuarter(),
            'all' => null,
            default => now()->subDay()->endOfDay(),
        };
    }
}
