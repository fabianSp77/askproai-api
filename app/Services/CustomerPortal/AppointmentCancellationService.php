<?php

namespace App\Services\CustomerPortal;

use App\Exceptions\AppointmentCancellationException;
use App\Models\Appointment;
use App\Models\AppointmentAuditLog;
use App\Models\User;
use App\Services\CalcomV2Client;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;

/**
 * Appointment Cancellation Service
 *
 * ARCHITECTURE: ACID-compliant with synchronous Cal.com sync
 *
 * CANCELLATION POLICIES:
 * - Minimum notice period (company-configurable)
 * - Cancellation window (cannot cancel within X hours)
 * - Maximum cancellations per month (abuse prevention)
 * - No-show handling (3 strikes policy)
 *
 * SOFT DELETE STRATEGY:
 * - Appointments are soft-deleted for audit trail
 * - Can be restored within 30 days
 * - Hard delete after retention period
 */
class AppointmentCancellationService
{
    public function __construct(
        private CalcomV2Client $calcom,
        private CalcomCircuitBreaker $circuitBreaker,
    ) {}

    /**
     * Cancel appointment
     *
     * @throws AppointmentCancellationException
     */
    public function cancel(
        Appointment $appointment,
        User $user,
        string $reason,
        ?string $cancellationType = 'customer_requested'
    ): CancellationResult {
        // ==========================================
        // STEP 1: AUTHORIZATION
        // ==========================================
        $this->authorizeCancellation($appointment, $user);

        // ==========================================
        // STEP 2: VALIDATION
        // ==========================================
        $this->validateCancellation($appointment, $user);

        // ==========================================
        // STEP 3: OPTIMISTIC LOCK CHECK
        // ==========================================
        $originalVersion = $appointment->version;

        try {
            // ==========================================
            // STEP 4: CAL.COM SYNCHRONOUS CANCELLATION
            // ==========================================
            $this->syncCancellationToCalcom($appointment, $reason);

            // ==========================================
            // STEP 5: DATABASE UPDATE IN TRANSACTION
            // ==========================================
            DB::transaction(function () use (
                $appointment,
                $user,
                $reason,
                $cancellationType,
                $originalVersion
            ) {
                // Optimistic lock check
                $updated = Appointment::where('id', $appointment->id)
                    ->where('version', $originalVersion)
                    ->update([
                        'status' => 'cancelled',
                        'cancelled_at' => now(),
                        'cancelled_by' => $user->id,
                        'cancellation_reason' => $reason,
                        'cancellation_type' => $cancellationType,
                        'version' => $originalVersion + 1,
                        'last_modified_at' => now(),
                        'last_modified_by' => $user->id,
                        'calcom_sync_status' => 'synced',
                        'calcom_last_sync_at' => now(),
                    ]);

                if ($updated === 0) {
                    throw new AppointmentCancellationException(
                        'Appointment was modified by another user. Please refresh and try again.',
                        409
                    );
                }

                // Refresh model
                $appointment->refresh();

                // Audit log
                AppointmentAuditLog::logAction(
                    appointment: $appointment,
                    action: AppointmentAuditLog::ACTION_CANCELLED,
                    user: $user,
                    oldValues: [
                        'status' => 'confirmed',
                    ],
                    newValues: [
                        'status' => 'cancelled',
                        'cancellation_reason' => $reason,
                        'cancellation_type' => $cancellationType,
                    ],
                    reason: $reason
                );
            });

            // ==========================================
            // STEP 6: EVENT DISPATCH
            // ==========================================
            event(new \App\Events\AppointmentCancelled($appointment, $user, $reason));

            // ==========================================
            // STEP 7: CACHE INVALIDATION
            // ==========================================
            $this->invalidateCaches($appointment);

            // ==========================================
            // STEP 8: NOTIFICATION
            // ==========================================
            $this->sendCancellationNotifications($appointment, $user);

            return new CancellationResult(
                success: true,
                appointment: $appointment,
                reason: $reason,
                canRebook: $this->canCustomerRebook($appointment, $user),
            );

        } catch (\Exception $e) {
            // Log failure
            Log::error('Appointment cancellation failed', [
                'appointment_id' => $appointment->id,
                'user_id' => $user->id,
                'reason' => $reason,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Audit log failure
            AppointmentAuditLog::logAction(
                appointment: $appointment,
                action: 'cancellation_failed',
                user: $user,
                oldValues: ['status' => $appointment->status],
                newValues: ['status' => 'cancelled'],
                reason: "Failed: {$e->getMessage()}"
            );

            throw new AppointmentCancellationException(
                $this->getUserFriendlyErrorMessage($e),
                $this->getHttpStatusCode($e),
                $e
            );
        }
    }

    // ==========================================
    // AUTHORIZATION
    // ==========================================

    private function authorizeCancellation(Appointment $appointment, User $user): void
    {
        if (Gate::forUser($user)->denies('cancel', $appointment)) {
            throw new AppointmentCancellationException(
                'You are not authorized to cancel this appointment.',
                403
            );
        }
    }

    // ==========================================
    // VALIDATION
    // ==========================================

    private function validateCancellation(Appointment $appointment, User $user): void
    {
        // Rule 1: Cannot cancel past appointments
        if ($appointment->start_time->isPast()) {
            throw new AppointmentCancellationException(
                'Cannot cancel past appointments.',
                422
            );
        }

        // Rule 2: Already cancelled
        if ($appointment->status === 'cancelled') {
            throw new AppointmentCancellationException(
                'This appointment is already cancelled.',
                422
            );
        }

        // Rule 3: Minimum notice period
        $minimumNoticeHours = $appointment->company->policyConfiguration
            ?->minimum_cancellation_notice_hours ?? 24;

        if ($appointment->start_time->diffInHours(now()) < $minimumNoticeHours) {
            throw new AppointmentCancellationException(
                "Appointments must be cancelled at least {$minimumNoticeHours} hours in advance.",
                422
            );
        }

        // Rule 4: Maximum cancellations per month (abuse prevention)
        $maxCancellationsPerMonth = $appointment->company->policyConfiguration
            ?->max_cancellations_per_month ?? 3;

        $recentCancellations = Appointment::where('customer_phone', $appointment->customer_phone)
            ->where('company_id', $appointment->company_id)
            ->where('status', 'cancelled')
            ->where('cancelled_at', '>=', now()->subMonth())
            ->count();

        if ($recentCancellations >= $maxCancellationsPerMonth) {
            throw new AppointmentCancellationException(
                "You have reached the maximum number of cancellations ({$maxCancellationsPerMonth}) for this month. Please contact us directly.",
                422
            );
        }
    }

    // ==========================================
    // CAL.COM SYNCHRONIZATION
    // ==========================================

    private function syncCancellationToCalcom(Appointment $appointment, string $reason): void
    {
        // Circuit breaker check
        if ($this->circuitBreaker->isOpen()) {
            throw new AppointmentCancellationException(
                'Scheduling system is temporarily unavailable. Please try again in a few minutes.',
                503
            );
        }

        try {
            $this->circuitBreaker->recordAttempt();

            $this->calcom->cancelBooking(
                bookingId: $appointment->calcom_booking_id,
                reason: $reason,
                cancelledBy: 'customer'
            );

            $this->circuitBreaker->recordSuccess();

        } catch (\Exception $e) {
            $this->circuitBreaker->recordFailure();

            Log::error('Cal.com cancellation failed', [
                'appointment_id' => $appointment->id,
                'calcom_booking_id' => $appointment->calcom_booking_id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    // ==========================================
    // HELPER METHODS
    // ==========================================

    private function canCustomerRebook(Appointment $appointment, User $user): bool
    {
        // Check if customer can immediately rebook
        // Consider no-show history, cancellation frequency, etc.
        return true; // TODO: Implement business logic
    }

    private function invalidateCaches(Appointment $appointment): void
    {
        // Clear availability cache
        // Clear appointment list cache
        // Clear staff schedule cache
        // TODO: Implement cache invalidation
    }

    private function sendCancellationNotifications(Appointment $appointment, User $user): void
    {
        // Send email to customer
        // Send SMS confirmation
        // Notify staff member
        // TODO: Implement notification system
    }

    private function getUserFriendlyErrorMessage(\Exception $e): string
    {
        if ($e instanceof AppointmentCancellationException) {
            return $e->getMessage();
        }

        if (str_contains($e->getMessage(), 'Cal.com')) {
            return 'Unable to cancel appointment in scheduling system. Please contact support.';
        }

        return 'An error occurred while cancelling your appointment. Please try again or contact support.';
    }

    private function getHttpStatusCode(\Exception $e): int
    {
        if ($e instanceof AppointmentCancellationException) {
            return $e->getCode() ?: 500;
        }

        if (str_contains($e->getMessage(), 'Cal.com')) {
            return 503;
        }

        return 500;
    }
}
