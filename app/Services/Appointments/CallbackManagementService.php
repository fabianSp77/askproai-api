<?php

namespace App\Services\Appointments;

use App\Models\CallbackRequest;
use App\Models\CallbackEscalation;
use App\Models\Staff;
use App\Models\Branch;
use App\Events\Appointments\CallbackRequested;
use App\Events\Appointments\CallbackEscalated;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

/**
 * CallbackManagementService
 *
 * Manages the complete lifecycle of customer callback requests:
 * - Creation and auto-assignment
 * - Contact tracking
 * - Completion workflow
 * - Escalation management
 * - Overdue detection
 */
class CallbackManagementService
{
    /**
     * Create a new callback request
     *
     * @param array $data Callback request data
     * @return CallbackRequest
     */
    public function createRequest(array $data): CallbackRequest
    {
        DB::beginTransaction();

        try {
            // Set default values
            $defaults = [
                'priority' => CallbackRequest::PRIORITY_NORMAL,
                'status' => CallbackRequest::STATUS_PENDING,
                'expires_at' => $this->calculateExpirationTime($data['priority'] ?? CallbackRequest::PRIORITY_NORMAL),
            ];

            $callbackData = array_merge($defaults, $data);

            // Create callback request
            $callback = CallbackRequest::create($callbackData);

            // Load relationships
            $callback->loadMissing(['customer', 'branch', 'service', 'staff']);

            // Fire event
            event(new CallbackRequested($callback));

            // Auto-assign if configured
            if ($this->shouldAutoAssign($callback)) {
                $this->autoAssignToStaff($callback);
            }

            DB::commit();

            Log::info('✅ Created callback request', [
                'callback_id' => $callback->id,
                'customer_name' => $callback->customer_name,
                'phone' => $callback->phone_number,
                'priority' => $callback->priority,
            ]);

            return $callback->fresh(['customer', 'branch', 'service', 'assignedTo']);

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('❌ Failed to create callback request', [
                'error' => $e->getMessage(),
                'data' => $data,
            ]);

            throw $e;
        }
    }

    /**
     * Assign callback to specific staff member
     *
     * @param CallbackRequest $request
     * @param Staff $staff
     * @return void
     */
    public function assignToStaff(CallbackRequest $request, Staff $staff): void
    {
        try {
            $request->assign($staff);

            Log::info('📋 Callback assigned to staff', [
                'callback_id' => $request->id,
                'staff_id' => $staff->id,
                'staff_name' => $staff->name,
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Failed to assign callback', [
                'callback_id' => $request->id,
                'staff_id' => $staff->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Mark callback as contacted
     *
     * @param CallbackRequest $request
     * @return void
     */
    public function markContacted(CallbackRequest $request): void
    {
        try {
            $request->markContacted();

            Log::info('📞 Callback marked as contacted', [
                'callback_id' => $request->id,
                'customer_name' => $request->customer_name,
                'assigned_to' => $request->assigned_to,
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Failed to mark callback as contacted', [
                'callback_id' => $request->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Mark callback as completed with notes
     *
     * @param CallbackRequest $request
     * @param string $notes
     * @return void
     */
    public function markCompleted(CallbackRequest $request, string $notes): void
    {
        try {
            $request->notes = $notes;
            $request->markCompleted();

            Log::info('✅ Callback completed', [
                'callback_id' => $request->id,
                'customer_name' => $request->customer_name,
                'assigned_to' => $request->assigned_to,
                'notes_length' => strlen($notes),
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Failed to complete callback', [
                'callback_id' => $request->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Escalate callback request
     *
     * @param CallbackRequest $request
     * @param string $reason
     * @return CallbackEscalation
     */
    public function escalate(CallbackRequest $request, string $reason): CallbackEscalation
    {
        DB::beginTransaction();

        try {
            // Find escalation target (manager or senior staff)
            $escalationTarget = $this->findEscalationTarget($request);

            // Create escalation
            $escalation = $request->escalate($reason, $escalationTarget?->id);

            // Update callback assignment if escalation target found
            if ($escalationTarget) {
                $request->assign($escalationTarget);
            }

            // Fire escalation event
            event(new CallbackEscalated($request, $reason, 'auto', $escalationTarget?->id));

            DB::commit();

            Log::warning('⚠️ Callback escalated', [
                'callback_id' => $request->id,
                'escalation_id' => $escalation->id,
                'reason' => $reason,
                'escalated_from' => $escalation->escalated_from,
                'escalated_to' => $escalation->escalated_to,
            ]);

            return $escalation->fresh(['callbackRequest', 'escalatedFrom', 'escalatedTo']);

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('❌ Failed to escalate callback', [
                'callback_id' => $request->id,
                'reason' => $reason,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Get overdue callbacks for a branch
     *
     * @param Branch $branch
     * @return Collection
     */
    public function getOverdueCallbacks(Branch $branch): Collection
    {
        return CallbackRequest::overdue()
            ->where('branch_id', $branch->id)
            ->with(['customer', 'service', 'assignedTo', 'escalations'])
            ->orderBy('priority', 'desc')
            ->orderBy('expires_at', 'asc')
            ->get();
    }

    /**
     * Auto-assign callback to best available staff
     *
     * @param CallbackRequest $callback
     * @return void
     */
    protected function autoAssignToStaff(CallbackRequest $callback): void
    {
        $staff = $this->findBestStaff($callback);

        if ($staff) {
            $callback->assign($staff);

            Log::debug('🤖 Auto-assigned callback', [
                'callback_id' => $callback->id,
                'staff_id' => $staff->id,
                'staff_name' => $staff->name,
            ]);
        } else {
            Log::warning('⚠️ No staff available for auto-assignment', [
                'callback_id' => $callback->id,
                'branch_id' => $callback->branch_id,
            ]);
        }
    }

    /**
     * Find best staff member for callback assignment
     *
     * Strategy:
     * 1. Preferred staff if specified and available
     * 2. Staff with expertise in requested service
     * 3. Least loaded staff member
     *
     * @param CallbackRequest $callback
     * @return Staff|null
     */
    protected function findBestStaff(CallbackRequest $callback): ?Staff
    {
        // 1. Check preferred staff
        if ($callback->staff_id) {
            $preferredStaff = Staff::find($callback->staff_id);
            if ($preferredStaff && $preferredStaff->is_active) {
                return $preferredStaff;
            }
        }

        // 2. Find staff with service expertise
        if ($callback->service_id) {
            $expertStaff = Staff::where('branch_id', $callback->branch_id)
                ->where('is_active', true)
                ->whereHas('services', function ($query) use ($callback) {
                    $query->where('services.id', $callback->service_id);
                })
                ->withCount(['callbackRequests' => function ($query) {
                    $query->whereIn('status', [
                        CallbackRequest::STATUS_PENDING,
                        CallbackRequest::STATUS_ASSIGNED,
                        CallbackRequest::STATUS_CONTACTED,
                    ]);
                }])
                ->orderBy('callback_requests_count', 'asc')
                ->first();

            if ($expertStaff) {
                return $expertStaff;
            }
        }

        // 3. Least loaded staff in branch
        return Staff::where('branch_id', $callback->branch_id)
            ->where('is_active', true)
            ->withCount(['callbackRequests' => function ($query) {
                $query->whereIn('status', [
                    CallbackRequest::STATUS_PENDING,
                    CallbackRequest::STATUS_ASSIGNED,
                    CallbackRequest::STATUS_CONTACTED,
                ]);
            }])
            ->orderBy('callback_requests_count', 'asc')
            ->first();
    }

    /**
     * Find staff member to escalate to (any available staff different from current)
     *
     * @param CallbackRequest $request
     * @return Staff|null
     */
    protected function findEscalationTarget(CallbackRequest $request): ?Staff
    {
        // Find any active staff in the branch different from currently assigned
        return Staff::where('branch_id', $request->branch_id)
            ->where('is_active', true)
            ->where('id', '!=', $request->assigned_to ?? '')
            ->first();
    }

    /**
     * Check if callback should be auto-assigned
     *
     * @param CallbackRequest $callback
     * @return bool
     */
    protected function shouldAutoAssign(CallbackRequest $callback): bool
    {
        // Auto-assign high priority and urgent callbacks
        if (in_array($callback->priority, [CallbackRequest::PRIORITY_HIGH, CallbackRequest::PRIORITY_URGENT])) {
            return true;
        }

        // Check configuration
        return config('callbacks.auto_assign', true);
    }

    /**
     * Calculate expiration time based on priority
     *
     * @param string $priority
     * @return Carbon
     */
    protected function calculateExpirationTime(string $priority): Carbon
    {
        $hours = match ($priority) {
            CallbackRequest::PRIORITY_URGENT => config('callbacks.expiration_hours.urgent', 2),
            CallbackRequest::PRIORITY_HIGH => config('callbacks.expiration_hours.high', 4),
            default => config('callbacks.expiration_hours.normal', 24),
        };

        return Carbon::now()->addHours($hours);
    }
}
