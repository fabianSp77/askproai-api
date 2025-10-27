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
     *
     * Dual-Role Support:
     * - Admin Panel: admin, manager, staff
     * - Customer Portal: company_owner, company_admin, company_manager
     */
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole([
            // Admin Panel roles
            'admin',
            'manager',
            'staff',
            // Customer Portal roles
            'company_owner',
            'company_admin',
            'company_manager',
        ]);
    }

    /**
     * Determine whether the user can view the model.
     *
     * Multi-Level Access Control:
     * 1. Admin: See all staff
     * 2. Company isolation: Must belong to same company
     * 3. Branch isolation: company_manager sees only their branch staff
     * 4. Self: company_staff sees own profile
     */
    public function view(User $user, Staff $staff): bool
    {
        // Level 1: Admin can view all staff
        if ($user->hasRole('admin')) {
            return true;
        }

        // Level 2: Company isolation (CRITICAL for multi-tenancy)
        if ($user->company_id !== $staff->company_id) {
            return false;
        }

        // Level 3: Branch isolation for company_manager
        // Managers can only view staff in their assigned branch
        if ($user->hasRole('company_manager') && $user->branch_id) {
            return $user->branch_id === $staff->branch_id;
        }

        // Level 4: Staff can view their own profile
        if ($user->hasAnyRole(['staff', 'company_staff']) && $user->staff_id) {
            return $user->staff_id === $staff->id;
        }

        // Company owners/admins/managers can view all company staff
        if ($user->hasAnyRole(['manager', 'company_owner', 'company_admin', 'company_manager'])) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can create models.
     *
     * Customer Portal: Read-only in Phase 1 (no create)
     */
    public function create(User $user): bool
    {
        return $user->hasAnyRole([
            // Admin Panel roles
            'admin',
            'manager',
            // Customer Portal roles (Phase 2)
            // 'company_owner',
            // 'company_admin',
        ]);
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

        // Customer Portal: company_manager can update staff in their branch (Phase 2)
        // Currently read-only in Phase 1
        // if ($user->hasRole('company_manager') && $user->branch_id === $staff->branch_id) {
        //     return true;
        // }

        // Staff can update their own basic profile (limited fields)
        if ($user->hasAnyRole(['staff', 'company_staff']) && $user->staff_id) {
            return $user->staff_id === $staff->id; // Limit fields in controller
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