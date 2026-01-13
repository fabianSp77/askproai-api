<?php

namespace App\Filament\Widgets\Profit;

use App\Filament\Widgets\Profit\Concerns\HasProfitFilters;
use App\Models\Call;
use App\Models\Company;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\Widget;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Profit Alerts Widget
 *
 * Shows actionable alerts for profit anomalies:
 * - Negative profit calls (critical)
 * - Low margin calls (<20%)
 * - High performers (>50% margin, positive highlight)
 *
 * PERFORMANCE: Uses database aggregation with HAVING clauses.
 */
class ProfitAlertsWidget extends Widget
{
    use InteractsWithPageFilters;
    use HasProfitFilters;

    protected static string $view = 'filament.widgets.profit.profit-alerts-widget';
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
     * Get all profit alerts.
     */
    public function getAlerts(): Collection
    {
        try {
            $cacheKey = "profit_alerts_{$this->getFilterCacheKey()}";
            $cacheTtl = config('gateway.cache.widget_stats_seconds', 55);

            return Cache::remember($cacheKey, $cacheTtl, function () {
                return $this->calculateAlerts();
            });
        } catch (\Throwable $e) {
            Log::error('[ProfitAlertsWidget] getAlerts failed', [
                'error' => $e->getMessage(),
            ]);

            return collect();
        }
    }

    /**
     * Calculate alerts from profit data.
     */
    protected function calculateAlerts(): Collection
    {
        $alerts = collect();
        $timeRangeStart = $this->getTimeRangeStart();
        $timeRangeEnd = $this->getTimeRangeEnd();

        // Base query with filters
        $baseQuery = Call::query();
        $this->applyCompanyFilter($baseQuery);
        $this->applyTimeRangeFilter($baseQuery);

        // 1. Negative Profit Alerts (Critical)
        $negativeProfitCount = (clone $baseQuery)
            ->where('total_profit', '<', 0)
            ->count();

        if ($negativeProfitCount > 0) {
            $totalNegativeProfit = (clone $baseQuery)
                ->where('total_profit', '<', 0)
                ->sum('total_profit');

            $alerts->push([
                'type' => 'critical',
                'icon' => 'heroicon-o-exclamation-triangle',
                'title' => 'Negative Profit-Anrufe',
                'message' => "{$negativeProfitCount} Anrufe mit negativem Profit",
                'detail' => $this->formatCurrency((int) $totalNegativeProfit),
                'color' => 'danger',
            ]);
        }

        // 2. Low Margin Alerts (<20%)
        $lowMarginCount = (clone $baseQuery)
            ->where('profit_margin_total', '<', 20)
            ->where('profit_margin_total', '>=', 0)
            ->where('total_profit', '>', 0)
            ->count();

        if ($lowMarginCount > 0) {
            $alerts->push([
                'type' => 'warning',
                'icon' => 'heroicon-o-arrow-trending-down',
                'title' => 'Niedrige Margen',
                'message' => "{$lowMarginCount} Anrufe unter 20% Marge",
                'detail' => 'Prüfung empfohlen',
                'color' => 'warning',
            ]);
        }

        // 3. High Performers (>50% margin) - Positive Alert
        $highMarginCount = (clone $baseQuery)
            ->where('profit_margin_total', '>', 50)
            ->where('total_profit', '>', 0)
            ->count();

        if ($highMarginCount > 0) {
            $avgHighMargin = (clone $baseQuery)
                ->where('profit_margin_total', '>', 50)
                ->where('total_profit', '>', 0)
                ->avg('profit_margin_total') ?? 0;

            $alerts->push([
                'type' => 'success',
                'icon' => 'heroicon-o-star',
                'title' => 'Top-Performance',
                'message' => "{$highMarginCount} Anrufe mit >50% Marge",
                'detail' => 'Ø ' . $this->formatPercent((float) $avgHighMargin),
                'color' => 'success',
            ]);
        }

        // 4. Company-specific alerts (Super Admin only)
        if ($this->isSuperAdmin()) {
            $companiesWithNegativeProfit = $this->getCompaniesWithNegativeProfit($timeRangeStart, $timeRangeEnd);

            if ($companiesWithNegativeProfit->isNotEmpty()) {
                $alerts->push([
                    'type' => 'warning',
                    'icon' => 'heroicon-o-building-office',
                    'title' => 'Unternehmen mit Verlusten',
                    'message' => $companiesWithNegativeProfit->count() . ' Unternehmen im Minus',
                    'detail' => $companiesWithNegativeProfit->take(3)->pluck('name')->join(', '),
                    'color' => 'warning',
                ]);
            }
        }

        // 5. No Data Alert
        if ($alerts->isEmpty()) {
            $totalCalls = (clone $baseQuery)->count();

            if ($totalCalls === 0) {
                $alerts->push([
                    'type' => 'info',
                    'icon' => 'heroicon-o-information-circle',
                    'title' => 'Keine Daten',
                    'message' => 'Keine Anrufe im ausgewählten Zeitraum',
                    'detail' => $this->getTimeRangeLabel(),
                    'color' => 'gray',
                ]);
            } else {
                $alerts->push([
                    'type' => 'success',
                    'icon' => 'heroicon-o-check-circle',
                    'title' => 'Alles im grünen Bereich',
                    'message' => 'Keine Auffälligkeiten gefunden',
                    'detail' => "{$totalCalls} Anrufe analysiert",
                    'color' => 'success',
                ]);
            }
        }

        return $alerts;
    }

    /**
     * Get companies with negative total profit.
     */
    protected function getCompaniesWithNegativeProfit($timeRangeStart, $timeRangeEnd): Collection
    {
        $query = Company::query()
            ->withSum(['calls as total_profit' => function ($q) use ($timeRangeStart, $timeRangeEnd) {
                if ($timeRangeStart) {
                    $q->where('created_at', '>=', $timeRangeStart);
                }
                if ($timeRangeEnd) {
                    $q->where('created_at', '<=', $timeRangeEnd);
                }
            }], 'total_profit')
            ->having('calls_sum_total_profit', '<', 0)
            ->orderBy('calls_sum_total_profit')
            ->limit(5);

        // Apply company filter if set
        $companyId = $this->getEffectiveCompanyId();
        if ($companyId) {
            $query->where('id', $companyId);
        }

        return $query->get();
    }

    /**
     * Get widget heading.
     */
    public function getHeading(): string
    {
        return 'Profit-Alerts';
    }

    /**
     * Get time range description.
     */
    public function getDescription(): string
    {
        return $this->getTimeRangeLabel();
    }
}
