<?php

namespace App\Services\Saga;

use App\Models\Appointment;
use App\Services\Cache\InvalidationStrategies;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Exception;

/**
 * Cache Invalidation Service - Saga-aware cache management
 *
 * Integrates cache invalidation with Saga pattern to ensure:
 * - If saga succeeds: Cache is invalidated for stale data
 * - If saga fails: Cache was invalidated as part of compensation
 * - If cache invalidation fails: Non-blocking (doesn't fail saga)
 *
 * Strategies:
 * - Pattern-based: Clear all caches matching pattern (appointment:*)
 * - Tag-based: Clear caches with specific tags (redis only)
 * - Targeted: Clear only affected cache keys (surgical invalidation)
 */
class CacheInvalidationService
{
    private InvalidationStrategies $strategies;

    public function __construct()
    {
        $this->strategies = new InvalidationStrategies();
    }

    /**
     * Invalidate cache after successful appointment booking
     *
     * Called as part of AppointmentCreationSaga compensation
     * Ensures:
     * - Availability cache is cleared (no double-booking)
     * - Staff schedule cache is cleared
     * - Composite booking caches are cleared
     *
     * @param Appointment $appointment Booked appointment
     * @return bool Success status (non-blocking on failure)
     */
    public function invalidateAfterBooking(Appointment $appointment): bool
    {
        try {
            Log::channel('saga')->info('🗑️ Invalidating caches after appointment booking', [
                'appointment_id' => $appointment->id,
                'service_id' => $appointment->service_id,
                'staff_id' => $appointment->staff_id,
            ]);

            $cacheKeys = [
                // Availability caches
                "availability:service:{$appointment->service_id}",
                "availability:staff:{$appointment->staff_id}",
                "availability:branch:{$appointment->branch_id}",
                "availability:company:{$appointment->company_id}",

                // Week/month view caches
                "week_availability:*",
                "month_availability:*",

                // Staff schedule caches
                "staff:{$appointment->staff_id}:schedule",
                "staff:{$appointment->staff_id}:upcoming",

                // Composite booking caches (if applicable)
                "composite:pending:*",
                "composite:available:*",
            ];

            return $this->strategies->invalidateByPatterns($cacheKeys);

        } catch (Exception $e) {
            Log::channel('saga')->warning('⚠️ Cache invalidation failed (non-blocking)', [
                'appointment_id' => $appointment->id,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Invalidate cache after appointment cancellation
     *
     * Frees up the slot again so it becomes available
     * Clears caches in addition to booking invalidation
     *
     * @param Appointment $appointment Cancelled appointment
     * @return bool Success status
     */
    public function invalidateAfterCancellation(Appointment $appointment): bool
    {
        try {
            Log::channel('saga')->info('🗑️ Invalidating caches after appointment cancellation', [
                'appointment_id' => $appointment->id,
            ]);

            // All the same caches as booking (slot now available)
            return $this->invalidateAfterBooking($appointment);

        } catch (Exception $e) {
            Log::channel('saga')->warning('⚠️ Cache invalidation failed after cancellation', [
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Invalidate cache after appointment reschedule
     *
     * Clears caches for BOTH old and new time slots:
     * - Old slot: Now available again
     * - New slot: Now booked
     *
     * @param Appointment $appointment Rescheduled appointment
     * @param string $oldTime Previous appointment time (ISO format)
     * @return bool Success status
     */
    public function invalidateAfterReschedule(Appointment $appointment, string $oldTime): bool
    {
        try {
            Log::channel('saga')->info('🗑️ Invalidating caches after appointment reschedule', [
                'appointment_id' => $appointment->id,
                'old_time' => $oldTime,
                'new_time' => $appointment->starts_at->toIso8601String(),
            ]);

            $cacheKeys = [
                // Current slot (old, now available)
                "appointment:slot:{$appointment->staff_id}:{$oldTime}",

                // New slot (now booked)
                "appointment:slot:{$appointment->staff_id}:{$appointment->starts_at->toIso8601String()}",

                // General availability
                "availability:*",
                "schedule:*",
            ];

            return $this->strategies->invalidateByPatterns($cacheKeys);

        } catch (Exception $e) {
            Log::channel('saga')->warning('⚠️ Cache invalidation failed after reschedule', [
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Invalidate cache after Cal.com sync
     *
     * Called after successful sync to Cal.com
     * Ensures local cache matches Cal.com state
     *
     * @param Appointment $appointment Synced appointment
     * @return bool Success status
     */
    public function invalidateAfterSync(Appointment $appointment): bool
    {
        try {
            Log::channel('saga')->info('🗑️ Invalidating caches after Cal.com sync', [
                'appointment_id' => $appointment->id,
            ]);

            $cacheKeys = [
                // Sync-specific caches
                "calcom:sync:*",
                "appointment:{$appointment->id}:sync",

                // General availability (might have changed in Cal.com)
                "availability:service:{$appointment->service_id}",
                "availability:staff:{$appointment->staff_id}",
            ];

            return $this->strategies->invalidateByPatterns($cacheKeys);

        } catch (Exception $e) {
            Log::channel('saga')->warning('⚠️ Cache invalidation failed after sync', [
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Clear cache for specific appointment
     *
     * Surgical invalidation: only affects this appointment's caches
     * Used when appointment is deleted or fully reset
     *
     * @param int $appointmentId Appointment ID
     * @return bool Success status
     */
    public function clearForAppointment(int $appointmentId): bool
    {
        try {
            Log::channel('saga')->info('🗑️ Clearing all caches for appointment', [
                'appointment_id' => $appointmentId,
            ]);

            $patterns = [
                "appointment:{$appointmentId}:*",
            ];

            return $this->strategies->invalidateByPatterns($patterns);

        } catch (Exception $e) {
            Log::channel('saga')->warning('⚠️ Failed to clear appointment caches', [
                'appointment_id' => $appointmentId,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Clear all availability caches (global)
     *
     * Use sparingly - clears ALL availability data
     * Typically called during system maintenance or major reconfiguration
     *
     * @return bool Success status
     */
    public function clearAllAvailability(): bool
    {
        try {
            Log::channel('saga')->warning('🗑️ Clearing ALL availability caches', [
                'reason' => 'System maintenance or reconfiguration',
            ]);

            $patterns = [
                "availability:*",
                "week_availability:*",
                "month_availability:*",
                "slot:*",
                "calcom:slots:*",
            ];

            return $this->strategies->invalidateByPatterns($patterns);

        } catch (Exception $e) {
            Log::channel('saga')->error('❌ Failed to clear all availability caches', [
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Get cache invalidation statistics
     *
     * Returns stats on how much cache was invalidated
     *
     * @return array Statistics
     */
    public function getInvalidationStats(): array
    {
        return [
            'total_invalidations' => Cache::get('cache:invalidation:count', 0),
            'failed_invalidations' => Cache::get('cache:invalidation:failed', 0),
            'last_invalidation' => Cache::get('cache:invalidation:last_time'),
        ];
    }
}
