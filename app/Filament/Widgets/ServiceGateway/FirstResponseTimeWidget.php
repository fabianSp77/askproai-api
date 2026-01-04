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
 * First Response Time (FRT) Widget
 *
 * ServiceNow-style FRT metrics showing:
 * - Average first response time
 * - FRT SLA compliance rate
 * - Cases awaiting first response
 * SECURITY: All queries explicitly filtered by company_id for multi-tenancy isolation.
 * FEATURE: Supports company filter from dashboard for super-admins.
 */
class FirstResponseTimeWidget extends BaseWidget
{
    use InteractsWithPageFilters;

    protected static ?string $pollingInterval = '60s';

    protected static bool $isLazy = true;

    /**
     * Get the effective company ID based on filter or user context.
     */
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

    protected function getStats(): array
    {
        try {
            $companyId = $this->getEffectiveCompanyId();
            $cacheKey = $companyId ? "service_gateway_frt_stats_{$companyId}" : 'service_gateway_frt_stats_all';

            $stats = Cache::remember($cacheKey, config('gateway.cache.widget_stats_seconds'), function () use ($companyId) {
            $baseQuery = ServiceCase::query();
            if ($companyId) {
                $baseQuery->where('company_id', $companyId);
            }

            // Cases awaiting first response (no sla_response_met_at)
            $awaitingResponse = (clone $baseQuery)->open()
                ->whereNull('sla_response_met_at')
                ->count();

            // Cases overdue for first response
            $overdueResponse = (clone $baseQuery)->open()
                ->whereNull('sla_response_met_at')
                ->whereNotNull('sla_response_due_at')
                ->where('sla_response_due_at', '<', now())
                ->count();

            // Average first response time (last 30 days, in minutes)
            $avgFrtMinutes = (clone $baseQuery)
                ->whereNotNull('sla_response_met_at')
                ->where('sla_response_met_at', '>=', now()->subDays(30))
                ->selectRaw('AVG(TIMESTAMPDIFF(MINUTE, created_at, sla_response_met_at)) as avg_minutes')
                ->value('avg_minutes');
            $avgFrtMinutes = $avgFrtMinutes ? round($avgFrtMinutes) : 0;

            // FRT SLA compliance (last 30 days)
            $respondedLast30Days = (clone $baseQuery)
                ->whereNotNull('sla_response_met_at')
                ->whereNotNull('sla_response_due_at')
                ->where('sla_response_met_at', '>=', now()->subDays(30))
                ->count();

            $respondedWithinSla = (clone $baseQuery)
                ->whereNotNull('sla_response_met_at')
                ->whereNotNull('sla_response_due_at')
                ->where('sla_response_met_at', '>=', now()->subDays(30))
                ->whereColumn('sla_response_met_at', '<=', 'sla_response_due_at')
                ->count();

            $frtCompliance = $respondedLast30Days > 0
                ? round(($respondedWithinSla / $respondedLast30Days) * 100, 1)
                : 100;

            // Format time nicely
            if ($avgFrtMinutes < 60) {
                $avgFrtFormatted = "{$avgFrtMinutes} Min";
            } elseif ($avgFrtMinutes < 1440) {
                $hours = round($avgFrtMinutes / 60, 1);
                $avgFrtFormatted = "{$hours}h";
            } else {
                $days = round($avgFrtMinutes / 1440, 1);
                $avgFrtFormatted = "{$days}d";
            }

            return compact(
                'awaitingResponse',
                'overdueResponse',
                'avgFrtMinutes',
                'avgFrtFormatted',
                'frtCompliance',
                'respondedLast30Days'
            );
        });

        return [
            Stat::make('Warten auf Antwort', $stats['awaitingResponse'])
                ->description($stats['overdueResponse'] > 0
                    ? "{$stats['overdueResponse']} SLA überschritten"
                    : 'Alle im SLA')
                ->descriptionIcon($stats['overdueResponse'] > 0
                    ? 'heroicon-o-exclamation-triangle'
                    : 'heroicon-o-check-circle')
                ->color($stats['overdueResponse'] > 0 ? 'danger' : 'success')
                ->chart($this->getAwaitingTrend()),

            Stat::make('Avg. Erstantwortzeit', $stats['avgFrtFormatted'])
                ->description('Letzte 30 Tage')
                ->descriptionIcon('heroicon-o-clock')
                ->color($stats['avgFrtMinutes'] <= 60 ? 'success'
                    : ($stats['avgFrtMinutes'] <= 240 ? 'warning' : 'danger'))
                ->chart($this->getFrtTrend()),

            Stat::make('FRT SLA Compliance', "{$stats['frtCompliance']}%")
                ->description("{$stats['respondedLast30Days']} Antworten (30d)")
                ->descriptionIcon('heroicon-o-shield-check')
                ->color($stats['frtCompliance'] >= 90 ? 'success'
                    : ($stats['frtCompliance'] >= 70 ? 'warning' : 'danger')),
        ];
        } catch (\Throwable $e) {
            Log::error('[FirstResponseTimeWidget] getStats failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->getEmptyStats();
        }
    }

    protected function getAwaitingTrend(): array
    {
        try {
            $companyId = $this->getEffectiveCompanyId();
            $cacheKey = $companyId ? "service_gateway_frt_awaiting_trend_{$companyId}" : 'service_gateway_frt_awaiting_trend_all';

            return Cache::remember($cacheKey, config('gateway.cache.widget_trends_seconds'), function () use ($companyId) {
                $trend = [];
                for ($i = 6; $i >= 0; $i--) {
                    $date = now()->subDays($i)->endOfDay();
                    $query = ServiceCase::query();
                    if ($companyId) {
                        $query->where('company_id', $companyId);
                    }
                    $trend[] = $query
                        ->where('created_at', '<=', $date)
                        ->where(function ($q) use ($date) {
                            $q->whereNull('sla_response_met_at')
                                ->orWhere('sla_response_met_at', '>', $date);
                        })
                        ->whereIn('status', [ServiceCase::STATUS_NEW, ServiceCase::STATUS_OPEN, ServiceCase::STATUS_PENDING])
                        ->count();
                }
                return $trend;
            });
        } catch (\Throwable $e) {
            Log::warning('[FirstResponseTimeWidget] getAwaitingTrend failed', [
                'error' => $e->getMessage(),
            ]);
            return [0, 0, 0, 0, 0, 0, 0];
        }
    }

    protected function getFrtTrend(): array
    {
        try {
            $companyId = $this->getEffectiveCompanyId();
            $cacheKey = $companyId ? "service_gateway_frt_trend_{$companyId}" : 'service_gateway_frt_trend_all';

            return Cache::remember($cacheKey, config('gateway.cache.widget_trends_seconds'), function () use ($companyId) {
                $trend = [];
                for ($i = 6; $i >= 0; $i--) {
                    $date = now()->subDays($i);
                    $query = ServiceCase::query();
                    if ($companyId) {
                        $query->where('company_id', $companyId);
                    }
                    $avgMinutes = $query
                        ->whereNotNull('sla_response_met_at')
                        ->whereDate('sla_response_met_at', $date)
                        ->selectRaw('AVG(TIMESTAMPDIFF(MINUTE, created_at, sla_response_met_at)) as avg')
                        ->value('avg');
                    $trend[] = $avgMinutes ? round($avgMinutes / 60, 1) : 0;
                }
                return $trend;
            });
        } catch (\Throwable $e) {
            Log::warning('[FirstResponseTimeWidget] getFrtTrend failed', [
                'error' => $e->getMessage(),
            ]);
            return [0, 0, 0, 0, 0, 0, 0];
        }
    }

    protected function getEmptyStats(): array
    {
        return [
            Stat::make('Warten auf Antwort', 0)->color('gray'),
            Stat::make('Avg. Erstantwortzeit', '—')->color('gray'),
            Stat::make('FRT SLA Compliance', '—')->color('gray'),
        ];
    }
}
