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
                ->orderByRaw('CASE WHEN name LIKE "%Beratung%" THEN 0 WHEN name LIKE "%30 Minuten%" THEN 1 ELSE 2 END')
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
     * Clear request cache (for testing)
     *
     * @return void
     */
    public function clearCache(): void
    {
        $this->requestCache = [];
    }
}