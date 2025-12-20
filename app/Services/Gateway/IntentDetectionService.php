<?php

namespace App\Services\Gateway;

use App\Models\ServiceCaseCategory;
use Illuminate\Support\Facades\Log;

/**
 * Intent Detection Service
 *
 * Analyzes caller utterances to detect intent and route calls appropriately.
 * Uses keyword-based matching with German language support for both
 * appointment booking and service desk contexts.
 *
 * ARCHITECTURE:
 * - Keyword-based intent detection (appointment vs service_desk)
 * - Category-specific keyword augmentation from database
 * - Confidence scoring based on keyword matches
 * - Configurable threshold for hybrid mode routing
 *
 * USE CASES:
 * - Friseur/Wellness: "termin", "haarschnitt", "färben", "buchen"
 * - IT Service: "problem", "fehler", "drucker", "störung"
 * - General Service: "frage", "information", "beschwerde"
 *
 * @package App\Services\Gateway
 */
class IntentDetectionService
{
    /**
     * Appointment-related keywords (German)
     *
     * Context: Hair salons, wellness centers, general appointments
     *
     * @var array<string>
     */
    private array $appointmentKeywords = [
        'termin',
        'buchen',
        'reservieren',
        'zeitfenster',
        'verfügbar',
        'frei',
        'morgen',
        'heute',
        'nächste woche',
        'uhrzeit',
        'haarschnitt',
        'färben',
        'schneiden',
        'behandlung',
        'massage',
        'wellness',
        'kosmetik',
        'maniküre',
        'pediküre',
        'dauerwelle',
        'strähnchen',
        'föhnen',
        'styling',
        'termin vereinbaren',
        'termin ausmachen',
        'termin haben',
        'möchte einen termin',
        'brauch einen termin',
        'termin bekommen',
    ];

    /**
     * Service-desk-related keywords (German)
     *
     * Context: IT support, general inquiries, complaints
     *
     * @var array<string>
     */
    private array $serviceKeywords = [
        'problem',
        'fehler',
        'funktioniert nicht',
        'kaputt',
        'hilfe',
        'support',
        'störung',
        'ausfall',
        'dringend',
        'reklamation',
        'beschwerde',
        'frage',
        'information',
        'bestellung',
        'lieferung',
        'rechnung',
        'drucker',
        'computer',
        'laptop',
        'netzwerk',
        'internet',
        'email',
        'passwort',
        'zugang',
        'login',
        'geht nicht',
        'klappt nicht',
        'habe ein problem',
        'brauche hilfe',
        'nicht erreichbar',
        'absturz',
        'langsam',
    ];

    /**
     * Detect intent from user utterance
     *
     * Analyzes the text using keyword matching and returns intent classification
     * with confidence score.
     *
     * @param string $utterance User's spoken text
     * @param int|null $companyId For category-specific keywords
     * @return array{intent: string, confidence: float, detected_keywords: array, explanation: string}
     */
    public function detectIntent(string $utterance, ?int $companyId = null): array
    {
        $normalized = $this->normalizeText($utterance);

        $appointmentScore = $this->scoreKeywords($normalized, $this->appointmentKeywords);
        $serviceScore = $this->scoreKeywords($normalized, $this->serviceKeywords);

        // Add company-specific category keywords
        if ($companyId) {
            $categoryKeywords = $this->getCategoryKeywords($companyId);
            // Weight category keywords higher (1.5x) as they're company-specific
            $serviceScore += $this->scoreKeywords($normalized, $categoryKeywords) * 1.5;
        }

        $totalScore = $appointmentScore + $serviceScore;

        if ($totalScore === 0.0) {
            return [
                'intent' => 'unknown',
                'confidence' => 0.0,
                'detected_keywords' => [],
                'explanation' => 'Keine Keywords erkannt',
            ];
        }

        $appointmentConfidence = $appointmentScore / max($totalScore, 0.01);
        $serviceConfidence = $serviceScore / max($totalScore, 0.01);

        if ($appointmentConfidence > $serviceConfidence) {
            $intent = 'appointment';
            $confidence = $appointmentConfidence;
            $keywords = $this->findMatchedKeywords($normalized, $this->appointmentKeywords);
        } else {
            $intent = 'service_desk';
            $confidence = $serviceConfidence;
            $keywords = $this->findMatchedKeywords($normalized, $this->serviceKeywords);
        }

        Log::debug('[IntentDetection] Result', [
            'utterance' => substr($utterance, 0, 100),
            'intent' => $intent,
            'confidence' => round($confidence, 3),
            'appointment_score' => round($appointmentScore, 3),
            'service_score' => round($serviceScore, 3),
        ]);

        return [
            'intent' => $intent,
            'confidence' => round($confidence, 3),
            'detected_keywords' => $keywords,
            'explanation' => "Erkannte Keywords: " . implode(', ', $keywords),
        ];
    }

    /**
     * Determine gateway mode for hybrid routing
     *
     * Uses intent detection with confidence threshold to decide routing.
     * Falls back to configured mode if confidence is too low.
     *
     * @param string $utterance User's initial speech
     * @param int|null $companyId Company context for category keywords
     * @param float|null $confidenceThreshold Minimum confidence to route (default from config)
     * @return string 'appointment' | 'service_desk' | 'fallback'
     */
    public function determineMode(string $utterance, ?int $companyId = null, ?float $confidenceThreshold = null): string
    {
        $threshold = $confidenceThreshold ?? config('gateway.hybrid.intent_confidence_threshold', 0.75);
        $fallbackMode = config('gateway.hybrid.fallback_mode', 'appointment');

        $result = $this->detectIntent($utterance, $companyId);

        if ($result['confidence'] < $threshold) {
            Log::info('[IntentDetection] Low confidence, using fallback', [
                'confidence' => $result['confidence'],
                'threshold' => $threshold,
                'fallback' => $fallbackMode,
            ]);
            return $fallbackMode;
        }

        return $result['intent'];
    }

    /**
     * Normalize text for keyword matching
     *
     * Converts to lowercase, removes punctuation, and normalizes whitespace
     *
     * @param string $text Raw text
     * @return string Normalized text
     */
    private function normalizeText(string $text): string
    {
        // Lowercase, remove punctuation, trim
        $text = mb_strtolower($text);
        $text = preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $text);
        $text = preg_replace('/\s+/', ' ', $text);
        return trim($text);
    }

    /**
     * Score text against keyword list
     *
     * Longer keywords get higher scores to prefer more specific matches.
     *
     * @param string $text Normalized text
     * @param array $keywords Keyword list
     * @return float Score
     */
    private function scoreKeywords(string $text, array $keywords): float
    {
        $score = 0.0;

        foreach ($keywords as $keyword) {
            if (str_contains($text, mb_strtolower($keyword))) {
                // Longer keywords get higher score (more specific)
                $score += strlen($keyword) / 10;
            }
        }

        return $score;
    }

    /**
     * Find which keywords were matched in text
     *
     * @param string $text Normalized text
     * @param array $keywords Keyword list
     * @return array<string> Matched keywords
     */
    private function findMatchedKeywords(string $text, array $keywords): array
    {
        $matched = [];

        foreach ($keywords as $keyword) {
            if (str_contains($text, mb_strtolower($keyword))) {
                $matched[] = $keyword;
            }
        }

        return $matched;
    }

    /**
     * Get category keywords for company
     *
     * Retrieves AI keywords from active service case categories
     * for company-specific intent detection.
     *
     * @param int $companyId Company ID
     * @return array<string> Category keywords
     */
    private function getCategoryKeywords(int $companyId): array
    {
        $categories = ServiceCaseCategory::where('company_id', $companyId)
            ->where('is_active', true)
            ->whereNotNull('intent_keywords')
            ->pluck('intent_keywords')
            ->flatten()
            ->toArray();

        return $categories;
    }

    /**
     * Add custom appointment keywords at runtime
     *
     * Useful for company-specific customization
     *
     * @param array<string> $keywords Additional keywords
     */
    public function addAppointmentKeywords(array $keywords): void
    {
        $this->appointmentKeywords = array_merge($this->appointmentKeywords, $keywords);
    }

    /**
     * Add custom service keywords at runtime
     *
     * Useful for company-specific customization
     *
     * @param array<string> $keywords Additional keywords
     */
    public function addServiceKeywords(array $keywords): void
    {
        $this->serviceKeywords = array_merge($this->serviceKeywords, $keywords);
    }
}
