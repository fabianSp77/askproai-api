<?php

namespace App\Policies;

use App\Models\Staff;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class StaffPolicy
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
     */
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'manager', 'staff']);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Staff $staff): bool
    {
        // Admin can view all staff
        if ($user->hasRole('admin')) {
            return true;
        }

        // Users can view staff from their company
        if ($user->company_id === $staff->company_id) {
            return true;
        }

        // Staff can view their own profile
        if ($user->staff_id === $staff->id) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'manager']);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Staff $staff): bool
    {
        // Admin can update all staff
        if ($user->hasRole('admin')) {
            return true;
        }

        // Managers can update staff in their company
        if ($user->hasRole('manager') && $user->company_id === $staff->company_id) {
            return true;
        }

        // Staff can update their own basic profile (limited fields)
        if ($user->staff_id === $staff->id) {
            return true; // You should limit which fields in the controller
        }

        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Staff $staff): bool
    {
        // Only admins and managers can delete staff
        if ($user->hasRole('admin')) {
            return true;
        }

        if ($user->hasRole('manager') && $user->company_id === $staff->company_id) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Staff $staff): bool
    {
        if ($user->hasRole('admin')) {
            return true;
        }

        if ($user->hasRole('manager') && $user->company_id === $staff->company_id) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Staff $staff): bool
    {
        return $user->hasRole('super_admin');
    }

    /**
     * Determine whether the user can manage staff schedule.
     */
    public function manageSchedule(User $user, Staff $staff): bool
    {
        if ($user->hasRole('admin')) {
            return true;
        }

        // Managers can manage schedules in their company
        if ($user->hasRole('manager') && $user->company_id === $staff->company_id) {
            return true;
        }

        // Staff can manage their own schedule if allowed
        if ($user->staff_id === $staff->id && $staff->can_manage_own_schedule) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can view staff performance.
     */
    public function viewPerformance(User $user, Staff $staff): bool
    {
        if ($user->hasAnyRole(['admin', 'manager'])) {
            return $user->company_id === $staff->company_id || $user->hasRole('admin');
        }

        // Staff can view their own performance
        if ($user->staff_id === $staff->id) {
            return true;
        }

        return false;
    }
}