<?php

namespace App\Services;

use Stichoza\GoogleTranslate\GoogleTranslate;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class FreeTranslationService
{
    /**
     * Translate text to German
     *
     * @param string $text
     * @param string|null $sourceLanguage
     * @return string
     */
    public function translateToGerman(string $text, ?string $sourceLanguage = null): string
    {
        if (empty(trim($text))) {
            return $text;
        }

        // Check if text is already in German
        if ($sourceLanguage === 'de' || $this->isGerman($text)) {
            return $text;
        }

        // Create cache key
        $cacheKey = 'translation_de_' . md5($text);

        // Check cache first
        $cached = Cache::get($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        try {
            // Initialize Google Translate
            $translator = new GoogleTranslate('de');

            // Auto-detect source language if not provided
            if (!$sourceLanguage) {
                $translator->setSource(null); // Auto-detect
            } else {
                $translator->setSource($sourceLanguage);
            }

            // Translate the text
            $translated = $translator->translate($text);

            // Cache the translation for 30 days
            Cache::put($cacheKey, $translated, now()->addDays(30));

            return $translated;

        } catch (\Exception $e) {
            // Log the error
            Log::warning('Translation failed: ' . $e->getMessage(), [
                'text' => substr($text, 0, 100),
                'error' => $e->getMessage()
            ]);

            // Return original text on failure
            return $text;
        }
    }

    /**
     * Translate text to multiple languages
     *
     * @param string $text
     * @param array $targetLanguages ['de', 'tr', 'ar']
     * @param string|null $sourceLanguage
     * @return array
     */
    public function translateToMultiple(string $text, array $targetLanguages, ?string $sourceLanguage = null): array
    {
        $translations = [];

        foreach ($targetLanguages as $lang) {
            $cacheKey = 'translation_' . $lang . '_' . md5($text);

            // Check cache first
            $cached = Cache::get($cacheKey);
            if ($cached !== null) {
                $translations[$lang] = $cached;
                continue;
            }

            try {
                $translator = new GoogleTranslate($lang);

                if ($sourceLanguage) {
                    $translator->setSource($sourceLanguage);
                }

                $translated = $translator->translate($text);

                // Cache the translation
                Cache::put($cacheKey, $translated, now()->addDays(30));

                $translations[$lang] = $translated;

            } catch (\Exception $e) {
                Log::warning("Translation to {$lang} failed: " . $e->getMessage());
                $translations[$lang] = $text;
            }
        }

        return $translations;
    }

    /**
     * Detect the language of the text
     *
     * @param string $text
     * @return string|null
     */
    public function detectLanguage(string $text): ?string
    {
        try {
            // Simple heuristic detection based on common words
            $germanWords = ['der', 'die', 'das', 'und', 'ist', 'ein', 'eine', 'haben', 'werden', 'können', 'für', 'mit', 'sich'];
            $englishWords = ['the', 'and', 'is', 'are', 'was', 'were', 'have', 'has', 'for', 'with', 'that', 'this', 'appointment', 'customer', 'call'];
            $turkishWords = ['ve', 'bir', 'bu', 'için', 'ile', 'olan', 'olarak', 'sonra', 'gibi', 'daha'];
            $arabicPattern = '/[\x{0600}-\x{06FF}]/u';

            $lowerText = mb_strtolower($text);

            // Check for Arabic characters
            if (preg_match($arabicPattern, $text)) {
                return 'ar';
            }

            // Count word matches
            $germanScore = 0;
            $englishScore = 0;
            $turkishScore = 0;

            foreach ($germanWords as $word) {
                if (str_contains($lowerText, ' ' . $word . ' ') ||
                    str_starts_with($lowerText, $word . ' ') ||
                    str_ends_with($lowerText, ' ' . $word)) {
                    $germanScore++;
                }
            }

            foreach ($englishWords as $word) {
                if (str_contains($lowerText, ' ' . $word . ' ') ||
                    str_starts_with($lowerText, $word . ' ') ||
                    str_ends_with($lowerText, ' ' . $word)) {
                    $englishScore++;
                }
            }

            foreach ($turkishWords as $word) {
                if (str_contains($lowerText, ' ' . $word . ' ') ||
                    str_starts_with($lowerText, $word . ' ') ||
                    str_ends_with($lowerText, ' ' . $word)) {
                    $turkishScore++;
                }
            }

            // If text is already German, return 'de'
            if ($this->isGerman($text)) {
                return 'de';
            }

            // Return the language with highest score
            if ($germanScore > $englishScore && $germanScore > $turkishScore) {
                return 'de';
            } elseif ($turkishScore > $englishScore && $turkishScore > $germanScore) {
                return 'tr';
            } elseif ($englishScore > 0) {
                return 'en';
            } else {
                return 'en'; // Default to English
            }

        } catch (\Exception $e) {
            Log::warning('Language detection failed: ' . $e->getMessage());
            return 'en'; // Default to English on error
        }
    }

    /**
     * Check if text is likely German
     *
     * @param string $text
     * @return bool
     */
    private function isGerman(string $text): bool
    {
        // Common German words and patterns
        $germanPatterns = [
            '/\b(der|die|das|den|dem|des|ein|eine|einer|einen|einem|eines)\b/i',
            '/\b(und|oder|aber|weil|dass|wenn|als|wie|für|mit|bei|nach|vor|zu|von|auf|in|an)\b/i',
            '/\b(ich|du|er|sie|es|wir|ihr|Sie)\b/i',
            '/\b(ist|sind|war|waren|hat|haben|wird|werden|kann|können|muss|müssen)\b/i',
            '/[äöüÄÖÜß]/',
            '/\b(Termin|Kunde|Anruf|Gespräch|Uhrzeit|Datum)\b/i',
        ];

        $matches = 0;
        foreach ($germanPatterns as $pattern) {
            if (preg_match($pattern, $text)) {
                $matches++;
            }
        }

        // If we have at least 3 German patterns, consider it German
        return $matches >= 3;
    }

    /**
     * Get translated summary for a call with caching
     *
     * @param \App\Models\Call $call
     * @param string $targetLanguage
     * @return string
     */
    public function getTranslatedCallSummary($call, string $targetLanguage = 'de'): string
    {
        if (empty($call->summary)) {
            return '';
        }

        // Check if we have cached translations
        if (!empty($call->summary_translations)) {
            $translations = is_string($call->summary_translations)
                ? json_decode($call->summary_translations, true)
                : $call->summary_translations;

            if (isset($translations[$targetLanguage])) {
                return $translations[$targetLanguage];
            }
        }

        // Translate and cache in database
        $translated = $this->translateToGerman($call->summary);

        // Update the call with cached translation
        $translations = $call->summary_translations ?? [];
        if (is_string($translations)) {
            $translations = json_decode($translations, true) ?? [];
        }
        $translations[$targetLanguage] = $translated;

        $call->update([
            'summary_translations' => $translations,
            'summary_language' => $this->detectLanguage($call->summary)
        ]);

        return $translated;
    }
}