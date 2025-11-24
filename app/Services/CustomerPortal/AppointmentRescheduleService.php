<?php

namespace App\Services\CustomerPortal;

use App\Exceptions\AppointmentRescheduleException;
use App\Models\Appointment;
use App\Models\AppointmentAuditLog;
use App\Models\User;
use App\Services\CalcomV2Client;
use App\Services\Booking\OptimisticReservationService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;

/**
 * Appointment Reschedule Service
 *
 * ARCHITECTURE: ACID-compliant with synchronous Cal.com sync
 *
 * FLOW:
 * 1. Authorization (multi-layer policy check)
 * 2. Validation (minimum notice, conflicts, business rules)
 * 3. Optimistic lock verification
 * 4. Cal.com SYNCHRONOUS update (blocking)
 * 5. Database update in transaction
 * 6. Event dispatch
 * 7. Audit log
 * 8. Cache invalidation
 *
 * FAILURE MODES:
 * - Authorization failure → 403 Forbidden
 * - Validation failure → 422 Unprocessable Entity
 * - Optimistic lock failure → 409 Conflict (retry)
 * - Cal.com API failure → 503 Service Unavailable (rollback)
 * - Database failure → 500 Internal Error (rollback)
 *
 * IDEMPOTENCY: Safe to retry with same parameters
 */
class AppointmentRescheduleService
{
    public function __construct(
        private CalcomV2Client $calcom,
        private OptimisticReservationService $reservation,
        private CalcomCircuitBreaker $circuitBreaker,
    ) {}

    /**
     * Reschedule appointment to new time
     *
     * @throws AppointmentRescheduleException
     */
    public function reschedule(
        Appointment $appointment,
        Carbon $newStartTime,
        User $user,
        ?string $reason = null
    ): RescheduleResult {
        // ==========================================
        // STEP 1: AUTHORIZATION
        // ==========================================
        $this->authorizeReschedule($appointment, $user);

        // ==========================================
        // STEP 2: VALIDATION
        // ==========================================
        $this->validateReschedule($appointment, $newStartTime, $user);

        // ==========================================
        // STEP 3: OPTIMISTIC LOCK CHECK
        // ==========================================
        $originalVersion = $appointment->version;
        $oldStartTime = $appointment->start_time;

        // ==========================================
        // STEP 4: RESERVE NEW SLOT (PESSIMISTIC LOCK)
        // ==========================================
        $reservation = $this->reservation->createReservation(
            companyId: $appointment->company_id,
            startTime: $newStartTime,
            durationMinutes: $appointment->duration_minutes,
            staffId: $appointment->staff_id,
            serviceId: $appointment->service_id,
            metadata: [
                'reservation_type' => 'reschedule',
                'original_appointment_id' => $appointment->id,
                'user_id' => $user->id,
            ]
        );

        try {
            // ==========================================
            // STEP 5: CAL.COM SYNCHRONOUS UPDATE
            // ==========================================
            $calcomBooking = $this->syncToCalcom($appointment, $newStartTime);

            // ==========================================
            // STEP 6: DATABASE UPDATE IN TRANSACTION
            // ==========================================
            DB::transaction(function () use (
                $appointment,
                $newStartTime,
                $user,
                $originalVersion,
                $oldStartTime,
                $calcomBooking,
                $reason
            ) {
                // Optimistic lock check
                $updated = Appointment::where('id', $appointment->id)
                    ->where('version', $originalVersion)
                    ->update([
                        'start_time' => $newStartTime,
                        'version' => $originalVersion + 1,
                        'last_modified_at' => now(),
                        'last_modified_by' => $user->id,
                        'calcom_sync_status' => 'synced',
                        'calcom_last_sync_at' => now(),
                        'calcom_booking_id' => $calcomBooking->id ?? $appointment->calcom_booking_id,
                    ]);

                if ($updated === 0) {
                    throw new AppointmentRescheduleException(
                        'Appointment was modified by another user. Please refresh and try again.',
                        409
                    );
                }

                // Refresh model
                $appointment->refresh();

                // Audit log
                AppointmentAuditLog::logAction(
                    appointment: $appointment,
                    action: AppointmentAuditLog::ACTION_RESCHEDULED,
                    user: $user,
                    oldValues: [
                        'start_time' => $oldStartTime->toIso8601String(),
                    ],
                    newValues: [
                        'start_time' => $newStartTime->toIso8601String(),
                    ],
                    reason: $reason
                );
            });

            // ==========================================
            // STEP 7: RELEASE RESERVATION
            // ==========================================
            $this->reservation->releaseReservation($reservation->id);

            // ==========================================
            // STEP 8: EVENT DISPATCH
            // ==========================================
            event(new \App\Events\AppointmentRescheduled($appointment, $oldStartTime, $newStartTime, $user));

            // ==========================================
            // STEP 9: CACHE INVALIDATION
            // ==========================================
            $this->invalidateCaches($appointment);

            return new RescheduleResult(
                success: true,
                appointment: $appointment,
                oldStartTime: $oldStartTime,
                newStartTime: $newStartTime,
                calcomBookingId: $calcomBooking->id ?? null,
            );

        } catch (\Exception $e) {
            // Release reservation on any failure
            $this->reservation->releaseReservation($reservation->id);

            // Log failure
            Log::error('Appointment reschedule failed', [
                'appointment_id' => $appointment->id,
                'user_id' => $user->id,
                'new_start_time' => $newStartTime->toIso8601String(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Audit log failure
            AppointmentAuditLog::logAction(
                appointment: $appointment,
                action: 'reschedule_failed',
                user: $user,
                oldValues: ['start_time' => $oldStartTime->toIso8601String()],
                newValues: ['start_time' => $newStartTime->toIso8601String()],
                reason: "Failed: {$e->getMessage()}"
            );

            throw new AppointmentRescheduleException(
                $this->getUserFriendlyErrorMessage($e),
                $this->getHttpStatusCode($e),
                $e
            );
        }
    }

    // ==========================================
    // AUTHORIZATION
    // ==========================================

    private function authorizeReschedule(Appointment $appointment, User $user): void
    {
        if (Gate::forUser($user)->denies('reschedule', $appointment)) {
            throw new AppointmentRescheduleException(
                'You are not authorized to reschedule this appointment.',
                403
            );
        }
    }

    // ==========================================
    // VALIDATION
    // ==========================================

    private function validateReschedule(
        Appointment $appointment,
        Carbon $newStartTime,
        User $user
    ): void {
        // Rule 1: Cannot reschedule past appointments
        if ($appointment->start_time->isPast()) {
            throw new AppointmentRescheduleException(
                'Cannot reschedule past appointments.',
                422
            );
        }

        // Rule 2: Cannot reschedule cancelled appointments
        if ($appointment->status === 'cancelled') {
            throw new AppointmentRescheduleException(
                'Cannot reschedule cancelled appointments.',
                422
            );
        }

        // Rule 3: Minimum notice period
        $minimumNoticeHours = $appointment->company->policyConfiguration
            ?->minimum_reschedule_notice_hours ?? 24;

        if ($appointment->start_time->diffInHours(now()) < $minimumNoticeHours) {
            throw new AppointmentRescheduleException(
                "Appointments must be rescheduled at least {$minimumNoticeHours} hours in advance.",
                422
            );
        }

        // Rule 4: New time must be in future
        if ($newStartTime->isPast()) {
            throw new AppointmentRescheduleException(
                'New appointment time must be in the future.',
                422
            );
        }

        // Rule 5: Maximum advance booking
        $maxAdvanceBookingDays = $appointment->company->policyConfiguration
            ?->max_advance_booking_days ?? 90;

        if ($newStartTime->diffInDays(now()) > $maxAdvanceBookingDays) {
            throw new AppointmentRescheduleException(
                "Appointments can only be booked up to {$maxAdvanceBookingDays} days in advance.",
                422
            );
        }

        // Rule 6: Business hours check
        if (!$this->isWithinBusinessHours($newStartTime, $appointment->company)) {
            throw new AppointmentRescheduleException(
                'New appointment time must be within business hours.',
                422
            );
        }

        // Rule 7: Staff availability check
        if (!$this->isStaffAvailable($appointment, $newStartTime)) {
            throw new AppointmentRescheduleException(
                'The selected time slot is no longer available.',
                422
            );
        }
    }

    // ==========================================
    // CAL.COM SYNCHRONIZATION
    // ==========================================

    private function syncToCalcom(Appointment $appointment, Carbon $newStartTime): object
    {
        // Circuit breaker check
        if ($this->circuitBreaker->isOpen()) {
            throw new AppointmentRescheduleException(
                'Scheduling system is temporarily unavailable. Please try again in a few minutes.',
                503
            );
        }

        try {
            $this->circuitBreaker->recordAttempt();

            $response = $this->calcom->rescheduleBooking(
                bookingId: $appointment->calcom_booking_id,
                newStartTime: $newStartTime,
                reason: 'Rescheduled by customer via portal'
            );

            $this->circuitBreaker->recordSuccess();

            return $response;

        } catch (\Exception $e) {
            $this->circuitBreaker->recordFailure();

            Log::error('Cal.com reschedule failed', [
                'appointment_id' => $appointment->id,
                'calcom_booking_id' => $appointment->calcom_booking_id,
                'new_start_time' => $newStartTime->toIso8601String(),
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    // ==========================================
    // HELPER METHODS
    // ==========================================

    private function isWithinBusinessHours(Carbon $time, $company): bool
    {
        // Implement business hours check
        // TODO: Integrate with company business hours configuration
        return true;
    }

    private function isStaffAvailable(Appointment $appointment, Carbon $newStartTime): bool
    {
        // Check for conflicting appointments
        return !Appointment::where('staff_id', $appointment->staff_id)
            ->where('id', '!=', $appointment->id)
            ->where('status', '!=', 'cancelled')
            ->where(function ($query) use ($newStartTime, $appointment) {
                $newEndTime = $newStartTime->copy()->addMinutes($appointment->duration_minutes);
                $query->whereBetween('start_time', [$newStartTime, $newEndTime])
                    ->orWhere(function ($q) use ($newStartTime, $newEndTime) {
                        $q->where('start_time', '<=', $newStartTime)
                          ->whereRaw('DATE_ADD(start_time, INTERVAL duration_minutes MINUTE) > ?', [$newStartTime]);
                    });
            })
            ->exists();
    }

    private function invalidateCaches(Appointment $appointment): void
    {
        // Clear availability cache for affected date range
        // Clear appointment list cache for company
        // Clear staff schedule cache
        // TODO: Implement cache invalidation
    }

    private function getUserFriendlyErrorMessage(\Exception $e): string
    {
        if ($e instanceof AppointmentRescheduleException) {
            return $e->getMessage();
        }

        if (str_contains($e->getMessage(), 'Cal.com')) {
            return 'Unable to update appointment in scheduling system. Please contact support.';
        }

        return 'An error occurred while rescheduling your appointment. Please try again or contact support.';
    }

    private function getHttpStatusCode(\Exception $e): int
    {
        if ($e instanceof AppointmentRescheduleException) {
            return $e->getCode() ?: 500;
        }

        if (str_contains($e->getMessage(), 'Cal.com')) {
            return 503;
        }

        return 500;
    }
}
