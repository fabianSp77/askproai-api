<?php

namespace App\Filament\Widgets\Profit\Concerns;

use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;

/**
 * Shared filter handling for Profit Dashboard widgets.
 *
 * Provides:
 * - Role-based access control (super-admin, reseller)
 * - Company filter support (multi-tenant isolation)
 * - Time range filter support
 * - Cache key generation with filter hash
 * - Helper methods for applying filters to queries
 *
 * NOTE: Widgets using this trait must also use InteractsWithPageFilters.
 * The dashboard page MUST override getWidgetData() to pass filters to
 * header/footer widgets.
 *
 * ARCHITECTURE: Polling was removed from all widgets because it caused
 * race conditions with Livewire's reactive updates. The #[On('filtersUpdated')]
 * attribute handles filter changes via events.
 *
 * PROFIT-SPECIFIC: This trait extends the HasDashboardFilters pattern with:
 * - Reseller hierarchy filtering (parent_company_id)
 * - Role-based profit visibility checks
 * - Profit-specific cache key prefixes
 */
trait HasProfitFilters
{
    /**
     * Handle filter update events from the dashboard.
     * Receives new filter values and triggers widget re-render.
     */
    #[On('filtersUpdated')]
    public function handleFiltersUpdated(?array $filters = null): void
    {
        if ($filters !== null) {
            $this->filters = $filters;
        }
    }

    /**
     * Check if current user is Super Admin.
     * Handles multiple role name variants for backwards compatibility.
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
     * Check if current user is Reseller.
     */
    protected function isReseller(): bool
    {
        $user = Auth::user();
        if (!$user) {
            return false;
        }

        return $user->hasRole(['reseller_admin', 'reseller_owner', 'reseller_support']);
    }

    /**
     * Check if user can view profit data.
     * Only Super Admin and Reseller roles can access.
     */
    protected function canViewProfit(): bool
    {
        return $this->isSuperAdmin() || $this->isReseller();
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

        // Super admin without filter sees all companies
        if ($this->isSuperAdmin()) {
            return null;
        }

        // Resellers without filter see their own company context
        // (child companies are handled via applyCompanyFilter)
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
            'year' => 'Dieses Jahr',
            'all' => 'Gesamt',
            default => 'Dieser Monat',
        };
    }

    /**
     * Generate cache key suffix based on current filters and role.
     * Ensures cache is invalidated when filters change.
     */
    protected function getFilterCacheKey(): string
    {
        $companyId = $this->getEffectiveCompanyId();
        $timeRange = $this->getEffectiveTimeRange();
        $roleKey = $this->isSuperAdmin() ? 'super' : ($this->isReseller() ? 'reseller' : 'user');

        $companyPart = $companyId ? "c{$companyId}" : 'all';
        $timePart = $timeRange;

        if ($timeRange === 'custom') {
            $from = $this->getCustomDateFrom() ?? 'null';
            $to = $this->getCustomDateTo() ?? 'null';
            $timePart = "custom_{$from}_{$to}";
        }

        return "{$roleKey}_{$companyPart}_{$timePart}";
    }

    /**
     * Apply company filter to a query builder.
     * Handles super-admin (all), reseller (child companies), and regular users.
     *
     * PROFIT-SPECIFIC: Resellers see only their customers' calls via parent_company_id.
     */
    protected function applyCompanyFilter($query)
    {
        $companyId = $this->getEffectiveCompanyId();
        $user = Auth::user();

        if ($companyId) {
            // Explicit company filter applied
            $query->where('company_id', $companyId);
        } elseif ($this->isReseller() && !$this->isSuperAdmin()) {
            // Reseller without filter sees only their customers' calls
            $query->whereHas('company', function ($q) use ($user) {
                $q->where('parent_company_id', $user->company_id);
            });
        }
        // Super admin without filter sees all companies (no filter applied)

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
    protected function applyFilters($query, string $timeColumn = 'created_at')
    {
        $this->applyCompanyFilter($query);
        $this->applyTimeRangeFilter($query, $timeColumn);
        return $query;
    }

    /**
     * Format cents to EUR currency string.
     */
    protected function formatCurrency(int $cents): string
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
}
