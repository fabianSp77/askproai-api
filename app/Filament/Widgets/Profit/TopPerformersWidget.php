<?php

namespace App\Filament\Widgets\Profit;

use App\Filament\Widgets\Profit\Concerns\HasProfitFilters;
use App\Models\Company;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\Widget;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Top Performers Widget
 *
 * Shows top 10 companies by profit (super-admin) or top 5 customers (reseller).
 * Uses custom blade view instead of TableWidget for Livewire compatibility.
 *
 * PERFORMANCE: Uses withSum() for database-level aggregation.
 */
class TopPerformersWidget extends Widget
{
    use InteractsWithPageFilters;
    use HasProfitFilters;

    protected static string $view = 'filament.widgets.profit.top-performers-widget';
    protected static bool $isLazy = true;

    /**
     * Half width on larger screens.
     */
    protected int|string|array $columnSpan = [
        'default' => 'full',
        'md' => 1,
        'xl' => 1,
    ];

    /**
     * Get top performing companies/customers.
     */
    public function getPerformers(): Collection
    {
        try {
            $cacheKey = "profit_top_performers_{$this->getFilterCacheKey()}";
            $cacheTtl = config('gateway.cache.widget_stats_seconds', 55);

            return Cache::remember($cacheKey, $cacheTtl, function () {
                return $this->calculateTopPerformers();
            });
        } catch (\Throwable $e) {
            Log::error('[TopPerformersWidget] getPerformers failed', [
                'error' => $e->getMessage(),
            ]);

            return collect();
        }
    }

    /**
     * Calculate top performers using withSum aggregation.
     */
    protected function calculateTopPerformers(): Collection
    {
        $user = Auth::user();
        $timeRangeStart = $this->getTimeRangeStart();
        $timeRangeEnd = $this->getTimeRangeEnd();

        if ($this->isSuperAdmin()) {
            // Super-admin: Top 10 companies by total profit
            $query = Company::query()
                ->withSum(['calls as total_profit' => function ($q) use ($timeRangeStart, $timeRangeEnd) {
                    if ($timeRangeStart) {
                        $q->where('created_at', '>=', $timeRangeStart);
                    }
                    if ($timeRangeEnd) {
                        $q->where('created_at', '<=', $timeRangeEnd);
                    }
                }], 'total_profit')
                ->having('calls_sum_total_profit', '>', 0)
                ->orderByDesc('calls_sum_total_profit')
                ->limit(10);

            // Apply company filter if set
            $companyId = $this->getEffectiveCompanyId();
            if ($companyId) {
                $query->where('id', $companyId);
            }

            return $query->get()->map(function ($company) {
                return [
                    'name' => $company->name,
                    'profit' => (int) ($company->calls_sum_total_profit ?? 0),
                    'type' => $company->company_type ?? 'customer',
                ];
            });
        } elseif ($this->isReseller()) {
            // Reseller: Top 5 customers by reseller profit
            return Company::query()
                ->where('parent_company_id', $user->company_id)
                ->withSum(['calls as reseller_profit' => function ($q) use ($timeRangeStart, $timeRangeEnd) {
                    if ($timeRangeStart) {
                        $q->where('created_at', '>=', $timeRangeStart);
                    }
                    if ($timeRangeEnd) {
                        $q->where('created_at', '<=', $timeRangeEnd);
                    }
                }], 'reseller_profit')
                ->having('calls_sum_reseller_profit', '>', 0)
                ->orderByDesc('calls_sum_reseller_profit')
                ->limit(5)
                ->get()
                ->map(function ($company) {
                    return [
                        'name' => $company->name,
                        'profit' => (int) ($company->calls_sum_reseller_profit ?? 0),
                        'type' => 'customer',
                    ];
                });
        }

        return collect();
    }

    /**
     * Get widget heading based on user role.
     */
    public function getHeading(): string
    {
        return $this->isSuperAdmin()
            ? 'Top 10 Profitabelste Unternehmen'
            : 'Top 5 Profitabelste Kunden';
    }

    /**
     * Get time range label for description.
     */
    public function getDescription(): string
    {
        return $this->getTimeRangeLabel();
    }
}
