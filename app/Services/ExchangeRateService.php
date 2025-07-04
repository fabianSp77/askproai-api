<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ExchangeRateService
{
    /**
     * Get USD to EUR exchange rate
     * Cached for 24 hours
     */
    public static function getUsdToEur(): float
    {
        return Cache::remember('exchange_rate_usd_eur', 86400, function () {
            try {
                // Try to get from European Central Bank API (free, no key required)
                $response = Http::timeout(5)->get('https://api.exchangerate-api.com/v4/latest/USD');
                
                if ($response->successful()) {
                    $data = $response->json();
                    if (isset($data['rates']['EUR'])) {
                        return (float) $data['rates']['EUR'];
                    }
                }
            } catch (\Exception $e) {
                Log::warning('Failed to fetch exchange rate: ' . $e->getMessage());
            }
            
            // Fallback to recent realistic rate
            return 0.92;
        });
    }
    
    /**
     * Convert USD cents to EUR
     */
    public static function convertCentsToEur(float $cents): float
    {
        $usd = $cents / 100;
        return $usd * self::getUsdToEur();
    }
    
    /**
     * Format USD product costs for display
     */
    public static function formatProductCosts(array $productCosts): array
    {
        $formatted = [];
        $productNames = [
            'elevenlabs_tts' => 'ElevenLabs TTS',
            'gemini_2_0_flash' => 'Gemini 2.0 Flash',
            'background_voice_cancellation' => 'Rauschunterdrückung',
            'openai_gpt4' => 'OpenAI GPT-4',
            'deepgram_stt' => 'Deepgram STT',
        ];
        
        foreach ($productCosts as $product) {
            $name = $productNames[$product['product']] ?? ucfirst(str_replace('_', ' ', $product['product']));
            $costUsd = $product['cost'] / 100;
            $costEur = $costUsd * self::getUsdToEur();
            
            $formatted[] = [
                'name' => $name,
                'cost_usd' => $costUsd,
                'cost_eur' => $costEur,
                'unit_price' => $product['unit_price'] ?? null,
            ];
        }
        
        return $formatted;
    }
}