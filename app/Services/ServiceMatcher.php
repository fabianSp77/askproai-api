<?php

namespace App\Services;

use App\Models\Service;
use App\Models\Company;
use App\Models\Branch;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ServiceMatcher
{
    /**
     * Keywords mapped to company types
     */
    private array $keywordRules = [
        'friseur' => ['friseur', 'haare', 'schnitt', 'styling', 'waschen', 'färben', 'coloration', 'strähnen', 'dauerwelle', 'barbershop', 'coiffeur', 'salon'],
        'beauty' => ['beauty', 'kosmetik', 'nails', 'nail', 'wimpern', 'augenbrauen', 'makeup', 'make-up', 'gesichtsbehandlung', 'peeling'],
        'physio' => ['physio', 'therapie', 'massage', 'krankengymnastik', 'reha', 'rehabilitation', 'behandlung', 'mobilisation'],
        'medical' => ['arzt', 'doctor', 'medizin', 'praxis', 'sprechstunde', 'untersuchung', 'behandlung', 'diagnose'],
        'zahnarzt' => ['zahn', 'dental', 'zahnarzt', 'zahnreinigung', 'prophylaxe', 'kieferorthopädie', 'implant'],
        'tierarzt' => ['tier', 'veterinär', 'haustier', 'hund', 'katze', 'impfung', 'veterinary'],
        'legal' => ['legal', 'anwalt', 'rechts', 'jura', 'beratung', 'vertrag', 'gericht', 'mandant'],
        'consulting' => ['beratung', 'consulting', 'meeting', 'termin', 'gespräch', 'besprechung', 'konferenz'],
    ];

    /**
     * Suggest companies for a service based on name matching
     *
     * @return Collection Array of suggestions with confidence scores
     */
    public function suggestCompanies(Service $service): Collection
    {
        $suggestions = collect();
        $companies = Company::all();

        foreach ($companies as $company) {
            $confidence = $this->calculateConfidence($service, $company);

            if ($confidence > 0) {
                $suggestions->push([
                    'company' => $company,
                    'confidence' => $confidence,
                    'matched_keywords' => $this->getMatchedKeywords($service, $company),
                    'reasoning' => $this->generateReasoning($service, $company, $confidence)
                ]);
            }
        }

        // Sort by confidence descending
        return $suggestions->sortByDesc('confidence')->values();
    }

    /**
     * Automatically assign a company to a service if confidence is high enough
     */
    public function autoAssign(Service $service, float $minConfidence = 80.0): ?Company
    {
        $suggestions = $this->suggestCompanies($service);

        if ($suggestions->isEmpty()) {
            return null;
        }

        $bestMatch = $suggestions->first();

        if ($bestMatch['confidence'] >= $minConfidence) {
            // Auto-assign with metadata
            $service->update([
                'company_id' => $bestMatch['company']->id,
                'assignment_method' => 'auto',
                'assignment_confidence' => $bestMatch['confidence'],
                'assignment_notes' => $bestMatch['reasoning'],
                'assignment_date' => now()
            ]);

            Log::info('[ServiceMatcher] Auto-assigned service', [
                'service_id' => $service->id,
                'service_name' => $service->name,
                'company_id' => $bestMatch['company']->id,
                'company_name' => $bestMatch['company']->name,
                'confidence' => $bestMatch['confidence']
            ]);

            return $bestMatch['company'];
        }

        return null;
    }

    /**
     * Calculate confidence score for service-company match
     */
    private function calculateConfidence(Service $service, Company $company): float
    {
        $confidence = 0.0;
        $factors = [];

        // Normalize strings for comparison
        $serviceName = Str::lower($service->name);
        $serviceSlug = Str::lower($service->slug ?? '');
        $serviceDesc = Str::lower($service->description ?? '');
        $companyName = Str::lower($company->name);

        // 1. Special pattern matching for "Testtermin: [Type] Website" (80 points for exact match)
        if (preg_match('/testtermin:\s*([^\s]+)/i', $serviceName, $matches)) {
            $testType = Str::lower($matches[1]);

            // Exact match with company type
            if (Str::contains($companyName, $testType)) {
                $confidence += 80;
                $factors[] = 'test_pattern_exact_match';
            }
            // Synonym matching
            elseif ($this->matchSynonyms($testType, $companyName)) {
                $confidence += 70;
                $factors[] = 'test_pattern_synonym_match';
            }
        }

        // 2. Direct name matching (50 points)
        $companyWords = explode(' ', $companyName);
        foreach ($companyWords as $word) {
            if (strlen($word) > 3 && Str::contains($serviceName, $word)) {
                $confidence += 30;
                $factors[] = 'company_word_in_service';
                break;
            }
        }

        // 3. Strong keyword matching for hair/beauty services (60 points)
        if ($this->isHairService($serviceName) && $this->isHairCompany($companyName)) {
            $confidence += 60;
            $factors[] = 'hair_service_match';
        } elseif ($this->isBeautyService($serviceName) && $this->isBeautyCompany($companyName)) {
            $confidence += 60;
            $factors[] = 'beauty_service_match';
        }

        // 4. Keyword matching (30 points max)
        $keywordScore = $this->calculateKeywordScore($service, $company);
        $confidence += $keywordScore;
        if ($keywordScore > 0) {
            $factors[] = "keyword_match_{$keywordScore}";
        }

        // 5. Service type matching (20 points)
        $typeScore = $this->calculateTypeScore($service, $company);
        $confidence += $typeScore;
        if ($typeScore > 0) {
            $factors[] = "type_match_{$typeScore}";
        }

        // 6. Existing association bonus (10 points)
        $existingCount = Service::where('company_id', $company->id)
            ->where('id', '!=', $service->id)
            ->where('assignment_confidence', '>=', 70)
            ->count();
        if ($existingCount > 0) {
            $confidence += min(10, $existingCount * 2);
            $factors[] = "existing_association_{$existingCount}";
        }

        return min(100, $confidence); // Cap at 100
    }

    /**
     * Calculate keyword matching score
     */
    private function calculateKeywordScore(Service $service, Company $company): float
    {
        $score = 0.0;
        $searchText = Str::lower(implode(' ', [
            $service->name,
            $service->slug ?? '',
            $service->description ?? ''
        ]));

        $companyName = Str::lower($company->name);

        foreach ($this->keywordRules as $category => $keywords) {
            $matchedKeywords = 0;

            foreach ($keywords as $keyword) {
                if (Str::contains($searchText, $keyword)) {
                    $matchedKeywords++;

                    // Check if company name also contains this keyword
                    if (Str::contains($companyName, $keyword)) {
                        $score += 10;
                    } else if (Str::contains($companyName, $category)) {
                        $score += 5;
                    }
                }
            }
        }

        return min(30, $score); // Cap at 30 points
    }

    /**
     * Calculate service type matching score
     */
    private function calculateTypeScore(Service $service, Company $company): float
    {
        $score = 0.0;

        // Check service category against company type
        $serviceCategory = Str::lower($service->category ?? '');
        $companyName = Str::lower($company->name);

        // Map common service categories to company types
        $categoryMappings = [
            'haircut' => ['friseur', 'salon', 'barbershop'],
            'beauty' => ['beauty', 'kosmetik', 'salon'],
            'medical' => ['praxis', 'arzt', 'klinik', 'zahnarzt'],
            'consultation' => ['beratung', 'consulting', 'legal', 'office'],
        ];

        foreach ($categoryMappings as $category => $companyTypes) {
            if (Str::contains($serviceCategory, $category)) {
                foreach ($companyTypes as $type) {
                    if (Str::contains($companyName, $type)) {
                        $score += 20;
                        break 2;
                    }
                }
            }
        }

        // Check for specific service patterns
        if (Str::contains($service->name, ['damen', 'herren']) &&
            Str::contains($companyName, ['friseur', 'salon', 'beauty'])) {
            $score += 10;
        }

        return min(20, $score); // Cap at 20 points
    }

    /**
     * Get matched keywords between service and company
     */
    private function getMatchedKeywords(Service $service, Company $company): array
    {
        $matched = [];
        $searchText = Str::lower(implode(' ', [
            $service->name,
            $service->description ?? ''
        ]));
        $companyName = Str::lower($company->name);

        foreach ($this->keywordRules as $category => $keywords) {
            foreach ($keywords as $keyword) {
                if (Str::contains($searchText, $keyword) &&
                    (Str::contains($companyName, $keyword) || Str::contains($companyName, $category))) {
                    $matched[] = $keyword;
                }
            }
        }

        return array_unique($matched);
    }

    /**
     * Generate human-readable reasoning for the match
     */
    private function generateReasoning(Service $service, Company $company, float $confidence): string
    {
        $reasons = [];

        if ($confidence >= 80) {
            $reasons[] = "High confidence match ({$confidence}%)";
        } elseif ($confidence >= 60) {
            $reasons[] = "Good match ({$confidence}%)";
        } elseif ($confidence >= 40) {
            $reasons[] = "Possible match ({$confidence}%)";
        } else {
            $reasons[] = "Low confidence match ({$confidence}%)";
        }

        $keywords = $this->getMatchedKeywords($service, $company);
        if (!empty($keywords)) {
            $reasons[] = "Keywords: " . implode(', ', $keywords);
        }

        // Check for specific patterns
        if (Str::contains(Str::lower($service->name), Str::lower($company->name))) {
            $reasons[] = "Company name found in service name";
        }

        if (preg_match('/testtermin/i', $service->name)) {
            $reasons[] = "Test appointment pattern detected";
        }

        return implode('. ', $reasons);
    }

    /**
     * Batch process all unassigned services
     */
    public function batchAutoAssign(float $minConfidence = 80.0): array
    {
        $results = [
            'assigned' => [],
            'suggested' => [],
            'unmatched' => []
        ];

        // Get all services that need assignment
        $services = Service::whereNull('company_id')
            ->orWhere('assignment_method', null)
            ->get();

        foreach ($services as $service) {
            $company = $this->autoAssign($service, $minConfidence);

            if ($company) {
                $results['assigned'][] = [
                    'service' => $service->name,
                    'company' => $company->name,
                    'confidence' => $service->assignment_confidence
                ];
            } else {
                $suggestions = $this->suggestCompanies($service);

                if ($suggestions->isNotEmpty()) {
                    $results['suggested'][] = [
                        'service' => $service->name,
                        'suggestions' => $suggestions->take(3)->map(fn($s) => [
                            'company' => $s['company']->name,
                            'confidence' => $s['confidence']
                        ])
                    ];
                } else {
                    $results['unmatched'][] = $service->name;
                }
            }
        }

        return $results;
    }

    /**
     * Get assignment statistics
     */
    public function getStatistics(): array
    {
        return [
            'total_services' => Service::count(),
            'assigned' => Service::whereNotNull('company_id')->count(),
            'unassigned' => Service::whereNull('company_id')->count(),
            'auto_assigned' => Service::where('assignment_method', 'auto')->count(),
            'manual_assigned' => Service::where('assignment_method', 'manual')->count(),
            'high_confidence' => Service::where('assignment_confidence', '>=', 80)->count(),
            'low_confidence' => Service::where('assignment_confidence', '<', 50)->count(),
        ];
    }

    /**
     * Check if service is hair-related
     */
    private function isHairService(string $serviceName): bool
    {
        $hairKeywords = ['haarschnitt', 'hair', 'waschen', 'schneiden', 'styling', 'damen', 'herren', 'föhnen', 'färben'];
        foreach ($hairKeywords as $keyword) {
            if (Str::contains($serviceName, $keyword)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Check if company is hair-related
     */
    private function isHairCompany(string $companyName): bool
    {
        $hairCompanyKeywords = ['friseur', 'frisör', 'salon', 'hair', 'barbershop', 'coiffeur'];
        foreach ($hairCompanyKeywords as $keyword) {
            if (Str::contains($companyName, $keyword)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Check if service is beauty-related
     */
    private function isBeautyService(string $serviceName): bool
    {
        $beautyKeywords = ['beauty', 'kosmetik', 'nail', 'wimpern', 'augenbrauen', 'makeup', 'gesichtsbehandlung'];
        foreach ($beautyKeywords as $keyword) {
            if (Str::contains($serviceName, $keyword)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Check if company is beauty-related
     */
    private function isBeautyCompany(string $companyName): bool
    {
        $beautyCompanyKeywords = ['beauty', 'kosmetik', 'salon', 'schönheit', 'wellness'];
        foreach ($beautyCompanyKeywords as $keyword) {
            if (Str::contains($companyName, $keyword)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Match synonyms for test patterns
     */
    private function matchSynonyms(string $testType, string $companyName): bool
    {
        $synonyms = [
            'friseur' => ['friseur', 'frisör', 'hair', 'salon', 'barbershop'],
            'physio' => ['physio', 'therapie', 'physiotherapie', 'krankengymnastik'],
            'tierarzt' => ['tierarzt', 'veterinär', 'tierklinik', 'veterinary'],
            'zahnarzt' => ['zahnarzt', 'dental', 'zahn'],
        ];

        foreach ($synonyms as $type => $words) {
            if (Str::contains($testType, $type)) {
                foreach ($words as $word) {
                    if (Str::contains($companyName, $word)) {
                        return true;
                    }
                }
            }
        }
        return false;
    }
}