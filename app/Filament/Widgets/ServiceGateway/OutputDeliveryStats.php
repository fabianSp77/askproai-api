<?php

namespace App\Filament\Widgets\ServiceGateway;

use App\Models\ServiceCase;
use App\Models\ServiceGatewayExchangeLog;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Output Delivery Statistics Widget
 *
 * Shows delivery metrics: pending, failed, success rate, retry queue.
 * SECURITY: All queries explicitly filtered by company_id for multi-tenancy isolation.
 * FEATURE: Supports company filter from dashboard for super-admins.
 */
class OutputDeliveryStats extends BaseWidget
{
    use InteractsWithPageFilters;

    protected static ?string $pollingInterval = '30s';
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
            $cacheKey = $companyId ? "service_gateway_delivery_stats_{$companyId}" : 'service_gateway_delivery_stats_all';

            $stats = Cache::remember($cacheKey, config('gateway.cache.widget_stats_seconds'), function () use ($companyId) {
                $baseQuery = ServiceCase::query();
            if ($companyId) {
                $baseQuery->where('company_id', $companyId);
            }

            // Pending outputs (enriched but not delivered)
            $pendingDelivery = (clone $baseQuery)
                ->where('enrichment_status', ServiceCase::ENRICHMENT_ENRICHED)
                ->where(function ($q) {
                    $q->whereNull('output_status')
                        ->orWhere('output_status', ServiceCase::OUTPUT_PENDING);
                })
                ->count();

            // Failed deliveries
            $failedDelivery = (clone $baseQuery)
                ->where('output_status', ServiceCase::OUTPUT_FAILED)
                ->count();

            // Successful deliveries (last 24h)
            $successLast24h = (clone $baseQuery)
                ->where('output_status', ServiceCase::OUTPUT_SENT)
                ->where('output_sent_at', '>=', now()->subHours(24))
                ->count();

            // Total deliveries (last 24h)
            $totalLast24h = (clone $baseQuery)
                ->whereIn('output_status', [
                    ServiceCase::OUTPUT_SENT,
                    ServiceCase::OUTPUT_FAILED,
                ])
                ->where('updated_at', '>=', now()->subHours(24))
                ->count();

            $successRate = $totalLast24h > 0
                ? round(($successLast24h / $totalLast24h) * 100, 1)
                : 100;

            // Exchange log errors (last 24h)
            $exchangeErrorsQuery = ServiceGatewayExchangeLog::where('created_at', '>=', now()->subHours(24));
            if ($companyId) {
                $exchangeErrorsQuery->whereHas('serviceCase', function ($q) use ($companyId) {
                    $q->where('company_id', $companyId);
                });
            }
            $exchangeErrors = $exchangeErrorsQuery
                ->where(function ($q) {
                    $q->where('status_code', '>=', 400)
                        ->orWhereNotNull('error_class');
                })
                ->count();

            // Avg delivery time (last 7 days, in minutes)
            $avgDeliveryMinutes = (clone $baseQuery)
                ->where('output_status', ServiceCase::OUTPUT_SENT)
                ->whereNotNull('output_sent_at')
                ->where('output_sent_at', '>=', now()->subDays(7))
                ->selectRaw('AVG(TIMESTAMPDIFF(MINUTE, created_at, output_sent_at)) as avg_minutes')
                ->value('avg_minutes');
            $avgDeliveryMinutes = $avgDeliveryMinutes ? round($avgDeliveryMinutes) : 0;

            return compact(
                'pendingDelivery',
                'failedDelivery',
                'successRate',
                'exchangeErrors',
                'avgDeliveryMinutes',
                'successLast24h'
            );
        });

        return [
            Stat::make('Ausstehende Outputs', $stats['pendingDelivery'])
                ->description('Warten auf Zustellung')
                ->descriptionIcon('heroicon-o-clock')
                ->color($stats['pendingDelivery'] > 10 ? 'warning' : 'gray'),

            Stat::make('Fehlgeschlagen', $stats['failedDelivery'])
                ->description($stats['exchangeErrors'] > 0 ? "{$stats['exchangeErrors']} Errors (24h)" : 'Keine Errors')
                ->descriptionIcon($stats['failedDelivery'] > 0 ? 'heroicon-o-x-circle' : 'heroicon-o-check-circle')
                ->color($stats['failedDelivery'] > 0 ? 'danger' : 'success'),

            Stat::make('Erfolgsrate', "{$stats['successRate']}%")
                ->description("Letzte 24h ({$stats['successLast24h']} zugestellt)")
                ->descriptionIcon('heroicon-o-paper-airplane')
                ->color($stats['successRate'] >= 95 ? 'success' : ($stats['successRate'] >= 80 ? 'warning' : 'danger')),

            Stat::make('Avg. Zustellzeit', "{$stats['avgDeliveryMinutes']} Min")
                ->description('Letzte 7 Tage')
                ->descriptionIcon('heroicon-o-bolt')
                ->color($stats['avgDeliveryMinutes'] <= 5 ? 'success' : ($stats['avgDeliveryMinutes'] <= 15 ? 'warning' : 'danger')),
        ];
        } catch (\Throwable $e) {
            Log::error('[OutputDeliveryStats] Failed to get stats', ['error' => $e->getMessage()]);
            return $this->getEmptyStats();
        }
    }

    protected function getEmptyStats(): array
    {
        return [
            Stat::make('Ausstehende Outputs', 0)->color('gray'),
            Stat::make('Fehlgeschlagen', 0)->color('gray'),
            Stat::make('Erfolgsrate', '—')->color('gray'),
            Stat::make('Avg. Zustellzeit', '—')->color('gray'),
        ];
    }
}
