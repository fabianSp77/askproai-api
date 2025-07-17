<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class GoogleTranslateService
{
    /**
     * Translate text using Google Translate (free, no API key required)
     * Uses the Google Translate Web API (unofficial but reliable)
     */
    public function translate(string $text, string $targetLang, ?string $sourceLang = null): string
    {
        // Skip if text is empty
        if (empty(trim($text))) {
            return $text;
        }
        
        // Skip if source and target are the same
        if ($sourceLang && strtolower($sourceLang) === strtolower($targetLang)) {
            return $text;
        }
        
        // Check cache first
        $cacheKey = 'gtranslate:' . md5($text . $targetLang . $sourceLang);
        $cached = Cache::get($cacheKey);
        if ($cached !== null) {
            return $cached;
        }
        
        try {
            // Google Translate free API endpoint
            $url = 'https://translate.googleapis.com/translate_a/single';
            
            $response = Http::timeout(5)->get($url, [
                'client' => 'gtx',
                'sl' => $sourceLang ?? 'auto',  // Source language (auto-detect if not specified)
                'tl' => $targetLang,            // Target language
                'dt' => 't',                    // Return translated text
                'q' => $text,                   // Text to translate
            ]);
            
            if ($response->successful()) {
                $data = $response->json();
                
                // Extract translated text from response
                $translated = '';
                if (isset($data[0]) && is_array($data[0])) {
                    foreach ($data[0] as $sentence) {
                        if (isset($sentence[0])) {
                            $translated .= $sentence[0];
                        }
                    }
                }
                
                if (!empty($translated)) {
                    // Cache for 30 days
                    Cache::put($cacheKey, $translated, now()->addDays(30));
                    return $translated;
                }
            }
            
        } catch (\Exception $e) {
            Log::warning('Google Translate error', [
                'error' => $e->getMessage(),
                'text' => substr($text, 0, 100)
            ]);
        }
        
        // Return original text if translation fails
        return $text;
    }
    
    /**
     * Detect language of text
     */
    public function detectLanguage(string $text): ?string
    {
        try {
            $url = 'https://translate.googleapis.com/translate_a/single';
            
            $response = Http::timeout(5)->get($url, [
                'client' => 'gtx',
                'sl' => 'auto',
                'tl' => 'en',
                'dt' => 't',
                'q' => $text,
            ]);
            
            if ($response->successful()) {
                $data = $response->json();
                // Language is usually in $data[2]
                if (isset($data[2])) {
                    return substr($data[2], 0, 2); // Return 2-letter code
                }
            }
        } catch (\Exception $e) {
            Log::warning('Google Translate language detection error', [
                'error' => $e->getMessage()
            ]);
        }
        
        return null;
    }
}