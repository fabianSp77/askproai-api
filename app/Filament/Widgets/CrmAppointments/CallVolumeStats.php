<?php

namespace App\Filament\Widgets\CrmAppointments;

use App\Filament\Widgets\CrmAppointments\Concerns\HasCrmFilters;
use App\Models\Call;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Call Volume Statistics Widget
 *
 * Shows key call metrics: total calls, active calls, success rate, failed calls.
 * SECURITY: All queries filtered by company_id for multi-tenancy.
 * FEATURE: Supports company, agent, and time_range filters.
 */
class CallVolumeStats extends BaseWidget
{
    use InteractsWithPageFilters;
    use HasCrmFilters;

    protected static bool $isLazy = true;

    protected function getStats(): array
    {
        try {
            $companyId = $this->getEffectiveCompanyId();
            $agentId = $this->getEffectiveAgentId();
            $timeRangeStart = $this->getTimeRangeStart();
            $cacheKey = "crm_call_volume_{$this->getFilterCacheKey()}";

            $stats = Cache::remember($cacheKey, 60, function () use ($companyId, $agentId, $timeRangeStart) {
                $baseQuery = Call::query();
                if ($companyId) {
                    $baseQuery->where('company_id', $companyId);
                }
                if ($agentId) {
                    $baseQuery->where('retell_agent_id', $agentId);
                }
                if ($timeRangeStart) {
                    $baseQuery->where('created_at', '>=', $timeRangeStart);
                }

                $totalCalls = (clone $baseQuery)->count();
                $activeCalls = (clone $baseQuery)->active()->count();
                $successfulCalls = (clone $baseQuery)->successful()->count();
                $failedCalls = (clone $baseQuery)->failed()->count();

                $successRate = $totalCalls > 0
                    ? round(($successfulCalls / $totalCalls) * 100, 1)
                    : 0;

                // Comparison with previous period
                $compStart = $this->getComparisonStart();
                $compEnd = $this->getComparisonEnd();
                $prevCalls = 0;
                if ($compStart && $compEnd) {
                    $prevQuery = Call::query();
                    if ($companyId) {
                        $prevQuery->where('company_id', $companyId);
                    }
                    if ($agentId) {
                        $prevQuery->where('retell_agent_id', $agentId);
                    }
                    $prevCalls = $prevQuery->whereBetween('created_at', [$compStart, $compEnd])->count();
                }

                $trend = $prevCalls > 0
                    ? round((($totalCalls - $prevCalls) / $prevCalls) * 100)
                    : 0;

                return compact('totalCalls', 'activeCalls', 'successfulCalls', 'failedCalls', 'successRate', 'prevCalls', 'trend');
            });

            return [
                Stat::make('Anrufe', number_format($stats['totalCalls']))
                    ->description($stats['trend'] >= 0 ? "+{$stats['trend']}% vs. Vorperiode" : "{$stats['trend']}% vs. Vorperiode")
                    ->descriptionIcon($stats['trend'] >= 0 ? 'heroicon-o-arrow-trending-up' : 'heroicon-o-arrow-trending-down')
                    ->color($stats['trend'] >= 0 ? 'success' : 'warning')
                    ->chart($this->getCallTrend()),

                Stat::make('Aktive Anrufe', $stats['activeCalls'])
                    ->description('Laufend')
                    ->descriptionIcon('heroicon-o-phone')
                    ->color($stats['activeCalls'] > 0 ? 'info' : 'gray'),

                Stat::make('Erfolgsrate', "{$stats['successRate']}%")
                    ->description("{$stats['successfulCalls']} von {$stats['totalCalls']}")
                    ->descriptionIcon('heroicon-o-check-circle')
                    ->color($stats['successRate'] >= 70 ? 'success' : ($stats['successRate'] >= 50 ? 'warning' : 'danger')),

                Stat::make('Fehlgeschlagen', $stats['failedCalls'])
                    ->description($stats['totalCalls'] > 0 ? round(($stats['failedCalls'] / $stats['totalCalls']) * 100, 1) . '% der Anrufe' : 'Keine Anrufe')
                    ->descriptionIcon('heroicon-o-x-circle')
                    ->color($stats['failedCalls'] > 0 ? 'danger' : 'success'),
            ];
        } catch (\Throwable $e) {
            Log::error('[CallVolumeStats] Failed', ['error' => $e->getMessage()]);
            return $this->getEmptyStats();
        }
    }

    protected function getCallTrend(): array
    {
        try {
            $companyId = $this->getEffectiveCompanyId();
            $agentId = $this->getEffectiveAgentId();
            $cacheKey = "crm_call_trend_{$this->getFilterCacheKey()}";

            return Cache::remember($cacheKey, 300, function () use ($companyId, $agentId) {
                $trend = [];
                for ($i = 6; $i >= 0; $i--) {
                    $date = now()->subDays($i);
                    $query = Call::query();
                    if ($companyId) {
                        $query->where('company_id', $companyId);
                    }
                    if ($agentId) {
                        $query->where('retell_agent_id', $agentId);
                    }
                    $trend[] = $query->whereDate('created_at', $date)->count();
                }
                return $trend;
            });
        } catch (\Throwable $e) {
            return [0, 0, 0, 0, 0, 0, 0];
        }
    }

    protected function getEmptyStats(): array
    {
        return [
            Stat::make('Anrufe', 0)->color('gray'),
            Stat::make('Aktive Anrufe', 0)->color('gray'),
            Stat::make('Erfolgsrate', '—')->color('gray'),
            Stat::make('Fehlgeschlagen', 0)->color('gray'),
        ];
    }
}
