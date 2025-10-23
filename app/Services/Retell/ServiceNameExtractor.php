<?php

namespace App\Services\Retell;

use App\Models\Service;
use Illuminate\Support\Facades\Log;

class ServiceNameExtractor
{
    /**
     * Confidence threshold for fuzzy matching (0-100)
     * Below this threshold, fall back to default service
     */
    private const CONFIDENCE_THRESHOLD = 60;

    /**
     * Maximum Levenshtein distance for matching
     */
    private const MAX_DISTANCE = 5;

    /**
     * Common German variations and abbreviations for services
     */
    private const GERMAN_VARIATIONS = [
        'damenschnitt' => ['damen', 'frauen', 'frauenhaarschnitt', 'damenhaarschnitt'],
        'herrenschnitt' => ['herren', 'männer', 'herrenhaarschnitt', 'männerhaarschnitt'],
        'färben' => ['farbe', 'färbung', 'coloration', 'tönung', 'tönen'],
        'bart' => ['bartschnitt', 'barttrimmen', 'bart trimmen', 'bartpflege'],
        'waschen' => ['haarwäsche', 'kopfwäsche', 'shampoo'],
        'föhnen' => ['föhn', 'blow dry', 'trocknen'],
        'styling' => ['style', 'frisur'],
        'dauerwelle' => ['dauerwellen', 'perm'],
        'strähnen' => ['strähnchen', 'highlights'],
        'extensions' => ['extension', 'haarverlängerung'],
    ];

    /**
     * Extract and match service name from user speech
     *
     * @param string $userInput Raw voice input from user
     * @param int $companyId Company context for service lookup
     * @param int|null $branchId Optional branch context
     * @return array{service: Service|null, confidence: int, matched_text: string|null}
     */
    public function extractService(string $userInput, int $companyId, ?int $branchId = null): array
    {
        $normalizedInput = $this->normalizeInput($userInput);

        Log::info('🔍 Service extraction started', [
            'raw_input' => $userInput,
            'normalized_input' => $normalizedInput,
            'company_id' => $companyId,
            'branch_id' => $branchId,
        ]);

        // Get all active services for the company
        $services = $this->getAvailableServices($companyId, $branchId);

        if ($services->isEmpty()) {
            Log::warning('⚠️ No services found for company', [
                'company_id' => $companyId,
                'branch_id' => $branchId,
            ]);
            return [
                'service' => null,
                'confidence' => 0,
                'matched_text' => null,
            ];
        }

        // Find best match
        $bestMatch = $this->findBestMatch($normalizedInput, $services);

        Log::info('✅ Service extraction complete', [
            'service_id' => $bestMatch['service']?->id,
            'service_name' => $bestMatch['service']?->name,
            'confidence' => $bestMatch['confidence'],
            'matched_text' => $bestMatch['matched_text'],
            'threshold' => self::CONFIDENCE_THRESHOLD,
        ]);

        // If confidence is too low, return null to trigger service selection
        if ($bestMatch['confidence'] < self::CONFIDENCE_THRESHOLD) {
            Log::warning('⚠️ Confidence below threshold, service selection required', [
                'confidence' => $bestMatch['confidence'],
                'threshold' => self::CONFIDENCE_THRESHOLD,
            ]);
            return [
                'service' => null,
                'confidence' => $bestMatch['confidence'],
                'matched_text' => $bestMatch['matched_text'],
            ];
        }

        return $bestMatch;
    }

    /**
     * Normalize user input for matching
     */
    private function normalizeInput(string $input): string
    {
        // Convert to lowercase
        $normalized = mb_strtolower($input, 'UTF-8');

        // Remove common filler words
        $fillerWords = ['ich', 'möchte', 'gerne', 'bitte', 'einen', 'eine', 'ein', 'der', 'die', 'das'];
        foreach ($fillerWords as $word) {
            $normalized = preg_replace('/\b' . $word . '\b/', '', $normalized);
        }

        // Remove extra whitespace
        $normalized = preg_replace('/\s+/', ' ', $normalized);
        $normalized = trim($normalized);

        return $normalized;
    }

    /**
     * Get available services for company/branch
     */
    private function getAvailableServices(int $companyId, ?int $branchId = null)
    {
        $query = Service::where('company_id', $companyId)
            ->where('is_active', true);

        if ($branchId) {
            $query->where(function ($q) use ($branchId) {
                $q->where('branch_id', $branchId)
                    ->orWhereNull('branch_id');
            });
        }

        return $query->get();
    }

    /**
     * Find best matching service using fuzzy matching
     */
    private function findBestMatch(string $input, $services): array
    {
        $bestMatch = [
            'service' => null,
            'confidence' => 0,
            'matched_text' => null,
        ];

        foreach ($services as $service) {
            $matchResult = $this->calculateMatch($input, $service);

            if ($matchResult['confidence'] > $bestMatch['confidence']) {
                $bestMatch = [
                    'service' => $service,
                    'confidence' => $matchResult['confidence'],
                    'matched_text' => $matchResult['matched_text'],
                ];
            }
        }

        return $bestMatch;
    }

    /**
     * Calculate match confidence for a service
     */
    private function calculateMatch(string $input, Service $service): array
    {
        $serviceName = mb_strtolower($service->name, 'UTF-8');
        $displayName = $service->display_name ? mb_strtolower($service->display_name, 'UTF-8') : null;
        $calcomName = $service->calcom_name ? mb_strtolower($service->calcom_name, 'UTF-8') : null;

        $matches = [];

        // Check exact match first (100% confidence)
        if (str_contains($input, $serviceName)) {
            return ['confidence' => 100, 'matched_text' => $serviceName];
        }
        if ($displayName && str_contains($input, $displayName)) {
            return ['confidence' => 100, 'matched_text' => $displayName];
        }
        if ($calcomName && str_contains($input, $calcomName)) {
            return ['confidence' => 95, 'matched_text' => $calcomName];
        }

        // Check variations
        foreach (self::GERMAN_VARIATIONS as $baseWord => $variations) {
            if (str_contains($serviceName, $baseWord) || str_contains($displayName ?? '', $baseWord)) {
                foreach ($variations as $variation) {
                    if (str_contains($input, $variation)) {
                        $matches[] = ['confidence' => 90, 'matched_text' => $variation];
                    }
                }
            }
        }

        // Fuzzy match using Levenshtein distance
        $levenshteinDistance = levenshtein($input, $serviceName);
        if ($levenshteinDistance <= self::MAX_DISTANCE) {
            $confidence = (int) ((1 - ($levenshteinDistance / max(strlen($input), strlen($serviceName)))) * 80);
            $matches[] = ['confidence' => $confidence, 'matched_text' => $serviceName];
        }

        if ($displayName) {
            $levenshteinDistance = levenshtein($input, $displayName);
            if ($levenshteinDistance <= self::MAX_DISTANCE) {
                $confidence = (int) ((1 - ($levenshteinDistance / max(strlen($input), strlen($displayName)))) * 80);
                $matches[] = ['confidence' => $confidence, 'matched_text' => $displayName];
            }
        }

        // Check for partial word matches
        $inputWords = explode(' ', $input);
        $serviceWords = explode(' ', $serviceName);

        foreach ($inputWords as $inputWord) {
            foreach ($serviceWords as $serviceWord) {
                if (strlen($inputWord) >= 4 && strlen($serviceWord) >= 4) {
                    $similarity = 0;
                    similar_text($inputWord, $serviceWord, $similarity);
                    if ($similarity >= 75) {
                        $matches[] = [
                            'confidence' => (int) $similarity,
                            'matched_text' => $serviceWord
                        ];
                    }
                }
            }
        }

        // Return best match or zero confidence
        if (empty($matches)) {
            return ['confidence' => 0, 'matched_text' => null];
        }

        usort($matches, fn($a, $b) => $b['confidence'] <=> $a['confidence']);
        return $matches[0];
    }

    /**
     * Get list of all services for voice AI to present to user
     *
     * @param int $companyId Company context
     * @param int|null $branchId Optional branch context
     * @return array Array of service names and IDs formatted for Retell
     */
    public function getServiceList(int $companyId, ?int $branchId = null): array
    {
        $services = $this->getAvailableServices($companyId, $branchId);

        return $services->map(function ($service) {
            return [
                'id' => $service->id,
                'name' => $service->display_name ?? $service->name,
                'duration' => $service->duration_minutes,
                'price' => $service->price,
                'description' => $service->description,
            ];
        })->toArray();
    }
}
