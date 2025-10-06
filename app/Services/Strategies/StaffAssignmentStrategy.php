<?php

namespace App\Services\Strategies;

/**
 * Staff Assignment Strategy Interface
 *
 * Defines the contract for different staff assignment business models:
 * - AnyStaffAssignmentStrategy: First available staff (Model 1)
 * - ServiceStaffAssignmentStrategy: Only qualified staff (Model 2)
 *
 * Each strategy implements its own logic for finding and assigning staff
 * based on company configuration and service requirements.
 */
interface StaffAssignmentStrategy
{
    /**
     * Attempt to assign staff for an appointment
     *
     * @param AssignmentContext $context Appointment and service context
     * @return AssignmentResult Result with staff or failure reason
     */
    public function assign(AssignmentContext $context): AssignmentResult;

    /**
     * Get strategy identifier for audit trail
     *
     * @return string One of: any_staff, service_staff, manual
     */
    public function getModelName(): string;

    /**
     * Check if this strategy can handle the given context
     *
     * @param AssignmentContext $context
     * @return bool True if strategy is applicable
     */
    public function canHandle(AssignmentContext $context): bool;
}
