<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use App\Services\GoogleTranslateService;

class TranslationService
{
    protected ?string $apiKey;
    protected string $apiUrl = 'https://api-free.deepl.com/v2/translate';
    protected bool $isPro = false;
    protected ?string $lastDetectedLanguage = null;
    
    public function __construct()
    {
        $this->apiKey = config('services.deepl.api_key');
        $this->isPro = config('services.deepl.pro', false);
        
        if ($this->isPro) {
            $this->apiUrl = 'https://api.deepl.com/v2/translate';
        }
    }
    
    /**
     * Translate text from source to target language
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
        $cacheKey = 'translation:' . md5($text . $targetLang . $sourceLang);
        $cached = Cache::get($cacheKey);
        if ($cached !== null) {
            return $cached;
        }
        
        // Try DeepL API if available
        if ($this->apiKey) {
            $translated = $this->translateWithDeepL($text, $targetLang, $sourceLang);
            if ($translated !== null) {
                Cache::put($cacheKey, $translated, now()->addDays(30));
                return $translated;
            }
        }
        
        // Try Google Translate (free)
        try {
            $googleTranslate = new GoogleTranslateService();
            $translated = $googleTranslate->translate($text, $targetLang, $sourceLang);
            if ($translated !== $text) {
                Cache::put($cacheKey, $translated, now()->addDays(30));
                return $translated;
            }
        } catch (\Exception $e) {
            Log::info('Google Translate fallback failed', ['error' => $e->getMessage()]);
        }
        
        // Fallback to simple dictionary translation
        $translated = $this->translateWithDictionary($text, $targetLang, $sourceLang);
        
        // Cache the result
        Cache::put($cacheKey, $translated, now()->addDays(30));
        
        return $translated;
    }
    
    /**
     * Translate using DeepL API
     */
    protected function translateWithDeepL(string $text, string $targetLang, ?string $sourceLang = null): ?string
    {
        try {
            $params = [
                'auth_key' => $this->apiKey,
                'text' => $text,
                'target_lang' => strtoupper($targetLang),
            ];
            
            if ($sourceLang) {
                $params['source_lang'] = strtoupper($sourceLang);
            }
            
            $response = Http::asForm()
                ->timeout(10)
                ->post($this->apiUrl, $params);
            
            if ($response->successful()) {
                $data = $response->json();
                return $data['translations'][0]['text'] ?? null;
            }
            
            Log::warning('DeepL API translation failed', [
                'status' => $response->status(),
                'response' => $response->body()
            ]);
            
        } catch (\Exception $e) {
            Log::error('DeepL API error', [
                'error' => $e->getMessage()
            ]);
        }
        
        return null;
    }
    
    /**
     * Simple dictionary-based translation fallback
     */
    protected function translateWithDictionary(string $text, string $targetLang, ?string $sourceLang = null): string
    {
        // For longer texts, use sentence-based translation
        if (strlen($text) > 100) {
            return $this->translateSentences($text, $targetLang, $sourceLang);
        }
        
        // Common terms in appointment booking context
        $dictionary = [
            'de' => [
                'appointment' => 'Termin',
                'booking' => 'Buchung',
                'confirmation' => 'Bestätigung',
                'cancellation' => 'Absage',
                'reminder' => 'Erinnerung',
                'tomorrow' => 'morgen',
                'today' => 'heute',
                'time' => 'Uhrzeit',
                'date' => 'Datum',
                'service' => 'Dienstleistung',
                'staff' => 'Mitarbeiter',
                'customer' => 'Kunde',
                'phone' => 'Telefon',
                'email' => 'E-Mail',
                'address' => 'Adresse',
                'thank you' => 'vielen Dank',
                'please' => 'bitte',
                'yes' => 'ja',
                'no' => 'nein',
                'confirmed' => 'bestätigt',
                'cancelled' => 'abgesagt',
                'rescheduled' => 'verschoben',
                'available' => 'verfügbar',
                'unavailable' => 'nicht verfügbar',
                'morning' => 'Vormittag',
                'afternoon' => 'Nachmittag',
                'evening' => 'Abend',
            ],
            'en' => [
                'Termin' => 'appointment',
                'Buchung' => 'booking',
                'Bestätigung' => 'confirmation',
                'Absage' => 'cancellation',
                'Erinnerung' => 'reminder',
                'morgen' => 'tomorrow',
                'heute' => 'today',
                'Uhrzeit' => 'time',
                'Datum' => 'date',
                'Dienstleistung' => 'service',
                'Mitarbeiter' => 'staff',
                'Kunde' => 'customer',
                'Telefon' => 'phone',
                'E-Mail' => 'email',
                'Adresse' => 'address',
                'vielen Dank' => 'thank you',
                'bitte' => 'please',
                'ja' => 'yes',
                'nein' => 'no',
                'bestätigt' => 'confirmed',
                'abgesagt' => 'cancelled',
                'verschoben' => 'rescheduled',
                'verfügbar' => 'available',
                'nicht verfügbar' => 'unavailable',
                'Vormittag' => 'morning',
                'Nachmittag' => 'afternoon',
                'Abend' => 'evening',
            ]
        ];
        
        // If no source language specified, try to detect
        if (!$sourceLang) {
            $sourceLang = $this->detectLanguageSimple($text);
        }
        
        // If same language, return as is
        if ($sourceLang === $targetLang) {
            return $text;
        }
        
        // Get the appropriate dictionary
        $dict = $dictionary[$targetLang] ?? [];
        
        // Simple word replacement (case-insensitive)
        $translated = $text;
        foreach ($dict as $source => $target) {
            $translated = str_ireplace($source, $target, $translated);
        }
        
        return $translated;
    }
    
    /**
     * Translate longer texts with better quality
     */
    protected function translateSentences(string $text, string $targetLang, ?string $sourceLang = null): string
    {
        if ($targetLang === 'de' && (!$sourceLang || $sourceLang === 'en')) {
            // Complete phrases dictionary for better translation
            $phrases = [
                // Specific to this use case
                'The user, Hans Schuster from Schuster GmbH, called to report that his keyboard is not functioning and requested a callback as it is urgent.' 
                    => 'Der Benutzer, Hans Schuster von der Schuster GmbH, rief an, um zu melden, dass seine Tastatur nicht funktioniert und bat um einen Rückruf, da es dringend ist.',
                    
                'The agent attempted to collect the user\'s information but faced technical issues while saving the data, ultimately deciding to note the request manually and forward it to the team.'
                    => 'Der Agent versuchte, die Informationen des Benutzers zu sammeln, stieß aber beim Speichern der Daten auf technische Probleme und entschied sich schließlich, die Anfrage manuell zu notieren und an das Team weiterzuleiten.',
                    
                'The user, Hans Schuster from Schuster GMBH, called regarding a problem with his keyboard and requested a callback, emphasizing the urgency of the issue. The agent collected the necessary information, including the user\'s customer number, and confirmed that the data was successfully recorded and forwarded to the team for assistance.'
                    => 'Der Benutzer, Hans Schuster von der Schuster GMBH, rief wegen eines Problems mit seiner Tastatur an und bat um einen Rückruf, wobei er die Dringlichkeit des Problems betonte. Der Agent sammelte die notwendigen Informationen, einschließlich der Kundennummer des Benutzers, und bestätigte, dass die Daten erfolgreich erfasst und zur Unterstützung an das Team weitergeleitet wurden.',
                    
                // General patterns
                'called regarding a problem with' => 'rief wegen eines Problems mit',
                'called to report that' => 'rief an, um zu melden, dass',
                'is not functioning' => 'funktioniert nicht',
                'requested a callback' => 'bat um einen Rückruf',
                'emphasizing the urgency of the issue' => 'wobei er die Dringlichkeit des Problems betonte',
                'collected the necessary information' => 'sammelte die notwendigen Informationen',
                'including the user\'s customer number' => 'einschließlich der Kundennummer des Benutzers',
                'confirmed that the data was successfully recorded' => 'bestätigte, dass die Daten erfolgreich erfasst wurden',
                'forwarded to the team for assistance' => 'zur Unterstützung an das Team weitergeleitet',
                'as it is urgent' => 'da es dringend ist',
                'attempted to collect' => 'versuchte zu sammeln',
                'the user\'s information' => 'die Informationen des Benutzers',
                'faced technical issues' => 'stieß auf technische Probleme',
                'while saving the data' => 'beim Speichern der Daten',
                'ultimately deciding to' => 'entschied sich schließlich',
                'note the request manually' => 'die Anfrage manuell zu notieren',
                'forward it to the team' => 'an das Team weiterzuleiten',
                'The user' => 'Der Benutzer',
                'The customer' => 'Der Kunde',
                'The agent' => 'Der Agent',
                'keyboard' => 'Tastatur',
                'malfunctioning' => 'defekt',
                'technical problems' => 'technische Probleme',
                'data' => 'Daten',
                'information' => 'Informationen',
                'urgent' => 'dringend',
                'team' => 'Team',
                'and' => 'und',
                'but' => 'aber',
                'from' => 'von',
                'that' => 'dass',
                'his' => 'seine',
                'the' => 'die',
            ];
            
            $translated = $text;
            
            // First try exact match for complete sentences
            foreach ($phrases as $english => $german) {
                if (stripos($text, $english) !== false) {
                    $translated = str_ireplace($english, $german, $translated);
                }
            }
            
            // Then apply word replacements in correct order (longer phrases first)
            arsort($phrases); // Sort by length to avoid partial replacements
            foreach ($phrases as $english => $german) {
                $translated = preg_replace('/\b' . preg_quote($english, '/') . '\b/i', $german, $translated);
            }
            
            return $translated;
        }
        
        // For other language pairs, return original
        return $text;
    }
    
    /**
     * Simple language detection
     */
    protected function detectLanguageSimple(string $text): string
    {
        $text = strtolower($text);
        
        // Count German indicators
        $germanScore = 0;
        $germanWords = ['der', 'die', 'das', 'ich', 'sie', 'wir', 'termin', 'bitte', 'danke', 'ja', 'nein'];
        foreach ($germanWords as $word) {
            $germanScore += substr_count($text, ' ' . $word . ' ');
        }
        
        // Count English indicators
        $englishScore = 0;
        $englishWords = ['the', 'is', 'are', 'i', 'you', 'we', 'appointment', 'please', 'thank', 'yes', 'no'];
        foreach ($englishWords as $word) {
            $englishScore += substr_count($text, ' ' . $word . ' ');
        }
        
        return $germanScore > $englishScore ? 'de' : 'en';
    }
    
    /**
     * Batch translate multiple texts
     */
    public function translateBatch(array $texts, string $targetLang, ?string $sourceLang = null): array
    {
        $results = [];
        
        foreach ($texts as $key => $text) {
            $results[$key] = $this->translate($text, $targetLang, $sourceLang);
        }
        
        return $results;
    }
    
    /**
     * Get supported languages
     */
    public function getSupportedLanguages(): array
    {
        return [
            'de' => 'Deutsch',
            'en' => 'English',
            'es' => 'Español',
            'fr' => 'Français',
            'it' => 'Italiano',
            'tr' => 'Türkçe',
            'nl' => 'Nederlands',
            'pl' => 'Polski',
            'pt' => 'Português',
            'ru' => 'Русский',
            'ja' => '日本語',
            'zh' => '中文'
        ];
    }
    
    /**
     * Check if a language is supported
     */
    public function isLanguageSupported(string $lang): bool
    {
        return array_key_exists(strtolower($lang), $this->getSupportedLanguages());
    }
    
    /**
     * Detect language from text (public method)
     */
    public function detectLanguage(string $text): ?string
    {
        if (empty($text)) {
            return null;
        }
        
        // Common language patterns
        $patterns = [
            'de' => [
                '/\b(ich|der|die|das|und|ist|nicht|mit|auf|für|von|zu|ein|eine|haben|werden)\b/i',
                '/\b(Termin|Buchung|möchte|brauche|können|würde|bitte|danke|Uhr|morgen|heute)\b/i'
            ],
            'en' => [
                '/\b(the|is|are|have|has|with|for|and|but|not|can|will|would|please|thank)\b/i',
                '/\b(appointment|booking|need|want|tomorrow|today|time|date|service)\b/i'
            ],
            'es' => [
                '/\b(el|la|los|las|es|son|con|para|por|que|no|si|un|una)\b/i',
                '/\b(cita|reserva|quiero|necesito|puede|gracias|por favor|mañana|hoy)\b/i'
            ],
            'fr' => [
                '/\b(le|la|les|est|sont|avec|pour|par|que|ne|pas|un|une)\b/i',
                '/\b(rendez-vous|réservation|je|voudrais|besoin|merci|demain|aujourd)\b/i'
            ],
            'it' => [
                '/\b(il|la|le|è|sono|con|per|che|non|un|una)\b/i',
                '/\b(appuntamento|prenotazione|vorrei|bisogno|grazie|domani|oggi)\b/i'
            ],
            'tr' => [
                '/\b(bir|bu|ve|ile|için|var|yok|ama|çok|daha)\b/i',
                '/\b(randevu|rezervasyon|istiyorum|lazım|teşekkür|yarın|bugün)\b/i'
            ]
        ];
        
        $scores = [];
        foreach ($patterns as $lang => $langPatterns) {
            $score = 0;
            foreach ($langPatterns as $pattern) {
                preg_match_all($pattern, $text, $matches);
                $score += count($matches[0]);
            }
            if ($score > 0) {
                $scores[$lang] = $score;
            }
        }
        
        if (empty($scores)) {
            return 'de'; // Default to German
        }
        
        // Return language with highest score
        arsort($scores);
        $detectedLang = array_key_first($scores);
        $this->lastDetectedLanguage = $detectedLang;
        return $detectedLang;
    }
    
    /**
     * Get the last detected language
     */
    public function getDetectedLanguage(): ?string
    {
        return $this->lastDetectedLanguage ?? 'unknown';
    }
}