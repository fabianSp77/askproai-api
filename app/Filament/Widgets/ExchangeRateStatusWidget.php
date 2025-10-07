<?php

namespace App\Filament\Widgets;

use App\Models\CurrencyExchangeRate;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Cache;

class ExchangeRateStatusWidget extends BaseWidget
{
    protected static ?int $sort = 3;

    protected int | string | array $columnSpan = 'full';

    protected static ?string $pollingInterval = '30s';

    protected function getStats(): array
    {
        try {
            $stats = Cache::remember('exchange_rate_stats', 300, function () {
                // Get USD → EUR rate (most important for Retell costs)
                $usdEurRate = CurrencyExchangeRate::where('from_currency', 'USD')
                    ->where('to_currency', 'EUR')
                    ->where('is_active', true)
                    ->orderBy('valid_from', 'desc')
                    ->first();

                // Get EUR → USD rate
                $eurUsdRate = CurrencyExchangeRate::where('from_currency', 'EUR')
                    ->where('to_currency', 'USD')
                    ->where('is_active', true)
                    ->orderBy('valid_from', 'desc')
                    ->first();

                // Get GBP → EUR rate
                $gbpEurRate = CurrencyExchangeRate::where('from_currency', 'GBP')
                    ->where('to_currency', 'EUR')
                    ->where('is_active', true)
                    ->orderBy('valid_from', 'desc')
                    ->first();

                $usdEurAge = $usdEurRate ? now()->diffInHours($usdEurRate->valid_from) : 999999;
                $usdEurDaysOld = $usdEurRate ? now()->diffInDays($usdEurRate->valid_from) : 999999;

                return [
                    'usd_eur_rate' => $usdEurRate,
                    'eur_usd_rate' => $eurUsdRate,
                    'gbp_eur_rate' => $gbpEurRate,
                    'hours_old' => $usdEurAge,
                    'days_old' => $usdEurDaysOld,
                    'total_active_rates' => CurrencyExchangeRate::active()->count(),
                ];
            });

        } catch (\Exception $e) {
            \Log::error('ExchangeRateStatusWidget Error: ' . $e->getMessage());
            return [
                Stat::make('Fehler', 'Widget-Fehler')
                    ->description('Exchange Rate Widget konnte nicht geladen werden')
                    ->color('danger'),
            ];
        }

        return [
            Stat::make('USD → EUR Kurs', $this->formatRate($stats['usd_eur_rate']))
                ->description($this->getAgeDescription($stats))
                ->descriptionIcon($this->getAgeIcon($stats))
                ->color($this->getAgeColor($stats))
                ->chart($this->getRateTrend()),

            Stat::make('EUR → USD Kurs', $this->formatRate($stats['eur_usd_rate']))
                ->description($this->getSourceDescription($stats['eur_usd_rate']))
                ->descriptionIcon('heroicon-o-globe-alt')
                ->color($stats['eur_usd_rate'] ? 'info' : 'gray'),

            Stat::make('Wechselkurse Aktiv', $stats['total_active_rates'])
                ->description($this->getUpdateFrequencyDescription())
                ->descriptionIcon('heroicon-o-clock')
                ->color('success')
                ->chart([1, 2, 3, 2, 3, 4, 3]),

            Stat::make('GBP → EUR Kurs', $this->formatRate($stats['gbp_eur_rate']))
                ->description($this->getSourceDescription($stats['gbp_eur_rate']))
                ->descriptionIcon('heroicon-o-banknotes')
                ->color($stats['gbp_eur_rate'] ? 'info' : 'gray'),
        ];
    }

    protected function formatRate(?CurrencyExchangeRate $rate): string
    {
        if (!$rate) {
            return 'Nicht verfügbar';
        }

        return number_format($rate->rate, 6);
    }

    protected function getAgeDescription(array $stats): string
    {
        if (!$stats['usd_eur_rate']) {
            return 'Kein Kurs verfügbar';
        }

        $hours = $stats['hours_old'];
        $days = $stats['days_old'];

        if ($hours < 1) {
            $minutes = round($hours * 60);
            return "Aktualisiert vor {$minutes} Minuten";
        }

        if ($hours < 24) {
            return "Aktualisiert vor {$hours} Stunden";
        }

        if ($days === 0) {
            return "Heute aktualisiert";
        }

        if ($days === 1) {
            return "Gestern aktualisiert";
        }

        return "Aktualisiert vor {$days} Tagen";
    }

    protected function getAgeIcon(array $stats): string
    {
        $hours = $stats['hours_old'];

        if ($hours > 168) { // 7 days
            return 'heroicon-o-exclamation-triangle';
        }

        if ($hours > 48) { // 2 days
            return 'heroicon-o-exclamation-circle';
        }

        return 'heroicon-o-check-circle';
    }

    protected function getAgeColor(array $stats): string
    {
        $hours = $stats['hours_old'];

        if ($hours > 168) { // 7 days
            return 'danger';
        }

        if ($hours > 48) { // 2 days
            return 'warning';
        }

        if ($hours > 24) { // 1 day
            return 'info';
        }

        return 'success';
    }

    protected function getSourceDescription(?CurrencyExchangeRate $rate): string
    {
        if (!$rate) {
            return 'Keine Quelle';
        }

        $source = match($rate->source) {
            'ecb' => 'European Central Bank',
            'fixer' => 'Fixer.io API',
            'manual' => 'Manuell eingetragen',
            default => ucfirst($rate->source ?? 'Unbekannt')
        };

        return "Quelle: {$source}";
    }

    protected function getUpdateFrequencyDescription(): string
    {
        return 'Täglich um 2:00 Uhr aktualisiert';
    }

    protected function getRateTrend(): array
    {
        // Get last 7 days of USD→EUR rates
        try {
            $rates = CurrencyExchangeRate::where('from_currency', 'USD')
                ->where('to_currency', 'EUR')
                ->where('valid_from', '>=', now()->subDays(7))
                ->orderBy('valid_from', 'asc')
                ->pluck('rate')
                ->toArray();

            if (empty($rates)) {
                return [0.85, 0.855, 0.86, 0.855, 0.86, 0.858, 0.856];
            }

            // Normalize to fit chart scale
            return array_map(fn($r) => ($r - 0.8) * 100, $rates);

        } catch (\Exception $e) {
            return [0.85, 0.855, 0.86, 0.855, 0.86, 0.858, 0.856];
        }
    }
}
