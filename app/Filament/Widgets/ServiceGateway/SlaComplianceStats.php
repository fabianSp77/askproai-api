<?php

namespace App\Filament\Widgets\ServiceGateway;

use App\Models\ServiceCase;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * SLA Compliance Statistics Widget
 *
 * Shows key SLA metrics: open cases, overdue, compliance rate, avg resolution time.
 * SECURITY: All queries explicitly filtered by company_id for multi-tenancy isolation.
 * FEATURE: Supports company filter from dashboard for super-admins.
 */
class SlaComplianceStats extends BaseWidget
{
    use InteractsWithPageFilters;

    protected static ?string $pollingInterval = '30s';

    /**
     * Enable lazy loading for dashboard stability.
     * Prevents initialization errors during Livewire hydration.
     */
    protected static bool $isLazy = true;

    /**
     * Get the effective company ID based on filter or user context.
     * Returns null if "All Companies" is selected (super-admin view).
     */
    protected function getEffectiveCompanyId(): ?int
    {
        // Check if filter is set from dashboard
        $filteredCompanyId = $this->filters['company_id'] ?? null;
        if ($filteredCompanyId) {
            return (int) $filteredCompanyId;
        }

        // If no filter set and user is super-admin, show all companies (null)
        $user = Auth::user();
        if ($user && $user->hasAnyRole(['super_admin', 'super-admin', 'Admin', 'reseller_admin'])) {
            return null; // All companies
        }

        // Regular users see only their company
        return $user?->company_id;
    }

    protected function getStats(): array
    {
        try {
            $companyId = $this->getEffectiveCompanyId();
            $cacheKey = $companyId ? "service_gateway_sla_stats_{$companyId}" : 'service_gateway_sla_stats_all';

            $stats = Cache::remember($cacheKey, config('gateway.cache.widget_stats_seconds'), function () use ($companyId) {
            $baseQuery = ServiceCase::query();
            if ($companyId) {
                $baseQuery->where('company_id', $companyId);
            }

            $openCases = (clone $baseQuery)->open()->count();
            $overdueResponse = (clone $baseQuery)->open()
                ->whereNotNull('sla_response_due_at')
                ->where('sla_response_due_at', '<', now())
                ->count();
            $overdueResolution = (clone $baseQuery)->open()
                ->whereNotNull('sla_resolution_due_at')
                ->where('sla_resolution_due_at', '<', now())
                ->count();

            // Calculate compliance rate (last 30 days)
            $resolvedLast30Days = (clone $baseQuery)
                ->where('status', ServiceCase::STATUS_RESOLVED)
                ->where('updated_at', '>=', now()->subDays(30))
                ->count();
            // FIX: Cases without SLA (NULL) count as "within SLA" (pass-through companies)
            $resolvedWithinSla = (clone $baseQuery)
                ->where('status', ServiceCase::STATUS_RESOLVED)
                ->where('updated_at', '>=', now()->subDays(30))
                ->where(function ($q) {
                    $q->whereNull('sla_resolution_due_at')  // No SLA defined = always OK
                      ->orWhereColumn('updated_at', '<=', 'sla_resolution_due_at');
                })
                ->count();
            $complianceRate = $resolvedLast30Days > 0
                ? round(($resolvedWithinSla / $resolvedLast30Days) * 100, 1)
                : 100;

            // Average resolution time (last 30 days, in hours)
            $avgResolutionMinutes = (clone $baseQuery)
                ->where('status', ServiceCase::STATUS_RESOLVED)
                ->where('updated_at', '>=', now()->subDays(30))
                ->selectRaw('AVG(TIMESTAMPDIFF(MINUTE, created_at, updated_at)) as avg_minutes')
                ->value('avg_minutes');
            $avgResolutionHours = $avgResolutionMinutes ? round($avgResolutionMinutes / 60, 1) : 0;

            // Today's cases
            $todayCases = (clone $baseQuery)->whereDate('created_at', today())->count();
            $yesterdayCases = (clone $baseQuery)->whereDate('created_at', today()->subDay())->count();
            $caseTrend = $yesterdayCases > 0
                ? round((($todayCases - $yesterdayCases) / $yesterdayCases) * 100)
                : 0;

            // SLA Warning: Cases approaching SLA breach within 2 hours
            $slaWarning = (clone $baseQuery)->open()
                ->where(function ($q) {
                    $twoHoursFromNow = now()->addHours(2);
                    $q->where(function ($q2) use ($twoHoursFromNow) {
                        $q2->whereNotNull('sla_response_due_at')
                           ->where('sla_response_due_at', '>', now())
                           ->where('sla_response_due_at', '<=', $twoHoursFromNow);
                    })->orWhere(function ($q2) use ($twoHoursFromNow) {
                        $q2->whereNotNull('sla_resolution_due_at')
                           ->where('sla_resolution_due_at', '>', now())
                           ->where('sla_resolution_due_at', '<=', $twoHoursFromNow);
                    });
                })
                ->count();

            // Unassigned cases
            $unassignedCases = (clone $baseQuery)->open()
                ->whereNull('assigned_to')
                ->whereNull('assigned_group_id')
                ->count();

            return compact(
                'openCases',
                'overdueResponse',
                'overdueResolution',
                'complianceRate',
                'avgResolutionHours',
                'todayCases',
                'caseTrend',
                'slaWarning',
                'unassignedCases'
            );
        });

        // Build description for open cases with warnings
        $openDescription = [];
        if ($stats['overdueResolution'] > 0) {
            $openDescription[] = "{$stats['overdueResolution']} überfällig";
        }
        if ($stats['slaWarning'] > 0) {
            $openDescription[] = "{$stats['slaWarning']} dringend";
        }
        if ($stats['unassignedCases'] > 0) {
            $openDescription[] = "{$stats['unassignedCases']} nicht zugewiesen";
        }
        $openDescText = !empty($openDescription) ? implode(' · ', $openDescription) : 'Alle im SLA';
        $openColor = $stats['overdueResolution'] > 0 ? 'danger' : ($stats['slaWarning'] > 0 || $stats['unassignedCases'] > 0 ? 'warning' : 'success');

        return [
            Stat::make('Offene Cases', $stats['openCases'])
                ->description($openDescText)
                ->descriptionIcon($stats['overdueResolution'] > 0 ? 'heroicon-o-exclamation-triangle' : ($stats['slaWarning'] > 0 ? 'heroicon-o-clock' : 'heroicon-o-check-circle'))
                ->color($openColor)
                ->chart($this->getOpenCasesTrend()),

            Stat::make('SLA Compliance', "{$stats['complianceRate']}%")
                ->description('Letzte 30 Tage')
                ->descriptionIcon('heroicon-o-shield-check')
                ->color($stats['complianceRate'] >= 90 ? 'success' : ($stats['complianceRate'] >= 70 ? 'warning' : 'danger'))
                ->chart($this->getComplianceTrend()),

            Stat::make('Avg. Bearbeitungszeit', "{$stats['avgResolutionHours']}h")
                ->description('Letzte 30 Tage')
                ->descriptionIcon('heroicon-o-clock')
                ->color($stats['avgResolutionHours'] <= 24 ? 'success' : ($stats['avgResolutionHours'] <= 48 ? 'warning' : 'danger')),

            Stat::make('Heute erstellt', $stats['todayCases'])
                ->description($stats['caseTrend'] >= 0 ? "+{$stats['caseTrend']}% vs gestern" : "{$stats['caseTrend']}% vs gestern")
                ->descriptionIcon($stats['caseTrend'] >= 0 ? 'heroicon-o-arrow-trending-up' : 'heroicon-o-arrow-trending-down')
                ->color($stats['caseTrend'] <= 0 ? 'success' : 'warning'),
        ];
        } catch (\Throwable $e) {
            Log::error('[SlaComplianceStats] Failed to get stats', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->getEmptyStats();
        }
    }

    protected function getOpenCasesTrend(): array
    {
        try {
            $companyId = $this->getEffectiveCompanyId();
            $cacheKey = $companyId ? "service_gateway_open_trend_{$companyId}" : 'service_gateway_open_trend_all';

            return Cache::remember($cacheKey, config('gateway.cache.widget_trends_seconds'), function () use ($companyId) {
                $trend = [];
                for ($i = 6; $i >= 0; $i--) {
                    $date = now()->subDays($i);
                    $query = ServiceCase::query();
                    if ($companyId) {
                        $query->where('company_id', $companyId);
                    }
                    $trend[] = $query->whereDate('created_at', $date)->count();
                }
                return $trend;
            });
        } catch (\Throwable $e) {
            Log::warning('[SlaComplianceStats] Open cases trend failed', ['error' => $e->getMessage()]);
            return [0, 0, 0, 0, 0, 0, 0];
        }
    }

    protected function getComplianceTrend(): array
    {
        try {
            $companyId = $this->getEffectiveCompanyId();
            $cacheKey = $companyId ? "service_gateway_compliance_trend_{$companyId}" : 'service_gateway_compliance_trend_all';

            return Cache::remember($cacheKey, config('gateway.cache.widget_trends_seconds'), function () use ($companyId) {
                $trend = [];
                for ($i = 6; $i >= 0; $i--) {
                    $date = now()->subDays($i);
                    $query = ServiceCase::query();
                    if ($companyId) {
                        $query->where('company_id', $companyId);
                    }
                    $resolved = (clone $query)
                        ->where('status', ServiceCase::STATUS_RESOLVED)
                        ->whereDate('updated_at', $date)
                        ->count();
                    // FIX: Cases without SLA count as "within SLA"
                    $withinSla = (clone $query)
                        ->where('status', ServiceCase::STATUS_RESOLVED)
                        ->whereDate('updated_at', $date)
                        ->where(function ($q) {
                            $q->whereNull('sla_resolution_due_at')
                              ->orWhereColumn('updated_at', '<=', 'sla_resolution_due_at');
                        })
                        ->count();
                    $trend[] = $resolved > 0 ? round(($withinSla / $resolved) * 100) : 100;
                }
                return $trend;
            });
        } catch (\Throwable $e) {
            Log::warning('[SlaComplianceStats] Compliance trend failed', ['error' => $e->getMessage()]);
            return [100, 100, 100, 100, 100, 100, 100];
        }
    }

    protected function getEmptyStats(): array
    {
        return [
            Stat::make('Offene Cases', 0)->color('gray'),
            Stat::make('SLA Compliance', '—')->color('gray'),
            Stat::make('Avg. Bearbeitungszeit', '—')->color('gray'),
            Stat::make('Heute erstellt', 0)->color('gray'),
        ];
    }
}
