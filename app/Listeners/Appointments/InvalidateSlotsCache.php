<?php

namespace App\Listeners\Appointments;

use App\Events\Appointments\AppointmentBooked;
use App\Events\Appointments\AppointmentCancelled;
use App\Events\Appointments\AppointmentRescheduled;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

/**
 * Invalidate cached availability slots when appointments change
 *
 * CRITICAL: Prevents double-bookings by ensuring cache consistency
 * with actual Cal.com availability
 *
 * Design Principles:
 * - Performance: <50ms overhead (cache deletes are fast)
 * - Resilience: Non-blocking (logs errors but doesn't throw)
 * - Idempotency: Safe for multiple event firings
 * - Security: Respects multi-tenant isolation
 *
 * Cache Key Format (from AppointmentAlternativeFinder):
 * cal_slots_{company_id}_{branch_id}_{event_type_id}_{start_hour}_{end_hour}
 *
 * @see \App\Services\AppointmentAlternativeFinder::getAvailableSlots()
 */
class InvalidateSlotsCache
{
    /**
     * Handle appointment booked event
     *
     * Invalidates cache to remove newly booked slot from availability
     */
    public function handleBooked(AppointmentBooked $event): void
    {
        $appointment = $event->appointment;

        Log::info('🗑️ Invalidating cache for booked appointment', [
            'appointment_id' => $appointment->id,
            'starts_at' => $appointment->starts_at->toIso8601String(),
            'service_name' => $appointment->service?->name,
        ]);

        $this->invalidateCacheForAppointment($appointment);
    }

    /**
     * Handle appointment rescheduled event
     *
     * Invalidates cache for BOTH old and new time slots
     * - Old slot: Now available again
     * - New slot: Now booked
     */
    public function handleRescheduled(AppointmentRescheduled $event): void
    {
        $appointment = $event->appointment;

        Log::info('🗑️ Invalidating cache for rescheduled appointment', [
            'appointment_id' => $appointment->id,
            'old_starts_at' => $event->oldStartTime->toIso8601String(),
            'new_starts_at' => $event->newStartTime->toIso8601String(),
        ]);

        // Invalidate old time slot (now available)
        $this->invalidateCacheForAppointment($appointment, $event->oldStartTime);

        // Invalidate new time slot (now booked)
        $this->invalidateCacheForAppointment($appointment, $event->newStartTime);
    }

    /**
     * Handle appointment cancelled event
     *
     * Invalidates cache to restore cancelled slot to availability
     */
    public function handleCancelled(AppointmentCancelled $event): void
    {
        $appointment = $event->appointment;

        Log::info('🗑️ Invalidating cache for cancelled appointment (slot now available)', [
            'appointment_id' => $appointment->id,
            'starts_at' => $appointment->starts_at->toIso8601String(),
            'reason' => $event->reason,
        ]);

        $this->invalidateCacheForAppointment($appointment);
    }

    /**
     * Invalidate all cache keys related to an appointment
     *
     * Strategy: Delete cache for entire hour window around appointment time
     * This ensures all potentially overlapping slot queries are invalidated
     *
     * @param \App\Models\Appointment $appointment
     * @param Carbon|null $customStartTime Optional custom start time (for reschedule old slot)
     */
    private function invalidateCacheForAppointment($appointment, ?Carbon $customStartTime = null): void
    {
        try {
            $startTime = $customStartTime ?? $appointment->starts_at;
            $cacheKeys = $this->generateCacheKeys($appointment, $startTime);

            if (empty($cacheKeys)) {
                Log::warning('⚠️ No cache keys generated for invalidation', [
                    'appointment_id' => $appointment->id,
                    'reason' => 'Missing event type ID or invalid data',
                ]);
                return;
            }

            $deletedCount = 0;
            foreach ($cacheKeys as $key) {
                if (Cache::forget($key)) {
                    $deletedCount++;
                }
            }

            Log::info('✅ Cache invalidation complete', [
                'appointment_id' => $appointment->id,
                'keys_deleted' => $deletedCount,
                'total_keys' => count($cacheKeys),
                'time_window' => $this->formatTimeWindow($startTime),
            ]);

        } catch (\Exception $e) {
            // NON-BLOCKING: Log error but don't throw
            // Booking must succeed even if cache invalidation fails
            // Cache will expire naturally (300s TTL) ensuring eventual consistency
            Log::error('❌ Cache invalidation failed (non-critical)', [
                'appointment_id' => $appointment->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    /**
     * Generate all cache keys affected by this appointment
     *
     * 🔧 FIX 2025-10-15: Updated to match ACTUAL cache key formats used by the system
     * Bug: Previous keys didn't match WeeklyAvailabilityService or CalcomService formats
     *
     * Invalidates TWO cache key formats:
     * 1. WeeklyAvailabilityService: week_availability:{teamId}:{serviceId}:{weekStart}
     * 2. CalcomService: calcom:slots:{teamId}:{eventTypeId}:{startDate}:{endDate}
     *
     * @param \App\Models\Appointment $appointment
     * @param Carbon $startTime
     * @return array<string> Array of cache keys
     */
    private function generateCacheKeys($appointment, Carbon $startTime): array
    {
        $keys = [];

        // Load relationships
        $service = $appointment->service;
        $company = $service?->company;

        // Extract required IDs
        $eventTypeId = $service?->calcom_event_type_id;
        $serviceId = $service?->id;
        $teamId = $company?->calcom_team_id;

        if (!$eventTypeId || !$serviceId || !$teamId) {
            Log::warning('⚠️ Missing required IDs for cache invalidation', [
                'appointment_id' => $appointment->id,
                'event_type_id' => $eventTypeId,
                'service_id' => $serviceId,
                'team_id' => $teamId,
            ]);
            return $keys;
        }

        // ============================================================
        // FORMAT 1: WeeklyAvailabilityService Cache Keys
        // Pattern: week_availability:{teamId}:{serviceId}:{weekStart}
        // ============================================================
        $weekStart = $startTime->copy()->startOfWeek()->format('Y-m-d');
        $keys[] = "week_availability:{$teamId}:{$serviceId}:{$weekStart}";

        // Also invalidate previous and next week (for edge cases)
        $prevWeekStart = $startTime->copy()->subWeek()->startOfWeek()->format('Y-m-d');
        $nextWeekStart = $startTime->copy()->addWeek()->startOfWeek()->format('Y-m-d');
        $keys[] = "week_availability:{$teamId}:{$serviceId}:{$prevWeekStart}";
        $keys[] = "week_availability:{$teamId}:{$serviceId}:{$nextWeekStart}";

        // ============================================================
        // FORMAT 2: CalcomService Cache Keys (with wildcard)
        // Pattern: calcom:slots:{teamId}:{eventTypeId}:{startDate}:{endDate}
        // ============================================================
        // We use Redis KEYS command to find all matching cache entries
        // This catches all date ranges that might include this appointment
        $calcomPattern = "calcom:slots:{$teamId}:{$eventTypeId}:*";

        // Use Laravel's cache store to get Redis keys
        try {
            if (Cache::getStore() instanceof \Illuminate\Cache\RedisStore) {
                $redis = Cache::getStore()->connection();
                $matchingKeys = $redis->keys($calcomPattern);
                $keys = array_merge($keys, $matchingKeys);
            }
        } catch (\Exception $e) {
            Log::warning('⚠️ Redis wildcard search failed, using date-based keys', [
                'error' => $e->getMessage()
            ]);

            // Fallback: Generate specific date keys (7 days around appointment)
            for ($i = -3; $i <= 3; $i++) {
                $date = $startTime->copy()->addDays($i)->format('Y-m-d');
                $keys[] = "calcom:slots:{$teamId}:{$eventTypeId}:{$date}:{$date}";
            }
        }

        Log::debug('🔑 Generated cache keys for invalidation (FIX 2025-10-15)', [
            'appointment_id' => $appointment->id,
            'keys_count' => count($keys),
            'sample_keys' => array_slice($keys, 0, 5),
            'team_id' => $teamId,
            'service_id' => $serviceId,
            'event_type_id' => $eventTypeId,
        ]);

        return $keys;
    }

    /**
     * Format time window for logging
     */
    private function formatTimeWindow(Carbon $startTime): string
    {
        $hourBefore = $startTime->copy()->subHour();
        $hourAfter = $startTime->copy()->addHour();

        return sprintf('%s to %s',
            $hourBefore->format('Y-m-d H:i'),
            $hourAfter->format('Y-m-d H:i')
        );
    }
}
