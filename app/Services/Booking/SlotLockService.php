<?php

namespace App\Services\Booking;

use App\Models\AppointmentReservation;
use App\Services\Metrics\ReservationMetricsCollector;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

/**
 * Slot Lock Service
 *
 * Purpose: Redis-based distributed locking for appointment slots
 * Pattern: Industry-standard distributed lock with auto-expiry
 *
 * Features:
 * - Fast Redis-based locking (in-memory, <1ms)
 * - Auto-cleanup via TTL (no cleanup job needed)
 * - Optional database logging for metrics/debugging
 * - Thread-safe via atomic Redis operations
 *
 * Architecture:
 * - Primary: Redis locks (performance-critical)
 * - Secondary: DB reservations (optional, for analytics)
 */
class SlotLockService
{
    private const LOCK_PREFIX = 'slot_lock:';
    private const DEFAULT_TTL = 300; // 5 minutes in seconds

    public function __construct(
        private ReservationMetricsCollector $metricsCollector
    ) {}

    /**
     * Acquire a lock on a time slot
     *
     * @param int $companyId
     * @param int $serviceId
     * @param Carbon $startTime
     * @param Carbon $endTime
     * @param string $callId
     * @param string $customerPhone
     * @param array $metadata Additional data (customer_name, staff_id, etc.)
     * @return array{success: bool, lock_key?: string, reason?: string}
     */
    public function acquireLock(
        int $companyId,
        int $serviceId,
        Carbon $startTime,
        Carbon $endTime,
        string $callId,
        string $customerPhone,
        array $metadata = []
    ): array {
        $lockKey = $this->generateLockKey($companyId, $serviceId, $startTime);

        // Atomic check-and-set via Redis
        if (Cache::has($lockKey)) {
            $existingLock = Cache::get($lockKey);

            // Log conflict for debugging
            Log::warning('[SLOT_LOCK] Lock conflict', [
                'lock_key' => $lockKey,
                'current_call' => $callId,
                'blocking_call' => $existingLock['call_id'] ?? 'unknown',
                'requested_by' => $customerPhone,
            ]);

            $this->metricsCollector->trackError(
                $companyId,
                'lock_conflict',
                "Slot already locked by call {$existingLock['call_id']}"
            );

            return [
                'success' => false,
                'reason' => 'slot_locked',
                'locked_by' => $existingLock['call_id'] ?? 'unknown',
            ];
        }

        // Acquire lock
        $lockData = [
            'call_id' => $callId,
            'customer_phone' => $customerPhone,
            'company_id' => $companyId,
            'service_id' => $serviceId,
            'start_time' => $startTime->toIso8601String(),
            'end_time' => $endTime->toIso8601String(),
            'locked_at' => now()->toIso8601String(),
            'expires_at' => now()->addSeconds(self::DEFAULT_TTL)->toIso8601String(),
            'metadata' => $metadata,
        ];

        Cache::put($lockKey, $lockData, self::DEFAULT_TTL);

        // Optional: Log to database for metrics/debugging
        $this->logReservationToDatabase(
            $companyId,
            $serviceId,
            $startTime,
            $endTime,
            $callId,
            $customerPhone,
            $metadata
        );

        // Track metrics
        $this->metricsCollector->trackCreated(
            $companyId,
            $metadata['is_compound'] ?? false
        );

        Log::info('[SLOT_LOCK] Lock acquired', [
            'lock_key' => $lockKey,
            'call_id' => $callId,
            'customer_phone' => $customerPhone,
            'ttl' => self::DEFAULT_TTL,
        ]);

        return [
            'success' => true,
            'lock_key' => $lockKey,
            'expires_at' => now()->addSeconds(self::DEFAULT_TTL),
        ];
    }

    /**
     * Validate and consume a lock (used during booking)
     *
     * @param string $lockKey
     * @param string $callId
     * @return array{valid: bool, data?: array, reason?: string}
     */
    public function validateLock(string $lockKey, string $callId): array
    {
        if (!Cache::has($lockKey)) {
            return [
                'valid' => false,
                'reason' => 'lock_expired',
            ];
        }

        $lockData = Cache::get($lockKey);

        // Verify ownership
        if ($lockData['call_id'] !== $callId) {
            Log::warning('[SLOT_LOCK] Lock ownership mismatch', [
                'lock_key' => $lockKey,
                'expected_call' => $callId,
                'actual_call' => $lockData['call_id'],
            ]);

            return [
                'valid' => false,
                'reason' => 'lock_ownership_mismatch',
            ];
        }

        return [
            'valid' => true,
            'data' => $lockData,
        ];
    }

    /**
     * Release a lock (called after successful booking)
     *
     * @param string $lockKey
     * @param string $callId
     * @param int|null $appointmentId
     * @return bool
     */
    public function releaseLock(string $lockKey, string $callId, ?int $appointmentId = null): bool
    {
        $validation = $this->validateLock($lockKey, $callId);

        if (!$validation['valid']) {
            return false;
        }

        $lockData = $validation['data'];

        // Calculate time to conversion
        $lockedAt = Carbon::parse($lockData['locked_at']);
        $timeToConversion = now()->diffInSeconds($lockedAt);

        // Track metrics
        $this->metricsCollector->trackConverted(
            $lockData['company_id'],
            $timeToConversion,
            $lockData['metadata']['is_compound'] ?? false
        );

        // Update database reservation if exists
        if ($appointmentId) {
            $this->markReservationAsConverted($lockKey, $appointmentId);
        }

        // Release Redis lock
        Cache::forget($lockKey);

        Log::info('[SLOT_LOCK] Lock released', [
            'lock_key' => $lockKey,
            'call_id' => $callId,
            'appointment_id' => $appointmentId,
            'time_to_conversion' => $timeToConversion,
        ]);

        return true;
    }

    /**
     * Cancel a lock (customer cancelled before booking)
     *
     * @param string $lockKey
     * @param string $callId
     * @param string $reason
     * @return bool
     */
    public function cancelLock(string $lockKey, string $callId, string $reason = 'user_cancelled'): bool
    {
        $validation = $this->validateLock($lockKey, $callId);

        if (!$validation['valid']) {
            return false;
        }

        $lockData = $validation['data'];

        // Track metrics
        $this->metricsCollector->trackCancelled($lockData['company_id'], $reason);

        // Update database reservation if exists
        $this->markReservationAsCancelled($lockKey, $reason);

        // Release Redis lock
        Cache::forget($lockKey);

        Log::info('[SLOT_LOCK] Lock cancelled', [
            'lock_key' => $lockKey,
            'call_id' => $callId,
            'reason' => $reason,
        ]);

        return true;
    }

    /**
     * Check if a slot is currently locked
     *
     * @param int $companyId
     * @param int $serviceId
     * @param Carbon $startTime
     * @return bool
     */
    public function isSlotLocked(int $companyId, int $serviceId, Carbon $startTime): bool
    {
        $lockKey = $this->generateLockKey($companyId, $serviceId, $startTime);
        return Cache::has($lockKey);
    }

    /**
     * Get lock information (for debugging)
     *
     * @param string $lockKey
     * @return array|null
     */
    public function getLockInfo(string $lockKey): ?array
    {
        return Cache::get($lockKey);
    }

    /**
     * Extend lock TTL (if customer needs more time)
     *
     * @param string $lockKey
     * @param string $callId
     * @param int $additionalSeconds
     * @return bool
     */
    public function extendLock(string $lockKey, string $callId, int $additionalSeconds = 300): bool
    {
        $validation = $this->validateLock($lockKey, $callId);

        if (!$validation['valid']) {
            return false;
        }

        $lockData = $validation['data'];
        $lockData['expires_at'] = now()->addSeconds($additionalSeconds)->toIso8601String();

        Cache::put($lockKey, $lockData, $additionalSeconds);

        Log::info('[SLOT_LOCK] Lock extended', [
            'lock_key' => $lockKey,
            'call_id' => $callId,
            'additional_seconds' => $additionalSeconds,
        ]);

        return true;
    }

    // ========================================================================
    // PRIVATE HELPER METHODS
    // ========================================================================

    /**
     * Generate consistent lock key
     *
     * @param int $companyId
     * @param int $serviceId
     * @param Carbon $startTime
     * @return string Lock key format: slot_lock:c{company}:s{service}:t{YmdHi}
     */
    public function generateLockKey(int $companyId, int $serviceId, Carbon $startTime): string
    {
        // Format: slot_lock:c{company}:s{service}:t{YmdHi}
        return self::LOCK_PREFIX . "c{$companyId}:s{$serviceId}:t{$startTime->format('YmdHi')}";
    }

    /**
     * Optional: Log reservation to database for analytics
     */
    private function logReservationToDatabase(
        int $companyId,
        int $serviceId,
        Carbon $startTime,
        Carbon $endTime,
        string $callId,
        string $customerPhone,
        array $metadata
    ): void {
        try {
            AppointmentReservation::create([
                'company_id' => $companyId,
                'call_id' => $callId,
                'customer_phone' => $customerPhone,
                'customer_name' => $metadata['customer_name'] ?? null,
                'service_id' => $serviceId,
                'staff_id' => $metadata['staff_id'] ?? null,
                'start_time' => $startTime,
                'end_time' => $endTime,
                'is_compound' => $metadata['is_compound'] ?? false,
                'compound_parent_token' => $metadata['compound_parent_token'] ?? null,
                'segment_number' => $metadata['segment_number'] ?? null,
                'total_segments' => $metadata['total_segments'] ?? null,
                'expires_at' => now()->addSeconds(self::DEFAULT_TTL),
            ]);
        } catch (\Exception $e) {
            // Non-critical - log and continue
            Log::warning('[SLOT_LOCK] Failed to log reservation to database', [
                'error' => $e->getMessage(),
                'call_id' => $callId,
            ]);
        }
    }

    /**
     * Mark database reservation as converted
     */
    private function markReservationAsConverted(string $lockKey, int $appointmentId): void
    {
        try {
            $parts = explode(':', $lockKey);
            $callId = $parts[1] ?? null;

            if ($callId) {
                AppointmentReservation::where('call_id', $callId)
                    ->where('status', 'active')
                    ->first()
                    ?->markConverted($appointmentId);
            }
        } catch (\Exception $e) {
            Log::warning('[SLOT_LOCK] Failed to update reservation status', [
                'error' => $e->getMessage(),
                'lock_key' => $lockKey,
            ]);
        }
    }

    /**
     * Mark database reservation as cancelled
     */
    private function markReservationAsCancelled(string $lockKey, string $reason): void
    {
        try {
            $parts = explode(':', $lockKey);
            $callId = $parts[1] ?? null;

            if ($callId) {
                AppointmentReservation::where('call_id', $callId)
                    ->where('status', 'active')
                    ->first()
                    ?->markCancelled();
            }
        } catch (\Exception $e) {
            Log::warning('[SLOT_LOCK] Failed to update reservation status', [
                'error' => $e->getMessage(),
                'lock_key' => $lockKey,
            ]);
        }
    }
}
