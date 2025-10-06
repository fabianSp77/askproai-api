<?php

namespace App\Services\Retell;

use App\Models\Service;
use Illuminate\Support\Collection;

/**
 * Service Selection Interface
 *
 * Responsible for selecting and validating services based on company and branch context
 *
 * SECURITY: Prevents cross-branch service access
 * - Validates service belongs to company
 * - Filters services by branch isolation
 * - Validates team ownership (Cal.com)
 */
interface ServiceSelectionInterface
{
    /**
     * Get default service for company and branch
     *
     * Priority order:
     * 1. Service marked as is_default=true
     * 2. Highest priority service
     * 3. First service matching name patterns (Beratung, 30 Minuten)
     *
     * @param int $companyId Company ID
     * @param string|null $branchId Branch UUID (null = company-wide)
     * @return Service|null Default service or null if none found
     */
    public function getDefaultService(int $companyId, ?string $branchId = null): ?Service;

    /**
     * Get all available services for company and branch
     *
     * Filters by:
     * - Company ownership
     * - Branch access (branch-specific OR company-wide)
     * - Active status
     * - Cal.com integration (has calcom_event_type_id)
     * - Team ownership (if company has team)
     *
     * @param int $companyId Company ID
     * @param string|null $branchId Branch UUID (null = all company services)
     * @return Collection<Service> Available services, ordered by priority
     */
    public function getAvailableServices(int $companyId, ?string $branchId = null): Collection;

    /**
     * Validate that service belongs to company/branch
     *
     * Security check to prevent cross-company/cross-branch access
     *
     * @param int $serviceId Service ID to validate
     * @param int $companyId Company ID
     * @param string|null $branchId Branch UUID (null = skip branch check)
     * @return bool True if service is accessible, false otherwise
     */
    public function validateServiceAccess(int $serviceId, int $companyId, ?string $branchId = null): bool;

    /**
     * Find service by ID with company/branch validation
     *
     * Convenience method that combines lookup and validation
     *
     * @param int $serviceId Service ID
     * @param int $companyId Company ID
     * @param string|null $branchId Branch UUID (null = skip branch check)
     * @return Service|null Service if found and accessible, null otherwise
     */
    public function findServiceById(int $serviceId, int $companyId, ?string $branchId = null): ?Service;

    /**
     * Clear request cache (for testing)
     *
     * @return void
     */
    public function clearCache(): void;
}