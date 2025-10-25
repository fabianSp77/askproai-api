<?php

namespace App\Services\Retell;

use App\Models\Service;
use App\Models\Company;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

/**
 * Service Selection Service
 *
 * Manages service selection and validation with branch isolation
 *
 * SECURITY: Protects against cross-branch/cross-company service access
 * - Validates company ownership
 * - Enforces branch isolation
 * - Validates Cal.com team ownership
 * - Caches results per request for performance
 */
class ServiceSelectionService implements ServiceSelectionInterface
{
    /**
     * Request-scoped cache for service lookups
     * Prevents repeated database queries within a single request
     */
    private array $requestCache = [];

    /**
     * Get default service for company and branch
     *
     * @param int $companyId Company ID
     * @param string|null $branchId Branch UUID (null = company-wide)
     * @return Service|null Default service or null if none found
     */
    public function getDefaultService(int $companyId, ?string $branchId = null): ?Service
    {
        $cacheKey = "default_service_{$companyId}_{$branchId}";
        if (isset($this->requestCache[$cacheKey])) {
            Log::debug('Service selection cache hit', ['key' => $cacheKey]);
            return $this->requestCache[$cacheKey];
        }

        $query = Service::where('company_id', $companyId)
            ->where('is_active', true)
            ->whereNotNull('calcom_event_type_id');

        // Apply branch filtering if provided
        if ($branchId) {
            $query->where(function($q) use ($branchId) {
                $q->where('branch_id', $branchId)
                  ->orWhereHas('branches', function($q2) use ($branchId) {
                      $q2->where('branches.id', $branchId);
                  })
                  ->orWhereNull('branch_id'); // Company-wide services
            });
        }

        // Try to find default service first
        $service = (clone $query)->where('is_default', true)->first();

        // Fallback to highest priority service
        if (!$service) {
            $service = $query
                ->orderBy('priority', 'asc')
                ->first();
        }

        // Validate team ownership if company has a team
        if ($service) {
            $company = Company::find($companyId);
            if ($company && $company->hasTeam() && !$company->ownsService($service->calcom_event_type_id)) {
                Log::warning('Service does not belong to company team', [
                    'service_id' => $service->id,
                    'company_id' => $companyId,
                    'team_id' => $company->calcom_team_id
                ]);
                return null;
            }
        }

        $this->requestCache[$cacheKey] = $service;

        Log::info('Service selected', [
            'service_id' => $service?->id,
            'service_name' => $service?->name,
            'company_id' => $companyId,
            'branch_id' => $branchId,
            'is_default' => $service?->is_default ?? false,
        ]);

        return $service;
    }

    /**
     * Get all available services for company and branch
     *
     * @param int $companyId Company ID
     * @param string|null $branchId Branch UUID (null = all company services)
     * @return Collection<Service> Available services, ordered by priority
     */
    public function getAvailableServices(int $companyId, ?string $branchId = null): Collection
    {
        $cacheKey = "available_services_{$companyId}_{$branchId}";
        if (isset($this->requestCache[$cacheKey])) {
            Log::debug('Service list cache hit', ['key' => $cacheKey]);
            return $this->requestCache[$cacheKey];
        }

        $query = Service::where('company_id', $companyId)
            ->where('is_active', true)
            ->whereNotNull('calcom_event_type_id');

        if ($branchId) {
            $query->where(function($q) use ($branchId) {
                $q->where('branch_id', $branchId)
                  ->orWhereHas('branches', function($q2) use ($branchId) {
                      $q2->where('branches.id', $branchId);
                  })
                  ->orWhereNull('branch_id');
            });
        }

        $services = $query->orderBy('priority', 'asc')->get();

        // Filter out services not owned by team
        $company = Company::find($companyId);
        if ($company && $company->hasTeam()) {
            $services = $services->filter(function($service) use ($company) {
                return $company->ownsService($service->calcom_event_type_id);
            });
        }

        $this->requestCache[$cacheKey] = $services;

        Log::info('Services loaded', [
            'company_id' => $companyId,
            'branch_id' => $branchId,
            'count' => $services->count(),
        ]);

        return $services;
    }

    /**
     * Validate that service belongs to company/branch
     *
     * @param int $serviceId Service ID to validate
     * @param int $companyId Company ID
     * @param string|null $branchId Branch UUID (null = skip branch check)
     * @return bool True if service is accessible, false otherwise
     */
    public function validateServiceAccess(int $serviceId, int $companyId, ?string $branchId = null): bool
    {
        $cacheKey = "validate_service_{$serviceId}_{$companyId}_{$branchId}";
        if (isset($this->requestCache[$cacheKey])) {
            return $this->requestCache[$cacheKey];
        }

        $service = Service::where('id', $serviceId)
            ->where('company_id', $companyId)
            ->where('is_active', true)
            ->first();

        if (!$service) {
            Log::warning('Service not found or not owned by company', [
                'service_id' => $serviceId,
                'company_id' => $companyId,
            ]);
            $this->requestCache[$cacheKey] = false;
            return false;
        }

        // Check branch access if branch is specified
        if ($branchId) {
            $hasBranchAccess = $service->branch_id === $branchId
                || $service->branches->contains('id', $branchId)
                || $service->branch_id === null; // Company-wide service

            if (!$hasBranchAccess) {
                Log::warning('Service not accessible to branch', [
                    'service_id' => $serviceId,
                    'company_id' => $companyId,
                    'branch_id' => $branchId,
                ]);
                $this->requestCache[$cacheKey] = false;
                return false;
            }
        }

        // Validate team ownership
        $company = Company::find($companyId);
        if ($company && $company->hasTeam() && !$company->ownsService($service->calcom_event_type_id)) {
            Log::warning('Service not owned by company team', [
                'service_id' => $serviceId,
                'company_id' => $companyId,
                'team_id' => $company->calcom_team_id,
            ]);
            $this->requestCache[$cacheKey] = false;
            return false;
        }

        $this->requestCache[$cacheKey] = true;
        return true;
    }

    /**
     * Find service by ID with company/branch validation
     *
     * @param int $serviceId Service ID
     * @param int $companyId Company ID
     * @param string|null $branchId Branch UUID (null = skip branch check)
     * @return Service|null Service if found and accessible, null otherwise
     */
    public function findServiceById(int $serviceId, int $companyId, ?string $branchId = null): ?Service
    {
        if (!$this->validateServiceAccess($serviceId, $companyId, $branchId)) {
            return null;
        }

        return Service::where('id', $serviceId)
            ->where('company_id', $companyId)
            ->first();
    }

    /**
     * Find service by name with fuzzy matching and synonym support
     *
     * Implements multi-strategy matching:
     * 1. Exact match (case-insensitive)
     * 2. Synonym match (from service_synonyms table)
     * 3. Fuzzy match (Levenshtein distance)
     *
     * @param string $serviceName Service name from user input (e.g., "Herrenhaarschnitt")
     * @param int $companyId Company ID
     * @param string|null $branchId Branch UUID (null = skip branch check)
     * @return Service|null Matched service or null if no match found
     */
    public function findServiceByName(string $serviceName, int $companyId, ?string $branchId = null): ?Service
    {
        $cacheKey = "service_by_name_{$companyId}_{$branchId}_" . md5($serviceName);
        if (isset($this->requestCache[$cacheKey])) {
            return $this->requestCache[$cacheKey];
        }

        // Strategy 1: Exact match (case-insensitive)
        $query = Service::where('company_id', $companyId)
            ->where('is_active', true)
            ->whereNotNull('calcom_event_type_id')
            ->where(function($q) use ($serviceName) {
                // FIX 2025-10-25: Use LIKE instead of ILIKE for MySQL/MariaDB compatibility
                // PostgreSQL: ILIKE is case-insensitive
                // MySQL/MariaDB: LIKE is case-insensitive by default (utf8mb4_unicode_ci collation)
                $q->where('name', 'LIKE', $serviceName)
                  ->orWhere('name', 'LIKE', '%' . $serviceName . '%')
                  ->orWhere('slug', '=', \Illuminate\Support\Str::slug($serviceName));
            });

        if ($branchId) {
            $query->where(function($q) use ($branchId) {
                $q->where('branch_id', $branchId)
                  ->orWhereHas('branches', fn($q2) => $q2->where('branches.id', $branchId))
                  ->orWhereNull('branch_id');
            });
        }

        $service = $query->first();

        if ($service) {
            Log::info('✅ Service matched by exact name', [
                'input_name' => $serviceName,
                'matched_service' => $service->name,
                'service_id' => $service->id,
                'strategy' => 'exact'
            ]);
            $this->requestCache[$cacheKey] = $service;
            return $service;
        }

        // Strategy 2: Synonym match
        if (\Illuminate\Support\Facades\Schema::hasTable('service_synonyms')) {
            $synonymMatch = \Illuminate\Support\Facades\DB::table('service_synonyms')
                ->join('services', 'service_synonyms.service_id', '=', 'services.id')
                ->where('services.company_id', $companyId)
                ->where('services.is_active', true)
                ->whereNotNull('services.calcom_event_type_id')
                ->where('service_synonyms.synonym', 'ILIKE', $serviceName)
                ->select('services.*', 'service_synonyms.confidence')
                ->orderBy('service_synonyms.confidence', 'desc')
                ->first();

            if ($synonymMatch) {
                $service = Service::find($synonymMatch->id);
                if ($service) {
                    Log::info('✅ Service matched by synonym', [
                        'input_name' => $serviceName,
                        'matched_service' => $service->name,
                        'service_id' => $service->id,
                        'confidence' => $synonymMatch->confidence,
                        'strategy' => 'synonym'
                    ]);
                    $this->requestCache[$cacheKey] = $service;
                    return $service;
                }
            }
        }

        // Strategy 3: Fuzzy matching (Levenshtein distance)
        $allServices = $this->getAvailableServices($companyId, $branchId);
        $bestMatch = null;
        $bestScore = 0;
        $minSimilarity = 0.75; // 75% similarity required

        foreach ($allServices as $candidate) {
            $similarity = $this->calculateSimilarity($serviceName, $candidate->name);

            if ($similarity > $minSimilarity && $similarity > $bestScore) {
                $bestMatch = $candidate;
                $bestScore = $similarity;
            }
        }

        if ($bestMatch) {
            Log::info('✅ Service matched by fuzzy matching', [
                'input_name' => $serviceName,
                'matched_service' => $bestMatch->name,
                'service_id' => $bestMatch->id,
                'similarity_score' => round($bestScore, 3),
                'strategy' => 'fuzzy'
            ]);
            $this->requestCache[$cacheKey] = $bestMatch;
            return $bestMatch;
        }

        // No match found
        Log::warning('❌ No service matched by name', [
            'input_name' => $serviceName,
            'company_id' => $companyId,
            'branch_id' => $branchId,
            'available_services' => $allServices->pluck('name')->toArray()
        ]);

        $this->requestCache[$cacheKey] = null;
        return null;
    }

    /**
     * Calculate similarity between two strings using Levenshtein distance
     *
     * @param string $str1 First string
     * @param string $str2 Second string
     * @return float Similarity score (0.0 to 1.0)
     */
    private function calculateSimilarity(string $str1, string $str2): float
    {
        $str1 = mb_strtolower($str1);
        $str2 = mb_strtolower($str2);

        $maxLen = max(mb_strlen($str1), mb_strlen($str2));

        if ($maxLen === 0) {
            return 1.0;
        }

        $distance = levenshtein($str1, $str2);
        $similarity = 1 - ($distance / $maxLen);

        return max(0.0, $similarity);
    }

    /**
     * Clear request cache (for testing)
     *
     * @return void
     */
    public function clearCache(): void
    {
        $this->requestCache = [];
    }
}