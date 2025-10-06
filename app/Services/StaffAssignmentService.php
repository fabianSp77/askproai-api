<?php

namespace App\Services;

use App\Models\CompanyAssignmentConfig;
use App\Services\Strategies\AssignmentContext;
use App\Services\Strategies\AssignmentResult;
use App\Services\Strategies\AnyStaffAssignmentStrategy;
use App\Services\Strategies\ServiceStaffAssignmentStrategy;
use App\Services\Strategies\StaffAssignmentStrategy;
use Illuminate\Support\Facades\Log;

/**
 * Staff Assignment Service
 *
 * Orchestrates staff assignment across multiple business models:
 * - Model 1 (any_staff): First available staff
 * - Model 2 (service_staff): Only qualified staff with service restrictions
 *
 * Responsibilities:
 * 1. Load company configuration to determine business model
 * 2. Select appropriate strategy (any_staff or service_staff)
 * 3. Attempt assignment with primary model
 * 4. Fallback to alternate model if configured and primary fails
 * 5. Return assignment result with audit metadata
 *
 * Design Pattern: Strategy Pattern + Dependency Injection
 */
class StaffAssignmentService
{
    public function __construct(
        protected AnyStaffAssignmentStrategy $anyStaffStrategy,
        protected ServiceStaffAssignmentStrategy $serviceStaffStrategy
    ) {}

    /**
     * Assign staff for an appointment
     *
     * @param AssignmentContext $context Appointment context
     * @return AssignmentResult Assignment result with staff or failure reason
     */
    public function assignStaff(AssignmentContext $context): AssignmentResult
    {
        Log::info('StaffAssignmentService: Starting assignment', [
            'company_id' => $context->companyId,
            'service_id' => $context->serviceId,
            'starts_at' => $context->startsAt->format('Y-m-d H:i:s'),
        ]);

        // 1. Load company configuration
        $config = CompanyAssignmentConfig::getActiveForCompany($context->companyId);

        if (!$config) {
            Log::warning('StaffAssignmentService: No active config for company', [
                'company_id' => $context->companyId,
            ]);

            // Default to any_staff if no config (backward compatibility)
            return $this->anyStaffStrategy->assign($context);
        }

        // 2. Select primary strategy
        $primaryStrategy = $this->getStrategy($config->assignment_model);

        Log::info('StaffAssignmentService: Using primary model', [
            'model' => $config->assignment_model,
            'has_fallback' => $config->fallback_model !== null,
        ]);

        // 3. Attempt primary assignment
        $result = $primaryStrategy->assign($context);

        if ($result->isSuccessful()) {
            Log::info('StaffAssignmentService: Primary assignment successful', [
                'model' => $config->assignment_model,
                'staff_id' => $result->getStaffId(),
            ]);
            return $result;
        }

        // 4. Try fallback if configured
        if ($config->fallback_model) {
            Log::info('StaffAssignmentService: Primary failed, trying fallback', [
                'primary_model' => $config->assignment_model,
                'fallback_model' => $config->fallback_model,
                'primary_reason' => $result->reason,
            ]);

            $fallbackStrategy = $this->getStrategy($config->fallback_model);
            $fallbackResult = $fallbackStrategy->assign($context);

            if ($fallbackResult->isSuccessful()) {
                Log::info('StaffAssignmentService: Fallback assignment successful', [
                    'fallback_model' => $config->fallback_model,
                    'staff_id' => $fallbackResult->getStaffId(),
                ]);

                // Mark as fallback in result
                return AssignmentResult::success(
                    staff: $fallbackResult->staff,
                    model: $fallbackResult->model,
                    reason: "Fallback assignment: {$fallbackResult->reason}",
                    wasFallback: true,
                    metadata: array_merge($fallbackResult->metadata, [
                        'primary_model' => $config->assignment_model,
                        'primary_failure_reason' => $result->reason,
                    ])
                );
            }

            Log::warning('StaffAssignmentService: Both primary and fallback failed', [
                'primary_model' => $config->assignment_model,
                'fallback_model' => $config->fallback_model,
            ]);

            // Return combined failure
            return AssignmentResult::failed(
                model: $config->assignment_model,
                reason: "Primary and fallback failed: {$result->reason} | {$fallbackResult->reason}",
                metadata: [
                    'primary_failure' => $result->reason,
                    'fallback_failure' => $fallbackResult->reason,
                    'fallback_model' => $config->fallback_model,
                ]
            );
        }

        // No fallback configured, return primary failure
        Log::warning('StaffAssignmentService: Assignment failed, no fallback', [
            'model' => $config->assignment_model,
            'reason' => $result->reason,
        ]);

        return $result;
    }

    /**
     * Get strategy instance for model name
     *
     * @param string $modelName One of: any_staff, service_staff
     * @return StaffAssignmentStrategy
     */
    protected function getStrategy(string $modelName): StaffAssignmentStrategy
    {
        return match ($modelName) {
            'any_staff' => $this->anyStaffStrategy,
            'service_staff' => $this->serviceStaffStrategy,
            default => throw new \InvalidArgumentException("Unknown assignment model: {$modelName}"),
        };
    }

    /**
     * Quick helper: Assign staff and return just the staff ID
     *
     * @param AssignmentContext $context
     * @return string|null Staff ID or null if assignment failed
     */
    public function assignStaffId(AssignmentContext $context): ?string
    {
        $result = $this->assignStaff($context);
        return $result->getStaffId();
    }

    /**
     * Get company configuration
     *
     * @param int $companyId
     * @return CompanyAssignmentConfig|null
     */
    public function getCompanyConfig(int $companyId): ?CompanyAssignmentConfig
    {
        return CompanyAssignmentConfig::getActiveForCompany($companyId);
    }
}
