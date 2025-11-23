<?php

namespace App\Services\Booking;

use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

/**
 * Availability With Lock Service
 *
 * Purpose: Decorator that adds Redis-based slot locking to availability checks
 * Pattern: Decorator Pattern - wraps existing availability check with locking
 *
 * Usage:
 * 1. Check availability (existing logic)
 * 2. If available → Acquire Redis lock
 * 3. Return lock_key to client
 * 4. Client uses lock_key for booking
 *
 * Benefits:
 * - Zero changes to existing check_availability logic
 * - Backwards compatible (lock_key optional)
 * - Can be enabled/disabled via feature flag
 */
class AvailabilityWithLockService
{
    public function __construct(
        private SlotLockService $lockService
    ) {}

    /**
     * Wrap availability check result with slot locking
     *
     * @param array $availabilityResult Result from checkAvailability()
     * @param int $companyId
     * @param int $serviceId
     * @param Carbon $startTime
     * @param Carbon $endTime
     * @param string $callId
     * @param string $customerPhone
     * @param array $metadata
     * @return array Enhanced result with lock_key
     */
    public function wrapWithLock(
        array $availabilityResult,
        int $companyId,
        int $serviceId,
        Carbon $startTime,
        Carbon $endTime,
        string $callId,
        string $customerPhone,
        array $metadata = []
    ): array {
        // If slot is NOT available, return as-is (no lock needed)
        if (!($availabilityResult['available'] ?? false)) {
            Log::info('[LOCK_WRAPPER] Slot not available, skipping lock', [
                'call_id' => $callId,
                'service_id' => $serviceId,
                'start_time' => $startTime->format('Y-m-d H:i'),
            ]);

            return $availabilityResult;
        }

        // Slot is available → Acquire lock
        $lockResult = $this->lockService->acquireLock(
            $companyId,
            $serviceId,
            $startTime,
            $endTime,
            $callId,
            $customerPhone,
            $metadata
        );

        if (!$lockResult['success']) {
            // Lock failed (race condition detected!)
            Log::warning('[LOCK_WRAPPER] Failed to acquire lock - race condition detected', [
                'call_id' => $callId,
                'reason' => $lockResult['reason'] ?? 'unknown',
                'service_id' => $serviceId,
                'start_time' => $startTime->format('Y-m-d H:i'),
            ]);

            // Return "not available" (slot was just taken)
            return [
                'success' => false,
                'available' => false,
                'reason' => 'slot_just_taken',
                'message' => 'Dieser Zeitslot wurde gerade von einem anderen Kunden gebucht. Bitte wählen Sie einen anderen Termin.',
                'race_condition_detected' => true,
                'locked_by_call' => $lockResult['locked_by'] ?? null,
            ];
        }

        // Lock acquired successfully → Add lock_key to result
        $enhancedResult = $availabilityResult;
        $enhancedResult['lock_key'] = $lockResult['lock_key'];
        $enhancedResult['lock_expires_at'] = $lockResult['expires_at']->format('Y-m-d H:i:s');
        $enhancedResult['slot_locked'] = true;

        Log::info('[LOCK_WRAPPER] Slot locked successfully', [
            'call_id' => $callId,
            'lock_key' => $lockResult['lock_key'],
            'expires_at' => $lockResult['expires_at']->format('Y-m-d H:i:s'),
        ]);

        return $enhancedResult;
    }

    /**
     * Check if slot is already locked (before running availability check)
     *
     * This is an optimization to avoid expensive Cal.com API calls
     * if the slot is already locked by another call
     *
     * @param int $companyId
     * @param int $serviceId
     * @param Carbon $startTime
     * @return array{locked: bool, lock_info?: array}
     */
    public function checkIfLocked(
        int $companyId,
        int $serviceId,
        Carbon $startTime
    ): array {
        $isLocked = $this->lockService->isSlotLocked($companyId, $serviceId, $startTime);

        if (!$isLocked) {
            return ['locked' => false];
        }

        $lockKey = $this->lockService->generateLockKey($companyId, $serviceId, $startTime);
        $lockInfo = $this->lockService->getLockInfo($lockKey);

        return [
            'locked' => true,
            'lock_info' => $lockInfo,
        ];
    }
}
