<?php

namespace App\Policies\CustomerPortal;

use App\Models\Appointment;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Customer Portal Appointment Policy
 *
 * AUTHORIZATION RULES:
 * - Users can only access their own appointments
 * - Multi-tenant isolation via customer_id
 * - Time-based permissions (cannot modify past appointments)
 * - Policy-based permissions (minimum notice periods)
 *
 * SECURITY:
 * - Strict ownership validation
 * - No cross-company access
 * - Audit trail for all actions
 */
class AppointmentPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view the appointment.
     *
     * RULES:
     * - User must be associated with a customer account
     * - Appointment must belong to user's customer
     * - Multi-tenant isolation enforced
     */
    public function view(User $user, Appointment $appointment): bool
    {
        // User must have a customer account
        if (!$user->customer_id) {
            return false;
        }

        // Appointment must belong to user's customer
        if ($appointment->customer_id !== $user->customer_id) {
            return false;
        }

        // Multi-tenant isolation (extra safety check)
        if ($appointment->company_id !== $user->company_id) {
            return false;
        }

        return true;
    }

    /**
     * Determine whether the user can reschedule the appointment.
     *
     * RULES:
     * - Must pass view() check (ownership)
     * - Cannot reschedule past appointments
     * - Cannot reschedule cancelled appointments
     * - Must respect minimum notice period
     */
    public function reschedule(User $user, Appointment $appointment): bool
    {
        // Must own the appointment
        if (!$this->view($user, $appointment)) {
            return false;
        }

        // Cannot reschedule past appointments
        if ($appointment->start_time->isPast()) {
            return false;
        }

        // Cannot reschedule cancelled appointments
        if ($appointment->status === 'cancelled') {
            return false;
        }

        // Check minimum notice period
        $minimumNoticeHours = $appointment->company->policyConfiguration
            ?->minimum_reschedule_notice_hours ?? 24;

        if ($appointment->start_time->diffInHours(now()) < $minimumNoticeHours) {
            return false;
        }

        return true;
    }

    /**
     * Determine whether the user can cancel the appointment.
     *
     * RULES:
     * - Must pass view() check (ownership)
     * - Cannot cancel past appointments
     * - Cannot cancel already cancelled appointments
     * - Must respect minimum notice period
     */
    public function cancel(User $user, Appointment $appointment): bool
    {
        // Must own the appointment
        if (!$this->view($user, $appointment)) {
            return false;
        }

        // Cannot cancel past appointments
        if ($appointment->start_time->isPast()) {
            return false;
        }

        // Cannot cancel already cancelled appointments
        if ($appointment->status === 'cancelled') {
            return false;
        }

        // Check minimum notice period
        $minimumNoticeHours = $appointment->company->policyConfiguration
            ?->minimum_cancellation_notice_hours ?? 24;

        if ($appointment->start_time->diffInHours(now()) < $minimumNoticeHours) {
            return false;
        }

        return true;
    }
}
