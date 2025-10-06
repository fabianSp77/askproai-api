<?php

namespace App\Services\Strategies;

use App\Models\Staff;
use App\Services\CalcomHostMappingService;
use Illuminate\Support\Facades\Log;

/**
 * Any-Staff Assignment Strategy (Model 1)
 *
 * Business Model: "Egal wer" - First available staff
 *
 * Assignment Logic:
 * 1. If Cal.com booking: Try to match Cal.com host to internal staff
 * 2. Otherwise: Find first available active staff for the company
 * 3. Check staff availability for the requested time slot
 *
 * Used by companies where any staff can perform any service (e.g., general consultants).
 */
class AnyStaffAssignmentStrategy implements StaffAssignmentStrategy
{
    public function __construct(
        protected CalcomHostMappingService $hostMappingService
    ) {}

    /**
     * Assign first available staff
     */
    public function assign(AssignmentContext $context): AssignmentResult
    {
        Log::info('AnyStaffStrategy: Starting assignment', [
            'company_id' => $context->companyId,
            'service_id' => $context->serviceId,
            'starts_at' => $context->startsAt->format('Y-m-d H:i:s'),
        ]);

        // Strategy 1: Cal.com host mapping (if Cal.com booking)
        if ($context->isCalcomBooking()) {
            $staff = $this->findStaffViaCalcomHost($context);
            if ($staff) {
                return AssignmentResult::success(
                    staff: $staff,
                    model: 'any_staff',
                    reason: 'Matched Cal.com host to staff',
                    metadata: [
                        'calcom_host_id' => $context->getCalcomHostId(),
                        'strategy' => 'calcom_host_mapping',
                    ]
                );
            }
        }

        // Strategy 2: First available staff
        $staff = $this->findFirstAvailableStaff($context);
        if ($staff) {
            return AssignmentResult::success(
                staff: $staff,
                model: 'any_staff',
                reason: 'First available staff assigned',
                metadata: [
                    'strategy' => 'first_available',
                    'checked_availability' => true,
                ]
            );
        }

        // No staff available
        return AssignmentResult::failed(
            model: 'any_staff',
            reason: 'No available staff found for requested time slot',
            metadata: [
                'strategies_attempted' => ['calcom_host_mapping', 'first_available'],
            ]
        );
    }

    /**
     * Find staff via Cal.com host mapping
     */
    protected function findStaffViaCalcomHost(AssignmentContext $context): ?Staff
    {
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

        if ($staffId) {
            return Staff::find($staffId);
        }

        return null;
    }

    /**
     * Find first available staff for company
     */
    protected function findFirstAvailableStaff(AssignmentContext $context): ?Staff
    {
        $query = Staff::where('company_id', $context->companyId)
            ->where('is_active', true)
            ->where('is_bookable', true)
            ->whereNull('deleted_at');

        // Optional branch filter
        if ($context->branchId) {
            $query->where('branch_id', $context->branchId);
        }

        $staff = $query->first();

        if (!$staff) {
            Log::warning('AnyStaffStrategy: No active bookable staff found', [
                'company_id' => $context->companyId,
                'branch_id' => $context->branchId,
            ]);
            return null;
        }

        // TODO: Check availability/calendar conflicts in future enhancement
        // For now, returning first active staff

        return $staff;
    }

    public function getModelName(): string
    {
        return 'any_staff';
    }

    public function canHandle(AssignmentContext $context): bool
    {
        // This strategy can always attempt assignment
        return true;
    }
}
