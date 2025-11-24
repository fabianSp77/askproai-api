<?php

namespace App\Policies;

use App\Models\Appointment;
use App\Models\User;

/**
 * Appointment Policy Extension for Customer Portal
 *
 * ADDITIONAL METHODS:
 * - reschedule(): Can user reschedule this appointment?
 * - cancel(): Can user cancel this appointment?
 *
 * These methods extend the base AppointmentPolicy with Customer Portal-specific logic.
 * They should be merged into AppointmentPolicy or called via trait.
 *
 * BUSINESS RULES:
 * - Only upcoming appointments can be rescheduled/cancelled
 * - Cannot reschedule past appointments
 * - Cannot reschedule already cancelled appointments
 * - Minimum notice period enforced
 * - Company/branch/staff isolation enforced
 */
trait AppointmentPolicyExtension
{
    /**
     * Determine if user can reschedule appointment
     *
     * AUTHORIZATION LAYERS:
     * 1. Super admin override
     * 2. Company isolation
     * 3. Branch isolation (company_manager)
     * 4. Staff isolation (company_staff)
     * 5. Owner/Admin can reschedule any company appointment
     * 6. Minimum notice period check
     * 7. Appointment status check
     */
    public function reschedule(User $user, Appointment $appointment): bool
    {
        // Layer 1: Super admin override
        if ($user->hasRole('super_admin')) {
            return true;
        }

        // Layer 2: Company isolation (CRITICAL)
        if ($user->company_id !== $appointment->company_id) {
            return false;
        }

        // Layer 3: Cannot reschedule past appointments
        if ($appointment->start_time->isPast()) {
            return false;
        }

        // Layer 4: Cannot reschedule cancelled appointments
        if ($appointment->status === 'cancelled') {
            return false;
        }

        // Layer 5: Minimum notice period check
        $minimumNoticeHours = $appointment->company->policyConfiguration
            ?->minimum_reschedule_notice_hours ?? 24;

        if ($appointment->start_time->diffInHours(now()) < $minimumNoticeHours) {
            return false;
        }

        // Layer 6: Role-based authorization
        if ($user->hasAnyRole(['owner', 'admin', 'company_owner', 'company_admin'])) {
            // Owners and admins can reschedule any company appointment
            return true;
        }

        if ($user->hasRole('company_manager')) {
            // Managers can reschedule appointments in their branch
            if ($user->staff && $user->staff->branch_id) {
                return $appointment->staff?->branch_id === $user->staff->branch_id;
            }
            // Managers without branch assignment can reschedule all
            return true;
        }

        if ($user->hasAnyRole(['staff', 'company_staff'])) {
            // Staff can only reschedule their own appointments
            return $user->staff_id === $appointment->staff_id;
        }

        return false;
    }

    /**
     * Determine if user can cancel appointment
     *
     * AUTHORIZATION LAYERS:
     * Similar to reschedule, with separate minimum notice period
     */
    public function cancel(User $user, Appointment $appointment): bool
    {
        // Layer 1: Super admin override
        if ($user->hasRole('super_admin')) {
            return true;
        }

        // Layer 2: Company isolation (CRITICAL)
        if ($user->company_id !== $appointment->company_id) {
            return false;
        }

        // Layer 3: Cannot cancel past appointments
        if ($appointment->start_time->isPast()) {
            return false;
        }

        // Layer 4: Already cancelled
        if ($appointment->status === 'cancelled') {
            return false;
        }

        // Layer 5: Minimum notice period check
        $minimumNoticeHours = $appointment->company->policyConfiguration
            ?->minimum_cancellation_notice_hours ?? 24;

        if ($appointment->start_time->diffInHours(now()) < $minimumNoticeHours) {
            return false;
        }

        // Layer 6: Role-based authorization (same as reschedule)
        if ($user->hasAnyRole(['owner', 'admin', 'company_owner', 'company_admin'])) {
            return true;
        }

        if ($user->hasRole('company_manager')) {
            if ($user->staff && $user->staff->branch_id) {
                return $appointment->staff?->branch_id === $user->staff->branch_id;
            }
            return true;
        }

        if ($user->hasAnyRole(['staff', 'company_staff'])) {
            return $user->staff_id === $appointment->staff_id;
        }

        return false;
    }

    /**
     * Determine if user can view appointment audit logs
     */
    public function viewAuditLogs(User $user, Appointment $appointment): bool
    {
        // Only owners, admins, and managers can view audit logs
        if (!$user->hasAnyRole(['owner', 'admin', 'company_owner', 'company_admin', 'company_manager'])) {
            return false;
        }

        // Must have view permission first
        return $this->view($user, $appointment);
    }

    /**
     * Determine if user can restore cancelled appointment
     */
    public function restore(User $user, Appointment $appointment): bool
    {
        // Only owners and admins can restore
        if (!$user->hasAnyRole(['owner', 'admin', 'company_owner', 'company_admin'])) {
            return false;
        }

        // Company isolation
        if ($user->company_id !== $appointment->company_id) {
            return false;
        }

        // Must be cancelled
        if ($appointment->status !== 'cancelled') {
            return false;
        }

        // Must be in future
        if ($appointment->start_time->isPast()) {
            return false;
        }

        return true;
    }
}

/**
 * INTEGRATION INSTRUCTIONS:
 *
 * Add this trait to App\Policies\AppointmentPolicy:
 *
 * class AppointmentPolicy
 * {
 *     use HandlesAuthorization, AppointmentPolicyExtension;
 *     // ... existing methods ...
 * }
 *
 * Then register the policy methods in AuthServiceProvider:
 *
 * Gate::define('reschedule-appointment', [AppointmentPolicy::class, 'reschedule']);
 * Gate::define('cancel-appointment', [AppointmentPolicy::class, 'cancel']);
 */
