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
     * Invalidates a 3-hour window:
     * - 1 hour before appointment
     * - Appointment hour
     * - 1 hour after appointment
     *
     * This ensures all slot queries overlapping with this appointment
     * are invalidated, preventing stale cache hits
     *
     * @param \App\Models\Appointment $appointment
     * @param Carbon $startTime
     * @return array<string> Array of cache keys
     */
    private function generateCacheKeys($appointment, Carbon $startTime): array
    {
        $keys = [];

        // Extract event type ID from service
        $eventTypeId = $appointment->service?->calcom_event_type_id;
        if (!$eventTypeId) {
            Log::warning('⚠️ No event type ID found for appointment', [
                'appointment_id' => $appointment->id,
                'service_id' => $appointment->service_id,
            ]);
            return $keys; // Return empty array
        }

        // Tenant isolation fields (prevent cross-tenant cache pollution)
        $companyId = $appointment->company_id ?? 0;
        $branchId = $appointment->branch_id ?? 0;

        // Generate cache keys for 3-hour time window
        // Cache key format matches AppointmentAlternativeFinder::getAvailableSlots()
        // cal_slots_{company_id}_{branch_id}_{event_type_id}_{start_hour}_{end_hour}

        $hourBefore = $startTime->copy()->subHour();
        $appointmentHour = $startTime->copy();
        $hourAfter = $startTime->copy()->addHour();

        foreach ([$hourBefore, $appointmentHour, $hourAfter] as $time) {
            $startHour = $time->format('Y-m-d-H');
            $endHour = $time->copy()->addHour()->format('Y-m-d-H');

            $keys[] = sprintf(
                'cal_slots_%d_%d_%d_%s_%s',
                $companyId,
                $branchId,
                $eventTypeId,
                $startHour,
                $endHour
            );
        }

        Log::debug('🔑 Generated cache keys for invalidation', [
            'appointment_id' => $appointment->id,
            'keys_count' => count($keys),
            'keys' => $keys,
            'time_window' => sprintf('%s to %s',
                $hourBefore->format('Y-m-d H:i'),
                $hourAfter->format('Y-m-d H:i')
            ),
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
