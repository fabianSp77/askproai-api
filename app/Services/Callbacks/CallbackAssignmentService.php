<?php

namespace App\Services\Callbacks;

use App\Models\CallbackRequest;
use App\Models\Staff;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * CallbackAssignmentService
 *
 * Handles automatic assignment of callback requests to available staff.
 * Supports multiple assignment strategies:
 * - Round-Robin: Distributes evenly across all eligible staff
 * - Load-Based: Assigns to staff with fewest active callbacks
 */
class CallbackAssignmentService
{
    /**
     * Auto-assign callback to best available staff member.
     *
     * @param CallbackRequest $callback
     * @param string $strategy 'round_robin' or 'load_based'
     * @return Staff|null Assigned staff or null if none available
     */
    public function autoAssign(CallbackRequest $callback, string $strategy = 'round_robin'): ?Staff
    {
        // Get eligible staff for this callback
        $eligibleStaff = $this->getEligibleStaff($callback);

        if ($eligibleStaff->isEmpty()) {
            Log::warning('No eligible staff for callback auto-assignment', [
                'callback_id' => $callback->id,
                'branch_id' => $callback->branch_id,
                'service_id' => $callback->service_id,
            ]);
            return null;
        }

        // Select staff based on strategy
        $selectedStaff = match($strategy) {
            'round_robin' => $this->roundRobinSelection($eligibleStaff),
            'load_based' => $this->loadBasedSelection($eligibleStaff),
            default => $this->roundRobinSelection($eligibleStaff),
        };

        if (!$selectedStaff) {
            return null;
        }

        // Assign callback to selected staff
        $callback->assign($selectedStaff);

        Log::info('Callback auto-assigned', [
            'callback_id' => $callback->id,
            'staff_id' => $selectedStaff->id,
            'staff_name' => $selectedStaff->name,
            'strategy' => $strategy,
        ]);

        return $selectedStaff;
    }

    /**
     * Get staff members eligible for callback assignment.
     *
     * Criteria:
     * - Must be active
     * - Must belong to same branch as callback
     * - If service specified, must offer that service
     * - Must be available (not on leave, working hours)
     *
     * @param CallbackRequest $callback
     * @return Collection
     */
    protected function getEligibleStaff(CallbackRequest $callback): Collection
    {
        $query = Staff::where('is_active', true)
            ->where('branch_id', $callback->branch_id)
            ->where('company_id', $callback->company_id);

        // If service specified, filter by staff who offer this service
        if ($callback->service_id) {
            $query->whereHas('services', function ($q) use ($callback) {
                $q->where('service_id', $callback->service_id);
            });
        }

        // TODO: Add working hours check
        // TODO: Add leave/absence check

        return $query->with(['workingHours'])->get();
    }

    /**
     * Round-Robin Selection Strategy
     *
     * Distributes callbacks evenly by rotating through staff list.
     * Uses cache to remember last assigned staff.
     *
     * @param Collection $staff
     * @return Staff|null
     */
    protected function roundRobinSelection(Collection $staff): ?Staff
    {
        if ($staff->isEmpty()) {
            return null;
        }

        $cacheKey = 'callback.last_assigned_staff_id';
        $lastAssignedId = Cache::get($cacheKey);

        // Find index of last assigned staff
        $currentIndex = $staff->search(fn($s) => $s->id === $lastAssignedId);

        // If last assigned not found or is last in list, start from beginning
        if ($currentIndex === false) {
            $nextStaff = $staff->first();
        } else {
            $nextIndex = ($currentIndex + 1) % $staff->count();
            $nextStaff = $staff[$nextIndex];
        }

        // Remember this staff for next round
        if ($nextStaff) {
            Cache::put($cacheKey, $nextStaff->id, now()->addHours(24));
        }

        return $nextStaff;
    }

    /**
     * Load-Based Selection Strategy
     *
     * Assigns to staff member with fewest active callbacks.
     * Prevents overloading any single staff member.
     *
     * @param Collection $staff
     * @return Staff|null
     */
    protected function loadBasedSelection(Collection $staff): ?Staff
    {
        if ($staff->isEmpty()) {
            return null;
        }

        // Get callback counts for each staff
        $staffIds = $staff->pluck('id')->toArray();

        $callbackCounts = DB::table('callback_requests')
            ->select('assigned_to', DB::raw('COUNT(*) as count'))
            ->whereIn('assigned_to', $staffIds)
            ->whereIn('status', [
                CallbackRequest::STATUS_PENDING,
                CallbackRequest::STATUS_ASSIGNED,
            ])
            ->whereNull('deleted_at')
            ->groupBy('assigned_to')
            ->pluck('count', 'assigned_to')
            ->toArray();

        // Find staff with minimum callbacks
        $minLoad = PHP_INT_MAX;
        $selectedStaff = null;

        foreach ($staff as $member) {
            $currentLoad = $callbackCounts[$member->id] ?? 0;

            if ($currentLoad < $minLoad) {
                $minLoad = $currentLoad;
                $selectedStaff = $member;
            }
        }

        return $selectedStaff;
    }

    /**
     * Bulk auto-assign all pending callbacks.
     *
     * @param int|null $branchId Optional: Limit to specific branch
     * @param string $strategy Assignment strategy
     * @return array Statistics about assignment
     */
    public function bulkAutoAssign(?int $branchId = null, string $strategy = 'load_based'): array
    {
        $query = CallbackRequest::where('status', CallbackRequest::STATUS_PENDING)
            ->whereNull('assigned_to');

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        $pendingCallbacks = $query->orderBy('priority', 'desc')
            ->orderBy('created_at', 'asc')
            ->get();

        $assigned = 0;
        $failed = 0;

        foreach ($pendingCallbacks as $callback) {
            $staff = $this->autoAssign($callback, $strategy);

            if ($staff) {
                $assigned++;
            } else {
                $failed++;
            }
        }

        return [
            'total' => $pendingCallbacks->count(),
            'assigned' => $assigned,
            'failed' => $failed,
            'strategy' => $strategy,
        ];
    }

    /**
     * Re-assign callback to different staff (e.g., due to unavailability).
     *
     * @param CallbackRequest $callback
     * @param string $reason Reason for reassignment
     * @param string $strategy Assignment strategy
     * @return Staff|null New assigned staff
     */
    public function reassign(CallbackRequest $callback, string $reason, string $strategy = 'load_based'): ?Staff
    {
        $oldStaffId = $callback->assigned_to;

        // Get eligible staff excluding current assignee
        $eligibleStaff = $this->getEligibleStaff($callback)
            ->reject(fn($s) => $s->id === $oldStaffId);

        if ($eligibleStaff->isEmpty()) {
            Log::warning('No alternative staff for callback reassignment', [
                'callback_id' => $callback->id,
                'old_staff_id' => $oldStaffId,
                'reason' => $reason,
            ]);
            return null;
        }

        // Select new staff
        $newStaff = match($strategy) {
            'round_robin' => $this->roundRobinSelection($eligibleStaff),
            'load_based' => $this->loadBasedSelection($eligibleStaff),
            default => $this->loadBasedSelection($eligibleStaff),
        };

        if ($newStaff) {
            $callback->assign($newStaff);

            // Log reassignment with metadata
            $callback->update([
                'metadata' => array_merge($callback->metadata ?? [], [
                    'reassignment' => [
                        'from_staff_id' => $oldStaffId,
                        'to_staff_id' => $newStaff->id,
                        'reason' => $reason,
                        'reassigned_at' => now()->toIso8601String(),
                    ],
                ]),
            ]);

            Log::info('Callback reassigned', [
                'callback_id' => $callback->id,
                'old_staff_id' => $oldStaffId,
                'new_staff_id' => $newStaff->id,
                'reason' => $reason,
            ]);
        }

        return $newStaff;
    }
}
