<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CurrencyConverter
{
    /**
     * Fester Wechselkurs USD -> EUR
     * Kann später durch eine API ersetzt werden
     */
    const USD_TO_EUR_RATE = 0.92;
    
    /**
     * Convert cents to euros
     * 
     * @param float $cents
     * @return float
     */
    public static function centsToEuros(float $cents): float
    {
        try {
            // Validierung
            if (!is_numeric($cents)) {
                Log::error('CurrencyConverter: Invalid cents value', ['cents' => $cents]);
                return 0.0;
            }
            
            // Cents -> Dollar -> Euro
            $dollars = $cents / 100;
            $exchangeRate = self::getExchangeRate();
            $euros = $dollars * $exchangeRate;
            
            Log::debug('CurrencyConverter: Cents to Euros conversion', [
                'cents' => $cents,
                'dollars' => $dollars,
                'exchange_rate' => $exchangeRate,
                'euros' => $euros
            ]);
            
            return round($euros, 4);
        } catch (\Exception $e) {
            Log::error('CurrencyConverter: Error converting cents to euros', [
                'cents' => $cents,
                'error' => $e->getMessage()
            ]);
            return 0.0;
        }
    }
    
    /**
     * Convert dollars to euros
     * 
     * @param float $dollars
     * @return float
     */
    public static function dollarsToEuros(float $dollars): float
    {
        return round($dollars * self::getExchangeRate(), 4);
    }
    
    /**
     * Get current exchange rate (cached)
     * 
     * @param \DateTime|null $date Optional date for historical rate
     * @return float
     */
    public static function getExchangeRate($date = null): float
    {
        // Wenn ein Datum angegeben wurde, hole historischen Kurs
        if ($date) {
            return self::getHistoricalRate($date);
        }
        
        // Cache für 24 Stunden
        return Cache::remember('usd_to_eur_rate', 86400, function () {
            // Später kann hier eine echte API eingebunden werden
            // z.B. https://api.exchangerate-api.com/v4/latest/USD
            
            // Für jetzt verwenden wir einen festen Kurs
            return self::USD_TO_EUR_RATE;
        });
    }
    
    /**
     * Get historical exchange rate for a specific date
     * 
     * @param \DateTime $date
     * @return float
     */
    protected static function getHistoricalRate($date): float
    {
        $dateKey = $date->format('Y-m-d');
        
        // Cache historische Kurse für 7 Tage
        return Cache::remember("usd_to_eur_rate_{$dateKey}", 604800, function () use ($date, $dateKey) {
            // TODO: Hier würde eine echte API wie die ECB API verwendet werden
            // Beispiel: https://api.exchangeratesapi.io/v1/{date}?base=USD&symbols=EUR
            
            // Vorerst simulieren wir leichte Schwankungen basierend auf dem Datum
            $baseRate = self::USD_TO_EUR_RATE;
            $dayOfYear = (int)$date->format('z');
            $variation = sin($dayOfYear / 365 * 2 * pi()) * 0.05; // ±5% Schwankung
            
            return round($baseRate + ($baseRate * $variation), 4);
        });
    }
    
    /**
     * Convert Retell cost structure to euros
     * 
     * @param array|float $costData
     * @return float
     */
    public static function convertRetellCostToEuros($costData): float
    {
        try {
            if (is_numeric($costData)) {
                // Annahme: Einzelwert ist in Cents
                Log::debug('CurrencyConverter: Converting numeric cost data', ['cost' => $costData]);
                return self::centsToEuros($costData);
            }
            
            if (is_array($costData)) {
                // Priorität: combined_cost > total_cost
                $cents = $costData['combined_cost'] ?? $costData['total_cost'] ?? 0;
                
                Log::debug('CurrencyConverter: Converting array cost data', [
                    'combined_cost' => $costData['combined_cost'] ?? null,
                    'total_cost' => $costData['total_cost'] ?? null,
                    'selected_cents' => $cents
                ]);
                
                return self::centsToEuros($cents);
            }
            
            Log::warning('CurrencyConverter: Invalid cost data type', [
                'type' => gettype($costData),
                'data' => $costData
            ]);
            
            return 0.0;
        } catch (\Exception $e) {
            Log::error('CurrencyConverter: Error converting Retell cost', [
                'cost_data' => $costData,
                'error' => $e->getMessage()
            ]);
            return 0.0;
        }
    }
    
    /**
     * Format cost breakdown for storage
     * Konvertiert alle Cent-Werte zu Euro-Werten
     * 
     * @param array $costBreakdown
     * @return array
     */
    public static function formatCostBreakdown(array $costBreakdown): array
    {
        $formatted = $costBreakdown;
        
        // Konvertiere Hauptkosten
        if (isset($formatted['combined_cost'])) {
            $formatted['combined_cost_cents'] = $formatted['combined_cost'];
            $formatted['combined_cost_euros'] = self::centsToEuros($formatted['combined_cost']);
        }
        
        if (isset($formatted['total_cost'])) {
            $formatted['total_cost_cents'] = $formatted['total_cost'];
            $formatted['total_cost_euros'] = self::centsToEuros($formatted['total_cost']);
        }
        
        // Konvertiere Produktkosten
        if (isset($formatted['product_costs']) && is_array($formatted['product_costs'])) {
            foreach ($formatted['product_costs'] as &$product) {
                if (isset($product['cost'])) {
                    $product['cost_cents'] = $product['cost'];
                    $product['cost_euros'] = self::centsToEuros($product['cost']);
                }
            }
        }
        
        // Füge Metadaten hinzu
        $formatted['currency'] = 'EUR';
        $formatted['exchange_rate'] = self::getExchangeRate();
        $formatted['converted_at'] = now()->toIso8601String();
        
        return $formatted;
    }
}