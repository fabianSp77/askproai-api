<?php

namespace App\Services\Retell;

use App\Models\Branch;
use App\Models\Call;
use App\Services\Policy\BranchPolicyEnforcer;
use Illuminate\Support\Facades\Log;

/**
 * ServiceInformationService
 *
 * ✅ Phase 3: Retell function handler for service_information requests
 *
 * Provides information about services offered by a branch:
 * - Service name, description
 * - Duration (minutes)
 * - Price (if configured)
 * - Availability (active services only)
 *
 * Policy Integration:
 * - Checks POLICY_TYPE_SERVICE_INFORMATION before revealing service details
 * - Anonymous callers allowed by default (public information)
 * - Branch can restrict via policy configuration
 */
class ServiceInformationService
{
    public function __construct(
        private BranchPolicyEnforcer $policyEnforcer
    ) {}

    /**
     * Get service information for branch
     *
     * @param Branch $branch Branch to get services for
     * @param Call $call Call record for policy check
     * @param array $parameters Optional filter parameters (service_name, category)
     * @return array Response array for Retell
     */
    public function getServiceInformation(Branch $branch, Call $call, array $parameters = []): array
    {
        Log::info('📋 Service Information Request', [
            'branch_id' => $branch->id,
            'call_id' => $call->id,
            'parameters' => $parameters,
        ]);

        // 1. Policy Check
        $policyCheck = $this->policyEnforcer->isOperationAllowed(
            $branch,
            $call,
            'service_info'
        );

        if (!$policyCheck['allowed']) {
            Log::warning('🛑 Service information policy denied', [
                'branch_id' => $branch->id,
                'reason' => $policyCheck['reason'],
            ]);

            return [
                'success' => false,
                'error' => $policyCheck['message'] ?? 'Service-Informationen sind derzeit nicht verfügbar.',
                'reason' => $policyCheck['reason'],
            ];
        }

        // 2. Fetch active services
        $servicesQuery = $branch->activeServices();

        // Optional filtering by service name
        if (isset($parameters['service_name']) && !empty($parameters['service_name'])) {
            // FIX 2025-11-25: Use LIKE instead of ILIKE for MySQL/MariaDB compatibility
            $servicesQuery->where('services.name', 'LIKE', '%' . $parameters['service_name'] . '%');
        }

        $services = $servicesQuery->get();

        if ($services->isEmpty()) {
            Log::info('ℹ️ No services found', [
                'branch_id' => $branch->id,
                'filter' => $parameters['service_name'] ?? null,
            ]);

            return [
                'success' => true,
                'data' => [
                    'services' => [],
                    'message' => 'Derzeit sind keine Services verfügbar.',
                ],
            ];
        }

        // 3. Format services for response
        $formattedServices = $services->map(function ($service) {
            return [
                'id' => $service->id,
                'name' => $service->name,
                'description' => $service->description ?? 'Keine Beschreibung verfügbar',
                'duration_minutes' => $service->pivot->duration_override_minutes ?? $service->default_duration_minutes,
                'price' => $service->pivot->price_override ?? $service->price ?? null,
                'price_formatted' => $this->formatPrice($service->pivot->price_override ?? $service->price),
            ];
        })->values()->toArray();

        Log::info('✅ Service information retrieved', [
            'branch_id' => $branch->id,
            'service_count' => count($formattedServices),
        ]);

        return [
            'success' => true,
            'data' => [
                'services' => $formattedServices,
                'count' => count($formattedServices),
                'branch_name' => $branch->name,
            ],
        ];
    }

    /**
     * Format price for speech output
     *
     * @param float|null $price Price in euros
     * @return string Formatted price string
     */
    private function formatPrice(?float $price): string
    {
        if ($price === null) {
            return 'Preis auf Anfrage';
        }

        return number_format($price, 2, ',', '.') . ' Euro';
    }
}
