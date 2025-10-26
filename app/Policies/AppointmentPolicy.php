<?php

namespace App\Policies;

use App\Models\Appointment;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class AppointmentPolicy
{
    use HandlesAuthorization;

    /**
     * Perform pre-authorization checks.
     */
    public function before(User $user, string $ability): ?bool
    {
        if ($user->hasRole('super_admin')) {
            return true;
        }

        return null;
    }

    /**
     * Determine whether the user can view any models.
     *
     * Dual-Role Support:
     * - Admin Panel: admin, manager, staff, receptionist
     * - Customer Portal: company_owner, company_admin, company_manager, company_staff
     */
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole([
            // Admin Panel roles
            'admin',
            'manager',
            'staff',
            'receptionist',
            // Customer Portal roles
            'company_owner',
            'company_admin',
            'company_manager',
            'company_staff',
        ]);
    }

    /**
     * Determine whether the user can view the model.
     *
     * Multi-Level Access Control:
     * 1. Admin: See all appointments (no restrictions)
     * 2. Company isolation: Must belong to same company
     * 3. Branch isolation: company_manager sees only their branch
     * 4. Staff isolation: company_staff sees only their appointments
     */
    public function view(User $user, Appointment $appointment): bool
    {
        // Level 1: Admin can view all appointments
        if ($user->hasRole('admin')) {
            return true;
        }

        // Level 2: Company isolation (CRITICAL for multi-tenancy)
        if ($user->company_id !== $appointment->company_id) {
            return false;
        }

        // Level 3: Branch isolation for company_manager
        // Managers can only see appointments in their assigned branch
        if ($user->hasRole('company_manager') && $user->branch_id) {
            return $user->branch_id === $appointment->branch_id;
        }

        // Level 4: Staff isolation for company_staff
        // Staff can only see appointments they are assigned to
        if ($user->hasAnyRole(['staff', 'company_staff']) && $user->staff_id) {
            return $user->staff_id === $appointment->staff_id;
        }

        // Level 5: Company owners/admins/managers see all company appointments
        // (managers without branch_id assignment see all - for backward compatibility)
        if ($user->hasAnyRole(['manager', 'company_owner', 'company_admin'])) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can create models.
     *
     * Dual-Role Support: Admin Panel + Customer Portal
     */
    public function create(User $user): bool
    {
        return $user->hasAnyRole([
            // Admin Panel roles
            'admin',
            'manager',
            'staff',
            'receptionist',
            // Customer Portal roles (Phase 2 - currently read-only)
            // 'company_owner',
            // 'company_admin',
            // 'company_manager',
        ]);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Appointment $appointment): bool
    {
        // Admin can update all appointments
        if ($user->hasRole('admin')) {
            return true;
        }

        // Company users can update their company's appointments
        if ($user->company_id === $appointment->company_id &&
            $user->hasAnyRole(['manager', 'receptionist'])) {
            return true;
        }

        // Staff can update their own appointments
        if ($user->id === $appointment->staff_id && $user->hasRole('staff')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Appointment $appointment): bool
    {
        // Check if appointment is in the past
        if ($appointment->starts_at < now()) {
            // Only admins can delete past appointments
            return $user->hasRole('admin');
        }

        // Managers can delete future appointments in their company
        if ($user->hasRole('manager') && $user->company_id === $appointment->company_id) {
            return true;
        }

        // Admin can delete any appointment
        return $user->hasRole('admin');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Appointment $appointment): bool
    {
        if ($user->hasRole('admin')) {
            return true;
        }

        if ($user->hasRole('manager') && $user->company_id === $appointment->company_id) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Appointment $appointment): bool
    {
        return $user->hasRole('super_admin');
    }

    /**
     * Determine whether the user can confirm the appointment.
     */
    public function confirm(User $user, Appointment $appointment): bool
    {
        if ($user->hasAnyRole(['admin', 'manager'])) {
            return true;
        }

        // Staff can confirm their own appointments
        if ($user->id === $appointment->staff_id) {
            return true;
        }

        // Receptionists can confirm appointments in their company
        if ($user->hasRole('receptionist') && $user->company_id === $appointment->company_id) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can cancel the appointment.
     */
    public function cancel(User $user, Appointment $appointment): bool
    {
        // Can't cancel completed appointments
        if ($appointment->status === 'completed') {
            return false;
        }

        if ($user->hasAnyRole(['admin', 'manager'])) {
            return true;
        }

        // Staff can cancel their own appointments
        if ($user->id === $appointment->staff_id) {
            return true;
        }

        // Receptionists can cancel appointments in their company
        if ($user->hasRole('receptionist') && $user->company_id === $appointment->company_id) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can reschedule the appointment.
     */
    public function reschedule(User $user, Appointment $appointment): bool
    {
        // Can't reschedule past appointments
        if ($appointment->starts_at < now()) {
            return false;
        }

        return $this->update($user, $appointment);
    }
}