<?php

namespace App\Filament\Widgets\CrmAppointments;

use App\Filament\Widgets\CrmAppointments\Concerns\HasCrmFilters;
use App\Models\Call;
use App\Models\RetellAgent;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Performance Statistics Widget
 *
 * Shows performance metrics: avg duration, avg sentiment, top agent, unknown customers.
 * SECURITY: All queries filtered by company_id for multi-tenancy.
 * FEATURE: Supports company, agent, and time_range filters.
 */
class PerformanceStats extends BaseWidget
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
            $cacheKey = "crm_performance_{$this->getFilterCacheKey()}";

            $stats = Cache::remember($cacheKey, 60, function () use ($companyId, $agentId, $timeRangeStart) {
                $baseQuery = Call::query()->successful();
                if ($companyId) {
                    $baseQuery->where('company_id', $companyId);
                }
                if ($agentId) {
                    $baseQuery->where('retell_agent_id', $agentId);
                }
                if ($timeRangeStart) {
                    $baseQuery->where('created_at', '>=', $timeRangeStart);
                }

                // Average duration (in seconds)
                $avgDurationSec = (clone $baseQuery)
                    ->whereNotNull('duration_sec')
                    ->avg('duration_sec') ?? 0;

                // Average sentiment score (0-100 scale if exists)
                $avgSentiment = (clone $baseQuery)
                    ->whereNotNull('sentiment_score')
                    ->avg('sentiment_score');

                // Top performing agent (by conversion rate)
                $topAgentData = Call::query()->successful()
                    ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
                    ->when($timeRangeStart, fn ($q) => $q->where('created_at', '>=', $timeRangeStart))
                    ->select('retell_agent_id')
                    ->selectRaw('COUNT(*) as total_calls')
                    ->selectRaw('SUM(CASE WHEN has_appointment THEN 1 ELSE 0 END) as conversions')
                    ->groupBy('retell_agent_id')
                    ->havingRaw('COUNT(*) >= 5') // Minimum 5 calls for meaningful rate
                    ->orderByRaw('SUM(CASE WHEN has_appointment THEN 1 ELSE 0 END) / COUNT(*) DESC')
                    ->first();

                $topAgentName = null;
                $topAgentRate = 0;
                if ($topAgentData) {
                    $agent = RetellAgent::where('agent_id', $topAgentData->retell_agent_id)->first();
                    $topAgentName = $agent?->name ?? 'Unbekannt';
                    $topAgentRate = $topAgentData->total_calls > 0
                        ? round(($topAgentData->conversions / $topAgentData->total_calls) * 100, 1)
                        : 0;
                }

                // Unknown/anonymous customers
                $anonymousQuery = Call::query();
                if ($companyId) {
                    $anonymousQuery->where('company_id', $companyId);
                }
                if ($agentId) {
                    $anonymousQuery->where('retell_agent_id', $agentId);
                }
                if ($timeRangeStart) {
                    $anonymousQuery->where('created_at', '>=', $timeRangeStart);
                }
                $anonymousCalls = $anonymousQuery->anonymous()->count();

                $totalCalls = Call::query()
                    ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
                    ->when($agentId, fn ($q) => $q->where('retell_agent_id', $agentId))
                    ->when($timeRangeStart, fn ($q) => $q->where('created_at', '>=', $timeRangeStart))
                    ->count();

                $anonymousRate = $totalCalls > 0
                    ? round(($anonymousCalls / $totalCalls) * 100, 1)
                    : 0;

                return compact('avgDurationSec', 'avgSentiment', 'topAgentName', 'topAgentRate', 'anonymousCalls', 'anonymousRate');
            });

            // Format duration as Xm Ys
            $minutes = floor($stats['avgDurationSec'] / 60);
            $seconds = round($stats['avgDurationSec'] % 60);
            $durationFormatted = $minutes > 0 ? "{$minutes}m {$seconds}s" : "{$seconds}s";

            return [
                Stat::make('Avg. Dauer', $durationFormatted)
                    ->description($this->getTimeRangeLabel())
                    ->descriptionIcon('heroicon-o-clock')
                    ->color($stats['avgDurationSec'] <= 180 ? 'success' : ($stats['avgDurationSec'] <= 300 ? 'warning' : 'danger')),

                Stat::make('Avg. Sentiment', $stats['avgSentiment'] !== null ? round($stats['avgSentiment']) : '—')
                    ->description($stats['avgSentiment'] !== null ? 'Stimmungsanalyse' : 'Keine Daten')
                    ->descriptionIcon('heroicon-o-face-smile')
                    ->color($stats['avgSentiment'] !== null
                        ? ($stats['avgSentiment'] >= 70 ? 'success' : ($stats['avgSentiment'] >= 40 ? 'warning' : 'danger'))
                        : 'gray'),

                Stat::make('Top Agent', $stats['topAgentName'] ?? '—')
                    ->description($stats['topAgentName'] ? "{$stats['topAgentRate']}% Terminquote" : 'Keine Daten')
                    ->descriptionIcon('heroicon-o-trophy')
                    ->color($stats['topAgentName'] ? 'success' : 'gray'),

                Stat::make('Unbekannte Kunden', $stats['anonymousCalls'])
                    ->description("{$stats['anonymousRate']}% der Anrufe")
                    ->descriptionIcon('heroicon-o-user-circle')
                    ->color($stats['anonymousRate'] <= 20 ? 'success' : ($stats['anonymousRate'] <= 40 ? 'warning' : 'danger')),
            ];
        } catch (\Throwable $e) {
            Log::error('[PerformanceStats] Failed', ['error' => $e->getMessage()]);
            return $this->getEmptyStats();
        }
    }

    protected function getEmptyStats(): array
    {
        return [
            Stat::make('Avg. Dauer', '—')->color('gray'),
            Stat::make('Avg. Sentiment', '—')->color('gray'),
            Stat::make('Top Agent', '—')->color('gray'),
            Stat::make('Unbekannte Kunden', 0)->color('gray'),
        ];
    }
}
