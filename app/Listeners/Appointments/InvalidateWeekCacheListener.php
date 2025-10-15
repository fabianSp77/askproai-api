<?php

namespace App\Listeners\Appointments;

use App\Events\Appointments\AppointmentBooked;
use App\Events\Appointments\AppointmentCancelled;
use App\Events\Appointments\AppointmentRescheduled;
use App\Services\Appointments\WeeklyAvailabilityService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

/**
 * InvalidateWeekCacheListener
 *
 * Invalidates week availability cache when appointments are booked, cancelled, or rescheduled
 * Ensures users always see fresh availability data after changes
 *
 * Handles Events:
 * - AppointmentBooked
 * - AppointmentCancelled
 * - AppointmentRescheduled
 *
 * Performance:
 * - Runs synchronously (must be fast to keep UI responsive)
 * - Clears 4 weeks of cache (current + next 3 weeks)
 * - Service-specific cache keys only (no global invalidation)
 */
class InvalidateWeekCacheListener
{
    protected WeeklyAvailabilityService $weeklyAvailabilityService;

    /**
     * Create the event listener.
     */
    public function __construct(WeeklyAvailabilityService $weeklyAvailabilityService)
    {
        $this->weeklyAvailabilityService = $weeklyAvailabilityService;
    }

    /**
     * Handle appointment booked event
     */
    public function handleBooked(AppointmentBooked $event): void
    {
        $appointment = $event->appointment;

        if (!$appointment->service_id) {
            Log::warning('[InvalidateWeekCache] Appointment has no service_id', [
                'appointment_id' => $appointment->id,
            ]);
            return;
        }

        $this->invalidateServiceCache($appointment->service_id, 'booked', $appointment->id);
    }

    /**
     * Handle appointment cancelled event
     */
    public function handleCancelled(AppointmentCancelled $event): void
    {
        $appointment = $event->appointment;

        if (!$appointment->service_id) {
            Log::warning('[InvalidateWeekCache] Appointment has no service_id', [
                'appointment_id' => $appointment->id,
            ]);
            return;
        }

        $this->invalidateServiceCache($appointment->service_id, 'cancelled', $appointment->id);
    }

    /**
     * Handle appointment rescheduled event
     */
    public function handleRescheduled(AppointmentRescheduled $event): void
    {
        $appointment = $event->appointment;

        if (!$appointment->service_id) {
            Log::warning('[InvalidateWeekCache] Appointment has no service_id', [
                'appointment_id' => $appointment->id,
            ]);
            return;
        }

        $this->invalidateServiceCache($appointment->service_id, 'rescheduled', $appointment->id);
    }

    /**
     * Invalidate cache for a service
     *
     * @param string $serviceId Service UUID
     * @param string $reason Reason for invalidation
     * @param mixed $appointmentId Appointment ID for logging
     * @return void
     */
    protected function invalidateServiceCache(string $serviceId, string $reason, $appointmentId): void
    {
        try {
            // Clear 4 weeks of cache (current + next 3 weeks)
            $this->weeklyAvailabilityService->clearServiceCache($serviceId, 4);

            Log::info('[InvalidateWeekCache] Cache cleared', [
                'service_id' => $serviceId,
                'reason' => $reason,
                'appointment_id' => $appointmentId,
            ]);

        } catch (\Exception $e) {
            // Log error but don't fail the main operation
            Log::error('[InvalidateWeekCache] Failed to clear cache', [
                'service_id' => $serviceId,
                'reason' => $reason,
                'appointment_id' => $appointmentId,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
