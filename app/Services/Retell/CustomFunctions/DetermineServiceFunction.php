<?php

namespace App\Services\Retell\CustomFunctions;

use App\Models\Service;
use App\Models\Branch;
use App\Models\ServiceCategory;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

/**
 * Retell.ai Custom Function zum Matching von Service-Namen
 * Findet den passenden Service basierend auf natürlicher Sprache
 */
class DetermineServiceFunction
{
    /**
     * Function Definition für Retell.ai
     */
    public static function getDefinition(): array
    {
        return [
            'name' => 'determine_service',
            'description' => 'Bestimmt den passenden Service basierend auf der Kundenbeschreibung',
            'parameters' => [
                'type' => 'object',
                'properties' => [
                    'service_description' => [
                        'type' => 'string',
                        'description' => 'Beschreibung des gewünschten Services vom Kunden'
                    ],
                    'branch_id' => [
                        'type' => 'string',
                        'description' => 'ID der Filiale'
                    ],
                    'company_id' => [
                        'type' => 'string',
                        'description' => 'ID des Unternehmens'
                    ],
                    'customer_gender' => [
                        'type' => 'string',
                        'description' => 'Geschlecht des Kunden (m/w/d) falls relevant'
                    ],
                    'duration_hint' => [
                        'type' => 'string',
                        'description' => 'Hinweise zur gewünschten Dauer'
                    ]
                ],
                'required' => ['service_description', 'company_id']
            ]
        ];
    }

    /**
     * Führt das Service-Matching aus
     */
    public function execute(array $parameters): array
    {
        Log::info('DetermineServiceFunction::execute', [
            'parameters' => $parameters
        ]);

        try {
            $description = mb_strtolower($parameters['service_description']);
            $companyId = $parameters['company_id'];
            $branchId = $parameters['branch_id'] ?? null;
            $customerGender = $parameters['customer_gender'] ?? null;
            $durationHint = $parameters['duration_hint'] ?? null;

            // Cache-Key für Performance
            $cacheKey = "service_match_{$companyId}_{$branchId}_" . md5($description);
            
            // Prüfe Cache
            $cachedResult = Cache::get($cacheKey);
            if ($cachedResult) {
                Log::info('Using cached service match', ['cache_key' => $cacheKey]);
                return $cachedResult;
            }

            // Lade alle verfügbaren Services
            $servicesQuery = Service::where('company_id', $companyId)
                ->where('is_active', true);

            if ($branchId) {
                // Wenn Branch angegeben, nur Services dieser Branch
                $servicesQuery->whereHas('branches', function($query) use ($branchId) {
                    $query->where('branches.id', $branchId);
                });
            }

            $services = $servicesQuery->with(['category', 'tags'])->get();

            if ($services->isEmpty()) {
                return [
                    'success' => false,
                    'error' => 'Keine aktiven Services gefunden',
                    'suggestions' => []
                ];
            }

            // Führe Matching durch
            $matches = $this->performMatching($description, $services, $customerGender, $durationHint);

            // Sortiere nach Score
            $matches = collect($matches)->sortByDesc('score')->values();

            // Erstelle Response
            $response = $this->buildResponse($matches, $description);

            // Cache für 1 Stunde
            Cache::put($cacheKey, $response, 3600);

            return $response;

        } catch (\Exception $e) {
            Log::error('DetermineServiceFunction error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'error' => 'Fehler beim Service-Matching',
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Führt das eigentliche Matching durch
     */
    protected function performMatching(string $description, $services, ?string $customerGender, ?string $durationHint): array
    {
        $matches = [];

        foreach ($services as $service) {
            $score = 0.0;
            $matchReasons = [];

            // 1. Exakter Name Match
            if (stripos($service->name, $description) !== false || stripos($description, $service->name) !== false) {
                $score += 0.5;
                $matchReasons[] = 'Name Match';
            }

            // 2. Wort-für-Wort Matching
            $serviceWords = $this->extractKeywords($service->name);
            $descriptionWords = $this->extractKeywords($description);
            
            $commonWords = array_intersect($serviceWords, $descriptionWords);
            if (!empty($commonWords)) {
                $score += 0.3 * (count($commonWords) / max(count($serviceWords), count($descriptionWords)));
                $matchReasons[] = 'Keyword Match: ' . implode(', ', $commonWords);
            }

            // 3. Beschreibungs-Match
            if ($service->description) {
                $descriptionMatch = $this->calculateTextSimilarity($description, mb_strtolower($service->description));
                if ($descriptionMatch > 0.3) {
                    $score += $descriptionMatch * 0.2;
                    $matchReasons[] = 'Description Match';
                }
            }

            // 4. Kategorie-Match
            if ($service->category) {
                $categoryScore = $this->matchCategory($description, $service->category);
                if ($categoryScore > 0) {
                    $score += $categoryScore * 0.2;
                    $matchReasons[] = 'Category: ' . $service->category->name;
                }
            }

            // 5. Tag-Match
            if ($service->tags && count($service->tags) > 0) {
                $tagScore = $this->matchTags($description, $service->tags);
                if ($tagScore > 0) {
                    $score += $tagScore * 0.1;
                    $matchReasons[] = 'Tags Match';
                }
            }

            // 6. Gender-spezifisches Matching
            if ($customerGender) {
                $genderScore = $this->matchGender($service, $customerGender);
                $score *= $genderScore; // Multiplier, kann Score reduzieren
                if ($genderScore < 1.0) {
                    $matchReasons[] = 'Gender Mismatch';
                }
            }

            // 7. Dauer-Match
            if ($durationHint) {
                $durationScore = $this->matchDuration($service, $durationHint);
                if ($durationScore > 0.5) {
                    $score += 0.1;
                    $matchReasons[] = 'Duration Match';
                }
            }

            // 8. Synonym und Varianten Match
            $synonymScore = $this->matchSynonyms($description, $service);
            if ($synonymScore > 0) {
                $score += $synonymScore * 0.3;
                $matchReasons[] = 'Synonym Match';
            }

            // Nur Services mit Score > 0 aufnehmen
            if ($score > 0) {
                $matches[] = [
                    'service' => $service,
                    'score' => $score,
                    'match_reasons' => $matchReasons,
                    'confidence' => $this->scoreToConfidence($score)
                ];
            }
        }

        return $matches;
    }

    /**
     * Extrahiert Keywords aus Text
     */
    protected function extractKeywords(string $text): array
    {
        // Entferne Sonderzeichen
        $text = preg_replace('/[^\w\s]/u', ' ', $text);
        
        // In Wörter aufteilen
        $words = preg_split('/\s+/u', mb_strtolower($text), -1, PREG_SPLIT_NO_EMPTY);
        
        // Filtere Stoppwörter
        $stopwords = ['der', 'die', 'das', 'und', 'oder', 'mit', 'für', 'bei', 'im', 'am', 'zum', 'zur'];
        $words = array_diff($words, $stopwords);
        
        // Mindestlänge 2 Zeichen
        $words = array_filter($words, fn($w) => mb_strlen($w) > 1);
        
        return array_values($words);
    }

    /**
     * Berechnet Text-Ähnlichkeit
     */
    protected function calculateTextSimilarity(string $text1, string $text2): float
    {
        $words1 = $this->extractKeywords($text1);
        $words2 = $this->extractKeywords($text2);
        
        if (empty($words1) || empty($words2)) {
            return 0.0;
        }
        
        $common = array_intersect($words1, $words2);
        $union = array_unique(array_merge($words1, $words2));
        
        return count($common) / count($union);
    }

    /**
     * Matched gegen Kategorie
     */
    protected function matchCategory(string $description, ?ServiceCategory $category): float
    {
        if (!$category) {
            return 0.0;
        }

        $categoryKeywords = $this->getCategoryKeywords($category->slug ?? $category->name);
        $descriptionWords = $this->extractKeywords($description);
        
        $matches = array_intersect($categoryKeywords, $descriptionWords);
        
        return count($matches) > 0 ? 0.5 + (0.5 * count($matches) / count($categoryKeywords)) : 0.0;
    }

    /**
     * Liefert Keywords für Kategorien
     */
    protected function getCategoryKeywords(string $category): array
    {
        $keywords = [
            'haircut' => ['schneiden', 'schnitt', 'kürzen', 'stufen', 'spitzen'],
            'coloring' => ['färben', 'farbe', 'tönen', 'blondieren', 'strähnen', 'balayage'],
            'styling' => ['styling', 'föhnen', 'glätten', 'locken', 'wellen', 'frisur'],
            'treatment' => ['behandlung', 'pflege', 'kur', 'maske', 'repair'],
            'mens' => ['herren', 'männer', 'bart', 'rasur'],
            'kids' => ['kinder', 'kids', 'jungen', 'mädchen'],
            'wellness' => ['wellness', 'entspannung', 'massage', 'verwöhnen'],
            'nails' => ['nägel', 'maniküre', 'pediküre', 'lackieren', 'gel'],
            'cosmetics' => ['kosmetik', 'gesicht', 'haut', 'peeling', 'maske']
        ];
        
        return $keywords[mb_strtolower($category)] ?? [];
    }

    /**
     * Matched gegen Tags
     */
    protected function matchTags(string $description, array $tags): float
    {
        if (empty($tags)) {
            return 0.0;
        }

        $descriptionWords = $this->extractKeywords($description);
        $matches = 0;
        
        foreach ($tags as $tag) {
            $tagWords = $this->extractKeywords($tag);
            if (array_intersect($tagWords, $descriptionWords)) {
                $matches++;
            }
        }
        
        return $matches / count($tags);
    }

    /**
     * Gender-spezifisches Matching
     */
    protected function matchGender(Service $service, string $customerGender): float
    {
        $serviceName = mb_strtolower($service->name);
        
        // Explizit männliche Services
        if (preg_match('/\b(herren|männer|men|bart)\b/i', $serviceName)) {
            return $customerGender === 'm' ? 1.2 : 0.3;
        }
        
        // Explizit weibliche Services
        if (preg_match('/\b(damen|frauen|ladies|women)\b/i', $serviceName)) {
            return $customerGender === 'w' ? 1.2 : 0.3;
        }
        
        // Kinder-Services
        if (preg_match('/\b(kinder|kids|jungen|mädchen)\b/i', $serviceName)) {
            return 1.0; // Neutral für Kinder
        }
        
        // Neutrale Services
        return 1.0;
    }

    /**
     * Dauer-Matching
     */
    protected function matchDuration(Service $service, string $durationHint): float
    {
        if (!$service->duration) {
            return 0.5; // Neutral wenn keine Dauer angegeben
        }

        $hint = mb_strtolower($durationHint);
        $duration = $service->duration;
        
        // Kurze Termine
        if (preg_match('/\b(kurz|schnell|fix|15|zwanzig|20)\b/i', $hint)) {
            return $duration <= 30 ? 1.0 : 0.3;
        }
        
        // Mittlere Termine
        if (preg_match('/\b(normal|standard|30|dreißig|45)\b/i', $hint)) {
            return $duration > 30 && $duration <= 60 ? 1.0 : 0.5;
        }
        
        // Lange Termine
        if (preg_match('/\b(lang|ausführlich|gründlich|stunde|2\s*stunden)\b/i', $hint)) {
            return $duration >= 60 ? 1.0 : 0.3;
        }
        
        return 0.5; // Neutral
    }

    /**
     * Synonym-Matching
     */
    protected function matchSynonyms(string $description, Service $service): float
    {
        $synonymGroups = [
            ['schneiden', 'schnitt', 'kürzen', 'stutzen', 'trimmen'],
            ['färben', 'colorieren', 'tönen', 'farbe'],
            ['waschen', 'haarwäsche', 'shampoo'],
            ['föhnen', 'fönen', 'trocknen', 'blow', 'dry'],
            ['dauerwelle', 'locken', 'wellen', 'curls'],
            ['glätten', 'straighten', 'glatt'],
            ['hochstecken', 'hochsteckfrisur', 'updo'],
            ['extensions', 'verlängerung', 'haarverlängerung'],
            ['bart', 'barber', 'rasur', 'shave'],
            ['augenbrauen', 'brauen', 'brows'],
            ['wimpern', 'lashes', 'wimper'],
        ];

        $descWords = $this->extractKeywords($description);
        $serviceWords = $this->extractKeywords($service->name . ' ' . ($service->description ?? ''));
        
        $score = 0.0;
        
        foreach ($synonymGroups as $group) {
            $descMatches = array_intersect($descWords, $group);
            $serviceMatches = array_intersect($serviceWords, $group);
            
            if (!empty($descMatches) && !empty($serviceMatches)) {
                $score += 0.5;
            }
        }
        
        return min(1.0, $score);
    }

    /**
     * Konvertiert Score zu Confidence-Level
     */
    protected function scoreToConfidence(float $score): string
    {
        if ($score >= 0.8) return 'high';
        if ($score >= 0.5) return 'medium';
        if ($score >= 0.3) return 'low';
        return 'very_low';
    }

    /**
     * Baut die Response auf
     */
    protected function buildResponse($matches, string $originalDescription): array
    {
        if ($matches->isEmpty()) {
            return [
                'success' => true,
                'found' => false,
                'message' => 'Kein passender Service gefunden',
                'original_description' => $originalDescription,
                'suggestions' => []
            ];
        }

        // Nimm die Top 3 Matches
        $topMatches = $matches->take(3);
        
        // Wenn der beste Match sehr gut ist (>0.8), gib nur diesen zurück
        $bestMatch = $topMatches->first();
        if ($bestMatch['score'] > 0.8) {
            return [
                'success' => true,
                'found' => true,
                'confident' => true,
                'service' => $this->formatService($bestMatch['service']),
                'match_quality' => $bestMatch['confidence'],
                'match_reasons' => $bestMatch['match_reasons'],
                'alternatives' => []
            ];
        }

        // Ansonsten gib mehrere Optionen
        return [
            'success' => true,
            'found' => true,
            'confident' => false,
            'service' => $this->formatService($bestMatch['service']),
            'match_quality' => $bestMatch['confidence'],
            'match_reasons' => $bestMatch['match_reasons'],
            'alternatives' => $topMatches->slice(1)->map(function($match) {
                return [
                    'service' => $this->formatService($match['service']),
                    'match_quality' => $match['confidence'],
                    'match_reasons' => $match['match_reasons']
                ];
            })->values()->toArray(),
            'clarification_needed' => $bestMatch['score'] < 0.5,
            'clarification_prompt' => $this->generateClarificationPrompt($topMatches)
        ];
    }

    /**
     * Formatiert Service für Response
     */
    protected function formatService(Service $service): array
    {
        return [
            'id' => $service->id,
            'name' => $service->name,
            'description' => $service->description,
            'duration' => $service->duration,
            'price' => $service->price,
            'price_display' => number_format($service->price ?? 0, 2, ',', '.') . ' €',
            'category' => $service->category?->name,
            'requires_consultation' => $service->requires_consultation ?? false,
            'is_addon' => $service->is_addon ?? false,
            'gender_specific' => $this->determineGenderSpecific($service)
        ];
    }

    /**
     * Bestimmt ob Service geschlechtsspezifisch ist
     */
    protected function determineGenderSpecific(Service $service): ?string
    {
        $name = mb_strtolower($service->name);
        
        if (preg_match('/\b(herren|männer|men|bart)\b/i', $name)) {
            return 'male';
        }
        
        if (preg_match('/\b(damen|frauen|ladies|women)\b/i', $name)) {
            return 'female';
        }
        
        return null;
    }

    /**
     * Generiert Rückfrage-Prompt
     */
    protected function generateClarificationPrompt($matches): string
    {
        if ($matches->count() === 0) {
            return "Können Sie den gewünschten Service genauer beschreiben?";
        }

        $services = $matches->take(3)->pluck('service.name')->toArray();
        
        return "Meinen Sie vielleicht " . implode(', ', array_slice($services, 0, -1)) . 
               " oder " . end($services) . "?";
    }
}