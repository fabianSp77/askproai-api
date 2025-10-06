<?php

namespace App\Listeners\Appointments;

use App\Events\Appointments\CallbackRequested;
use App\Models\CallbackRequest;
use App\Models\Staff;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Auto-assign callback requests to available staff
 *
 * Assignment logic:
 * 1. Prefer staff who previously served this customer
 * 2. Prefer staff with expertise in requested topic
 * 3. Assign to least-loaded available staff
 * 4. Round-robin if all else equal
 */
class AssignCallbackToStaff implements ShouldQueue
{
    use InteractsWithQueue;

    public $queue = 'callbacks';
    public $tries = 2;

    /**
     * Handle the event
     */
    public function handle(CallbackRequested $event): void
    {
        try {
            $callbackRequest = $event->callbackRequest;

            // Skip if already assigned
            if ($callbackRequest->assigned_staff_id) {
                Log::debug('Callback already assigned', [
                    'callback_id' => $callbackRequest->id,
                    'staff_id' => $callbackRequest->assigned_staff_id,
                ]);
                return;
            }

            // Find best staff member
            $assignedStaff = $this->findBestStaff($callbackRequest, $event->topic);

            if (!$assignedStaff) {
                Log::warning('⚠️  No available staff for callback assignment', [
                    'callback_id' => $callbackRequest->id,
                    'customer_id' => $callbackRequest->customer_id,
                ]);
                return;
            }

            // Assign staff
            $callbackRequest->update([
                'assigned_staff_id' => $assignedStaff->id,
                'assigned_at' => now(),
                'status' => 'assigned',
            ]);

            Log::info('✅ Callback assigned to staff', [
                'callback_id' => $callbackRequest->id,
                'staff_id' => $assignedStaff->id,
                'staff_name' => $assignedStaff->name,
                'assignment_method' => $assignedStaff->assignment_method ?? 'auto',
            ]);

            // Notify assigned staff
            $this->notifyAssignedStaff($assignedStaff, $callbackRequest);

        } catch (\Exception $e) {
            Log::error('❌ Failed to assign callback to staff', [
                'callback_id' => $event->callbackRequest->id,
                'error' => $e->getMessage(),
            ]);

            throw $e; // Retry
        }
    }

    /**
     * Find best staff member for callback
     */
    private function findBestStaff($callbackRequest, ?string $topic): ?Staff
    {
        $customer = $callbackRequest->customer;
        $branch = $customer->branch ?? $customer->company->branches()->first();

        if (!$branch) {
            return null;
        }

        // Strategy 1: Staff who previously served this customer
        $previousStaff = $this->findPreviousStaff($customer, $branch);
        if ($previousStaff && $this->isStaffAvailable($previousStaff)) {
            $previousStaff->assignment_method = 'previous_relationship';
            return $previousStaff;
        }

        // Strategy 2: Staff with topic expertise
        if ($topic) {
            $expertStaff = $this->findExpertStaff($branch, $topic);
            if ($expertStaff && $this->isStaffAvailable($expertStaff)) {
                $expertStaff->assignment_method = 'topic_expertise';
                return $expertStaff;
            }
        }

        // Strategy 3: Least-loaded available staff
        $leastLoadedStaff = $this->findLeastLoadedStaff($branch);
        if ($leastLoadedStaff) {
            $leastLoadedStaff->assignment_method = 'least_loaded';
            return $leastLoadedStaff;
        }

        return null;
    }

    /**
     * Find staff who previously served this customer
     */
    private function findPreviousStaff($customer, $branch): ?Staff
    {
        return Staff::whereHas('appointments', function ($query) use ($customer) {
            $query->where('customer_id', $customer->id)
                ->whereIn('status', ['completed', 'confirmed']);
        })
            ->where('branch_id', $branch->id)
            ->where('is_active', true)
            ->orderByDesc(function ($query) use ($customer) {
                $query->select(DB::raw('COUNT(*)'))
                    ->from('appointments')
                    ->whereColumn('appointments.staff_id', 'staff.id')
                    ->where('customer_id', $customer->id);
            })
            ->first();
    }

    /**
     * Find staff with expertise in topic
     */
    private function findExpertStaff($branch, string $topic): ?Staff
    {
        // Match topic keywords with staff specializations
        return Staff::where('branch_id', $branch->id)
            ->where('is_active', true)
            ->where(function ($query) use ($topic) {
                $query->whereJsonContains('specializations', $topic)
                    ->orWhere('expertise', 'like', "%{$topic}%");
            })
            ->first();
    }

    /**
     * Find least-loaded available staff
     */
    private function findLeastLoadedStaff($branch): ?Staff
    {
        return Staff::where('branch_id', $branch->id)
            ->where('is_active', true)
            ->withCount([
                'callbackRequests' => function ($query) {
                    $query->whereIn('status', ['pending', 'assigned'])
                        ->where('created_at', '>=', now()->subDay());
                }
            ])
            ->orderBy('callback_requests_count', 'asc')
            ->first();
    }

    /**
     * Check if staff is available
     */
    private function isStaffAvailable(Staff $staff): bool
    {
        // Check if staff is active and not on break/holiday
        if (!$staff->is_active) {
            return false;
        }

        // Check working hours (if implemented)
        // Could check against shift schedule, availability calendar, etc.

        return true;
    }

    /**
     * Notify assigned staff of new callback
     */
    private function notifyAssignedStaff(Staff $staff, $callbackRequest): void
    {
        try {
            $staff->notify(new \App\Notifications\CallbackAssigned(
                callbackRequest: $callbackRequest,
                customer: $callbackRequest->customer,
                priority: $callbackRequest->priority ?? 'normal'
            ));
        } catch (\Exception $e) {
            Log::warning('Failed to notify staff of callback assignment', [
                'staff_id' => $staff->id,
                'callback_id' => $callbackRequest->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Handle failed job
     */
    public function failed(CallbackRequested $event, \Throwable $exception): void
    {
        Log::error('🔥 Callback assignment job permanently failed', [
            'callback_id' => $event->callbackRequest->id,
            'error' => $exception->getMessage(),
        ]);

        // Keep callback as pending for manual assignment
        $event->callbackRequest->update([
            'status' => CallbackRequest::STATUS_PENDING,
            'notes' => 'Auto-assignment failed: ' . $exception->getMessage(),
        ]);
    }
}
