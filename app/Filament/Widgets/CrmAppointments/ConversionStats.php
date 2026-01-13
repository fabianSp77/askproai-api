<?php

namespace App\Filament\Widgets\CrmAppointments;

use App\Filament\Widgets\CrmAppointments\Concerns\HasCrmFilters;
use App\Models\Call;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Conversion Statistics Widget
 *
 * Shows appointment conversion metrics: conversion rate, conversions count,
 * cost per conversion, 7-day trend.
 * SECURITY: All queries filtered by company_id for multi-tenancy.
 * FEATURE: Supports company, agent, and time_range filters.
 */
class ConversionStats extends BaseWidget
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
            $cacheKey = "crm_conversion_{$this->getFilterCacheKey()}";

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

                $totalSuccessful = (clone $baseQuery)->count();
                $withAppointment = (clone $baseQuery)->withAppointment()->count();

                $conversionRate = $totalSuccessful > 0
                    ? round(($withAppointment / $totalSuccessful) * 100, 1)
                    : 0;

                // Cost calculation
                $totalCost = (clone $baseQuery)->sum('base_cost') ?? 0;
                $costPerConversion = $withAppointment > 0
                    ? round($totalCost / $withAppointment, 2)
                    : 0;

                // Today's conversions
                $todayConversions = (clone $baseQuery)
                    ->whereDate('created_at', today())
                    ->withAppointment()
                    ->count();

                // Yesterday's conversions for comparison
                $yesterdayConversions = Call::query()->successful()
                    ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
                    ->when($agentId, fn ($q) => $q->where('retell_agent_id', $agentId))
                    ->whereDate('created_at', today()->subDay())
                    ->withAppointment()
                    ->count();

                $conversionTrend = $yesterdayConversions > 0
                    ? round((($todayConversions - $yesterdayConversions) / $yesterdayConversions) * 100)
                    : 0;

                return compact('totalSuccessful', 'withAppointment', 'conversionRate', 'totalCost', 'costPerConversion', 'todayConversions', 'conversionTrend');
            });

            return [
                Stat::make('Terminquote', "{$stats['conversionRate']}%")
                    ->description("{$stats['withAppointment']} von {$stats['totalSuccessful']} Anrufen")
                    ->descriptionIcon('heroicon-o-calendar')
                    ->color($stats['conversionRate'] >= 30 ? 'success' : ($stats['conversionRate'] >= 15 ? 'warning' : 'danger'))
                    ->chart($this->getConversionTrend()),

                Stat::make('Termine ' . $this->getTimeRangeLabel(), $stats['withAppointment'])
                    ->description($stats['conversionTrend'] >= 0 ? "+{$stats['conversionTrend']}% vs. Vortag" : "{$stats['conversionTrend']}% vs. Vortag")
                    ->descriptionIcon($stats['conversionTrend'] >= 0 ? 'heroicon-o-arrow-trending-up' : 'heroicon-o-arrow-trending-down')
                    ->color($stats['conversionTrend'] >= 0 ? 'success' : 'warning'),

                Stat::make('Kosten/Termin', $stats['costPerConversion'] > 0 ? number_format($stats['costPerConversion'], 2, ',', '.') . ' €' : '—')
                    ->description('Durchschnitt')
                    ->descriptionIcon('heroicon-o-currency-euro')
                    ->color($stats['costPerConversion'] <= 5 ? 'success' : ($stats['costPerConversion'] <= 10 ? 'warning' : 'danger')),

                Stat::make('Gesamtkosten', number_format($stats['totalCost'], 2, ',', '.') . ' €')
                    ->description($this->getTimeRangeLabel())
                    ->descriptionIcon('heroicon-o-banknotes')
                    ->color('gray'),
            ];
        } catch (\Throwable $e) {
            Log::error('[ConversionStats] Failed', ['error' => $e->getMessage()]);
            return $this->getEmptyStats();
        }
    }

    protected function getConversionTrend(): array
    {
        try {
            $companyId = $this->getEffectiveCompanyId();
            $agentId = $this->getEffectiveAgentId();
            $cacheKey = "crm_conversion_trend_{$this->getFilterCacheKey()}";

            return Cache::remember($cacheKey, 300, function () use ($companyId, $agentId) {
                $trend = [];
                for ($i = 6; $i >= 0; $i--) {
                    $date = now()->subDays($i);
                    $successfulQuery = Call::query()->successful()
                        ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
                        ->when($agentId, fn ($q) => $q->where('retell_agent_id', $agentId))
                        ->whereDate('created_at', $date);

                    $total = (clone $successfulQuery)->count();
                    $converted = (clone $successfulQuery)->withAppointment()->count();

                    $trend[] = $total > 0 ? round(($converted / $total) * 100) : 0;
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
            Stat::make('Terminquote', '—')->color('gray'),
            Stat::make('Termine', 0)->color('gray'),
            Stat::make('Kosten/Termin', '—')->color('gray'),
            Stat::make('Gesamtkosten', '—')->color('gray'),
        ];
    }
}
