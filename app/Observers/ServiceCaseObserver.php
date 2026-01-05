<?php

namespace App\Observers;

use App\Models\ServiceCase;
use App\Models\ServiceCaseActivityLog;
use Illuminate\Support\Facades\Log;

/**
 * ServiceCaseObserver
 *
 * RESPONSIBILITIES:
 * - Audit trail: Creates immutable activity logs for all case changes
 * - Error isolation: Logging failures NEVER block case operations
 *
 * TRACKED CHANGES:
 * - created: Initial case creation
 * - status: Status transitions (new → open → pending → resolved → closed)
 * - priority: Priority escalation/de-escalation
 * - urgency: Urgency level changes
 * - assigned_to: Staff assignment changes
 * - assigned_group_id: Group assignment changes
 * - category_id: Category reassignment
 * - customer_id: Customer linking
 * - output_status: Delivery status changes
 * - enrichment_status: Enrichment completion
 * - deleted/restored: Soft delete handling
 *
 * CRITICAL: All logging is wrapped in try-catch to prevent blocking!
 */
class ServiceCaseObserver
{
    /**
     * Fields that should trigger activity logs when changed
     */
    protected array $trackedFields = [
        'status' => ServiceCaseActivityLog::ACTION_STATUS_CHANGED,
        'priority' => ServiceCaseActivityLog::ACTION_PRIORITY_CHANGED,
        'urgency' => ServiceCaseActivityLog::ACTION_URGENCY_CHANGED,
        'assigned_to' => ServiceCaseActivityLog::ACTION_ASSIGNED,
        'assigned_group_id' => ServiceCaseActivityLog::ACTION_GROUP_ASSIGNED,
        'category_id' => ServiceCaseActivityLog::ACTION_CATEGORY_CHANGED,
        'customer_id' => ServiceCaseActivityLog::ACTION_CUSTOMER_LINKED,
        'output_status' => ServiceCaseActivityLog::ACTION_OUTPUT_STATUS_CHANGED,
        'enrichment_status' => ServiceCaseActivityLog::ACTION_ENRICHMENT_COMPLETED,
    ];

    /**
     * Handle the ServiceCase "creating" event.
     * Called BEFORE the case is saved to the database.
     *
     * PURPOSE: Calculate SLA due dates based on category configuration
     * CRITICAL: Failures must NOT block case creation!
     */
    public function creating(ServiceCase $case): void
    {
        try {
            // Calculate SLA due dates if company has SLA tracking enabled
            $case->calculateSlaDueDates();

            Log::debug('[ServiceCaseObserver] SLA dates calculated for new case', [
                'company_id' => $case->company_id,
                'category_id' => $case->category_id,
                'sla_response_due_at' => $case->sla_response_due_at,
                'sla_resolution_due_at' => $case->sla_resolution_due_at,
            ]);
        } catch (\Throwable $e) {
            // CRITICAL: Never block case creation due to SLA calculation failure
            Log::error('[ServiceCaseObserver] SLA calculation failed - case creation continues', [
                'company_id' => $case->company_id,
                'error' => $e->getMessage(),
            ]);
            // Case will be created WITHOUT SLA dates - that's acceptable
        }
    }

    /**
     * Handle the ServiceCase "created" event.
     */
    public function created(ServiceCase $case): void
    {
        try {
            ServiceCaseActivityLog::logAction(
                $case,
                ServiceCaseActivityLog::ACTION_CREATED,
                auth()->user(),
                null, // no old values for creation
                $this->getRelevantValues($case),
                null
            );

            Log::debug('[ServiceCaseObserver] Created activity log for new case', [
                'case_id' => $case->id,
                'company_id' => $case->company_id,
            ]);
        } catch (\Throwable $e) {
            // CRITICAL: Never block case creation due to logging failure
            Log::error('[ServiceCaseObserver] Failed to log case creation', [
                'case_id' => $case->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Handle the ServiceCase "updating" event.
     * Called BEFORE changes are saved to the database.
     *
     * PURPOSE: Recalculate SLA if category changed
     * CRITICAL: Failures must NOT block case update!
     */
    public function updating(ServiceCase $case): void
    {
        try {
            // Only recalculate SLA if category changed
            if ($case->isDirty('category_id')) {
                $case->calculateSlaDueDates();

                Log::debug('[ServiceCaseObserver] SLA recalculated on category change', [
                    'case_id' => $case->id,
                    'old_category_id' => $case->getOriginal('category_id'),
                    'new_category_id' => $case->category_id,
                    'sla_response_due_at' => $case->sla_response_due_at,
                    'sla_resolution_due_at' => $case->sla_resolution_due_at,
                ]);
            }
        } catch (\Throwable $e) {
            // CRITICAL: Never block case update due to SLA failure
            Log::error('[ServiceCaseObserver] SLA recalculation failed - update continues', [
                'case_id' => $case->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Handle the ServiceCase "updated" event.
     */
    public function updated(ServiceCase $case): void
    {
        try {
            // Track each changed field separately for granular history
            foreach ($this->trackedFields as $field => $action) {
                if ($case->wasChanged($field)) {
                    $this->logFieldChange($case, $field, $action);
                }
            }
        } catch (\Throwable $e) {
            // CRITICAL: Never block case update due to logging failure
            Log::error('[ServiceCaseObserver] Failed to log case update', [
                'case_id' => $case->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Handle the ServiceCase "deleted" event (soft delete).
     */
    public function deleted(ServiceCase $case): void
    {
        try {
            ServiceCaseActivityLog::logAction(
                $case,
                ServiceCaseActivityLog::ACTION_DELETED,
                auth()->user(),
                ['deleted_at' => null],
                ['deleted_at' => now()->toISOString()],
                request()->input('delete_reason')
            );

            Log::debug('[ServiceCaseObserver] Logged case deletion', [
                'case_id' => $case->id,
            ]);
        } catch (\Throwable $e) {
            // CRITICAL: Never block case deletion due to logging failure
            Log::error('[ServiceCaseObserver] Failed to log case deletion', [
                'case_id' => $case->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Handle the ServiceCase "restored" event (from soft delete).
     */
    public function restored(ServiceCase $case): void
    {
        try {
            ServiceCaseActivityLog::logAction(
                $case,
                ServiceCaseActivityLog::ACTION_RESTORED,
                auth()->user(),
                ['deleted_at' => $case->deleted_at?->toISOString()],
                ['deleted_at' => null],
                request()->input('restore_reason')
            );

            Log::debug('[ServiceCaseObserver] Logged case restoration', [
                'case_id' => $case->id,
            ]);
        } catch (\Throwable $e) {
            // CRITICAL: Never block case restoration due to logging failure
            Log::error('[ServiceCaseObserver] Failed to log case restoration', [
                'case_id' => $case->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Log a specific field change
     */
    protected function logFieldChange(ServiceCase $case, string $field, string $action): void
    {
        $oldValue = $case->getOriginal($field);
        $newValue = $case->getAttribute($field);

        // Skip if values are the same (shouldn't happen with wasChanged, but safety check)
        if ($oldValue === $newValue) {
            return;
        }

        // Special handling for enrichment_status - only log when actually enriched
        if ($field === 'enrichment_status' && $newValue !== ServiceCase::ENRICHMENT_ENRICHED) {
            // Use a generic status change action for non-enriched states
            $action = ServiceCaseActivityLog::ACTION_STATUS_CHANGED;
        }

        ServiceCaseActivityLog::logAction(
            $case,
            $action,
            auth()->user(),
            [$field => $oldValue],
            [$field => $newValue],
            $this->getChangeReason($field)
        );

        Log::debug('[ServiceCaseObserver] Logged field change', [
            'case_id' => $case->id,
            'field' => $field,
            'action' => $action,
            'old' => $oldValue,
            'new' => $newValue,
        ]);
    }

    /**
     * Get relevant field values for the initial creation log
     */
    protected function getRelevantValues(ServiceCase $case): array
    {
        return [
            'status' => $case->status,
            'priority' => $case->priority,
            'urgency' => $case->urgency,
            'case_type' => $case->case_type,
            'category_id' => $case->category_id,
            'customer_id' => $case->customer_id,
            'assigned_to' => $case->assigned_to,
            'assigned_group_id' => $case->assigned_group_id,
            'subject' => $case->subject,
        ];
    }

    /**
     * Get the reason for a field change from the request
     */
    protected function getChangeReason(string $field): ?string
    {
        // Map field names to common reason request parameters
        $reasonKeys = [
            'status' => ['status_reason', 'reason'],
            'priority' => ['priority_reason', 'escalation_reason', 'reason'],
            'assigned_to' => ['assignment_reason', 'reason'],
            'assigned_group_id' => ['assignment_reason', 'reason'],
        ];

        if (!isset($reasonKeys[$field])) {
            return null;
        }

        foreach ($reasonKeys[$field] as $key) {
            $reason = request()->input($key);
            if ($reason) {
                return $reason;
            }
        }

        return null;
    }
}
