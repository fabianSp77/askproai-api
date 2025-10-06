<?php

namespace App\Services;

use App\Models\CurrencyExchangeRate;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class ExchangeRateService
{
    const SUPPORTED_CURRENCIES = ['USD', 'EUR', 'GBP'];
    const DEFAULT_CURRENCY = 'EUR';

    /**
     * Fetch exchange rates from European Central Bank
     */
    public function fetchECBRates(): array
    {
        try {
            $response = Http::timeout(10)->get('https://api.frankfurter.app/latest', [
                'from' => 'EUR',
                'to' => 'USD,GBP'
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $rates = $data['rates'] ?? [];

                // Update EUR to USD rate
                if (isset($rates['USD'])) {
                    CurrencyExchangeRate::updateRate('EUR', 'USD', $rates['USD'], 'ecb');
                    CurrencyExchangeRate::updateRate('USD', 'EUR', 1 / $rates['USD'], 'ecb');
                }

                // Update EUR to GBP rate
                if (isset($rates['GBP'])) {
                    CurrencyExchangeRate::updateRate('EUR', 'GBP', $rates['GBP'], 'ecb');
                    CurrencyExchangeRate::updateRate('GBP', 'EUR', 1 / $rates['GBP'], 'ecb');
                }

                // Calculate GBP to USD rate
                if (isset($rates['USD']) && isset($rates['GBP'])) {
                    $gbpToUsd = $rates['USD'] / $rates['GBP'];
                    CurrencyExchangeRate::updateRate('GBP', 'USD', $gbpToUsd, 'ecb');
                    CurrencyExchangeRate::updateRate('USD', 'GBP', 1 / $gbpToUsd, 'ecb');
                }

                Log::info('Successfully updated exchange rates from ECB', $rates);
                return $rates;
            }
        } catch (\Exception $e) {
            Log::error('Failed to fetch ECB rates', ['error' => $e->getMessage()]);
        }

        return [];
    }

    /**
     * Fetch exchange rates from Fixer.io (requires API key)
     */
    public function fetchFixerRates(): array
    {
        $apiKey = config('services.fixer.api_key');
        if (!$apiKey) {
            return [];
        }

        try {
            $response = Http::timeout(10)->get('http://data.fixer.io/api/latest', [
                'access_key' => $apiKey,
                'base' => 'EUR',
                'symbols' => 'USD,GBP'
            ]);

            if ($response->successful()) {
                $data = $response->json();
                if ($data['success'] ?? false) {
                    $rates = $data['rates'] ?? [];
                    $this->updateRatesFromSource($rates, 'fixer');
                    return $rates;
                }
            }
        } catch (\Exception $e) {
            Log::error('Failed to fetch Fixer rates', ['error' => $e->getMessage()]);
        }

        return [];
    }

    /**
     * Update rates from a source
     */
    private function updateRatesFromSource(array $rates, string $source): void
    {
        if (isset($rates['USD'])) {
            CurrencyExchangeRate::updateRate('EUR', 'USD', $rates['USD'], $source);
            CurrencyExchangeRate::updateRate('USD', 'EUR', 1 / $rates['USD'], $source);
        }

        if (isset($rates['GBP'])) {
            CurrencyExchangeRate::updateRate('EUR', 'GBP', $rates['GBP'], $source);
            CurrencyExchangeRate::updateRate('GBP', 'EUR', 1 / $rates['GBP'], $source);
        }

        if (isset($rates['USD']) && isset($rates['GBP'])) {
            $gbpToUsd = $rates['USD'] / $rates['GBP'];
            CurrencyExchangeRate::updateRate('GBP', 'USD', $gbpToUsd, $source);
            CurrencyExchangeRate::updateRate('USD', 'GBP', 1 / $gbpToUsd, $source);
        }
    }

    /**
     * Convert USD to EUR (commonly needed for external services)
     */
    public function convertUsdToEur(float $usdAmount): float
    {
        $rate = CurrencyExchangeRate::getCurrentRate('USD', 'EUR');
        if ($rate === null) {
            Log::warning('No USD to EUR rate available, using default 0.92');
            $rate = 0.92; // Fallback rate
        }
        return $usdAmount * $rate;
    }

    /**
     * Convert USD cents to EUR cents
     */
    public function convertUsdCentsToEurCents(int $usdCents): int
    {
        $rate = CurrencyExchangeRate::getCurrentRate('USD', 'EUR');
        if ($rate === null) {
            Log::warning('No USD to EUR rate available, using default 0.92');
            $rate = 0.92;
        }
        return (int)round($usdCents * $rate);
    }

    /**
     * Convert any currency to EUR
     */
    public function convertToEur(float $amount, string $fromCurrency): float
    {
        if ($fromCurrency === 'EUR') {
            return $amount;
        }

        $rate = CurrencyExchangeRate::getCurrentRate($fromCurrency, 'EUR');
        if ($rate === null) {
            Log::warning("No {$fromCurrency} to EUR rate available");
            return $amount; // Return unchanged if no rate available
        }

        return $amount * $rate;
    }

    /**
     * Convert any currency cents to EUR cents
     */
    public function convertCentsToEurCents(int $cents, string $fromCurrency): int
    {
        if ($fromCurrency === 'EUR') {
            return $cents;
        }

        $rate = CurrencyExchangeRate::getCurrentRate($fromCurrency, 'EUR');
        if ($rate === null) {
            Log::warning("No {$fromCurrency} to EUR rate available");
            return $cents;
        }

        return (int)round($cents * $rate);
    }

    /**
     * Get exchange rate for display
     */
    public function getDisplayRate(string $from, string $to): ?array
    {
        $rate = CurrencyExchangeRate::getCurrentRate($from, $to);
        if ($rate === null) {
            return null;
        }

        $rateRecord = CurrencyExchangeRate::where('from_currency', $from)
            ->where('to_currency', $to)
            ->where('is_active', true)
            ->first();

        return [
            'rate' => $rate,
            'source' => $rateRecord->source ?? 'manual',
            'updated_at' => $rateRecord->valid_from ?? now(),
            'display' => sprintf('1 %s = %.4f %s', $from, $rate, $to)
        ];
    }

    /**
     * Update all rates from available sources
     */
    public function updateAllRates(): array
    {
        $results = [];

        // Try ECB first (free and reliable)
        $ecbRates = $this->fetchECBRates();
        if (!empty($ecbRates)) {
            $results['ecb'] = $ecbRates;
        }

        // Try Fixer if configured
        if (config('services.fixer.api_key')) {
            $fixerRates = $this->fetchFixerRates();
            if (!empty($fixerRates)) {
                $results['fixer'] = $fixerRates;
            }
        }

        // Clear all rate caches
        foreach (self::SUPPORTED_CURRENCIES as $from) {
            foreach (self::SUPPORTED_CURRENCIES as $to) {
                Cache::forget("exchange_rate_{$from}_{$to}");
            }
        }

        return $results;
    }

    /**
     * Ensure default rates exist
     */
    public function ensureDefaultRates(): void
    {
        // Check if we have any rates
        $hasRates = CurrencyExchangeRate::active()->exists();

        if (!$hasRates) {
            // Seed default rates
            CurrencyExchangeRate::seedDefaultRates();
            Log::info('Seeded default exchange rates');
        }
    }

    /**
     * Calculate external costs for a call
     */
    public function calculateCallExternalCosts(array $costs): array
    {
        $result = [
            'retell_usd' => $costs['retell_usd'] ?? 0,
            'twilio_usd' => $costs['twilio_usd'] ?? 0,
            'total_usd' => 0,
            'exchange_rate' => null,
            'retell_eur_cents' => 0,
            'twilio_eur_cents' => 0,
            'total_eur_cents' => 0
        ];

        // Calculate total USD
        $result['total_usd'] = $result['retell_usd'] + $result['twilio_usd'];

        // Get exchange rate
        $rate = CurrencyExchangeRate::getCurrentRate('USD', 'EUR');
        if ($rate) {
            $result['exchange_rate'] = $rate;

            // Convert to EUR cents
            $result['retell_eur_cents'] = (int)round($result['retell_usd'] * $rate * 100);
            $result['twilio_eur_cents'] = (int)round($result['twilio_usd'] * $rate * 100);
            $result['total_eur_cents'] = $result['retell_eur_cents'] + $result['twilio_eur_cents'];
        } else {
            // Use fallback rate
            $result['exchange_rate'] = 0.92;
            $result['retell_eur_cents'] = (int)round($result['retell_usd'] * 0.92 * 100);
            $result['twilio_eur_cents'] = (int)round($result['twilio_usd'] * 0.92 * 100);
            $result['total_eur_cents'] = $result['retell_eur_cents'] + $result['twilio_eur_cents'];
        }

        return $result;
    }
}