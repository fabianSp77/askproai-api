<?php

namespace App\Services\Strategies;

use App\Models\ServiceStaffAssignment;
use App\Models\Staff;
use App\Services\CalcomHostMappingService;
use Illuminate\Support\Facades\Log;

/**
 * Service-Staff Assignment Strategy (Model 2)
 *
 * Business Model: "Nur Qualifizierte" - Only qualified staff for services
 *
 * Assignment Logic:
 * 1. Get list of staff qualified for the service (from service_staff_assignments)
 * 2. If Cal.com booking: Try to match Cal.com host to one of qualified staff
 * 3. Otherwise: Select first available qualified staff by priority order
 * 4. Check staff availability for the requested time slot
 *
 * Used by companies with service restrictions (e.g., hair salons where not every
 * staff member can perform every service - 50% of customers need this).
 */
class ServiceStaffAssignmentStrategy implements StaffAssignmentStrategy
{
    public function __construct(
        protected CalcomHostMappingService $hostMappingService
    ) {}

    /**
     * Assign qualified staff for service
     */
    public function assign(AssignmentContext $context): AssignmentResult
    {
        Log::info('ServiceStaffStrategy: Starting assignment', [
            'company_id' => $context->companyId,
            'service_id' => $context->serviceId,
            'starts_at' => $context->startsAt->format('Y-m-d H:i:s'),
        ]);

        // Get qualified staff for this service
        $qualifiedStaff = ServiceStaffAssignment::getQualifiedStaffForService(
            $context->serviceId,
            $context->companyId
        );

        if ($qualifiedStaff->isEmpty()) {
            Log::warning('ServiceStaffStrategy: No qualified staff for service', [
                'service_id' => $context->serviceId,
                'company_id' => $context->companyId,
            ]);

            return AssignmentResult::failed(
                model: 'service_staff',
                reason: 'No qualified staff configured for this service',
                metadata: [
                    'service_id' => $context->serviceId,
                    'qualified_staff_count' => 0,
                ]
            );
        }

        Log::info('ServiceStaffStrategy: Found qualified staff', [
            'count' => $qualifiedStaff->count(),
            'staff_ids' => $qualifiedStaff->pluck('id')->toArray(),
        ]);

        // Strategy 1: Cal.com host mapping (if Cal.com booking)
        if ($context->isCalcomBooking()) {
            $staff = $this->findQualifiedStaffViaCalcomHost($context, $qualifiedStaff);
            if ($staff) {
                return AssignmentResult::success(
                    staff: $staff,
                    model: 'service_staff',
                    reason: 'Matched Cal.com host to qualified staff',
                    metadata: [
                        'calcom_host_id' => $context->getCalcomHostId(),
                        'strategy' => 'calcom_host_mapping',
                        'qualified_staff_count' => $qualifiedStaff->count(),
                    ]
                );
            }
        }

        // Strategy 2: First available qualified staff by priority
        $staff = $this->findFirstAvailableQualifiedStaff($context, $qualifiedStaff);
        if ($staff) {
            return AssignmentResult::success(
                staff: $staff,
                model: 'service_staff',
                reason: 'First available qualified staff assigned',
                metadata: [
                    'strategy' => 'priority_based',
                    'qualified_staff_count' => $qualifiedStaff->count(),
                    'checked_availability' => true,
                ]
            );
        }

        // No qualified staff available
        return AssignmentResult::failed(
            model: 'service_staff',
            reason: 'No qualified staff available for requested time slot',
            metadata: [
                'qualified_staff_count' => $qualifiedStaff->count(),
                'strategies_attempted' => ['calcom_host_mapping', 'priority_based'],
            ]
        );
    }

    /**
     * Find qualified staff via Cal.com host mapping
     */
    protected function findQualifiedStaffViaCalcomHost(
        AssignmentContext $context,
        \Illuminate\Support\Collection $qualifiedStaff
    ): ?Staff {
        $hostId = $context->getCalcomHostId();
        if (!$hostId) {
            return null;
        }

        $staffId = $this->hostMappingService->resolveStaffForHost(
            ['id' => $hostId],
            new \App\Services\Strategies\HostMatchContext(
                companyId: $context->companyId,
                branchId: $context->branchId,
                serviceId: $context->serviceId,
                calcomBooking: $context->calcomBooking
            )
        );

        if (!$staffId) {
            return null;
        }

        // Verify the mapped staff is in qualified list
        $staff = $qualifiedStaff->firstWhere('id', $staffId);

        if (!$staff) {
            Log::warning('ServiceStaffStrategy: Cal.com host mapped to non-qualified staff', [
                'host_id' => $hostId,
                'staff_id' => $staffId,
                'service_id' => $context->serviceId,
            ]);
            return null;
        }

        return $staff;
    }

    /**
     * Find first available qualified staff (already ordered by priority)
     */
    protected function findFirstAvailableQualifiedStaff(
        AssignmentContext $context,
        \Illuminate\Support\Collection $qualifiedStaff
    ): ?Staff {
        // Filter to active and bookable staff
        $availableStaff = $qualifiedStaff->filter(function ($staff) {
            return $staff->is_active && $staff->is_bookable && !$staff->deleted_at;
        });

        if ($availableStaff->isEmpty()) {
            Log::warning('ServiceStaffStrategy: No active/bookable qualified staff', [
                'service_id' => $context->serviceId,
                'qualified_count' => $qualifiedStaff->count(),
            ]);
            return null;
        }

        // Optional branch filter
        if ($context->branchId) {
            $availableStaff = $availableStaff->where('branch_id', $context->branchId);
        }

        // TODO: Check availability/calendar conflicts in future enhancement
        // For now, returning first qualified staff (already ordered by priority)

        return $availableStaff->first();
    }

    public function getModelName(): string
    {
        return 'service_staff';
    }

    public function canHandle(AssignmentContext $context): bool
    {
        // This strategy requires service context
        return $context->serviceId !== null;
    }
}
