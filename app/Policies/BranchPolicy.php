<?php

namespace App\Policies;

use App\Models\Branch;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class BranchPolicy
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
     * - Customer Portal: company_owner, company_admin, company_manager
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
        ]);
    }

    /**
     * Determine whether the user can view the model.
     *
     * Multi-Level Access Control:
     * 1. Admin: See all branches
     * 2. Company isolation: Must belong to same company
     * 3. Branch isolation: company_manager sees only their branch
     */
    public function view(User $user, Branch $branch): bool
    {
        // Level 1: Admin can view all branches
        if ($user->hasRole('admin')) {
            return true;
        }

        // Level 2: Company isolation (CRITICAL for multi-tenancy)
        if ($user->company_id !== $branch->company_id) {
            return false;
        }

        // Level 3: Branch isolation for company_manager
        // Managers can only view their assigned branch
        if ($user->hasRole('company_manager') && $user->branch_id) {
            return $user->branch_id === $branch->id;
        }

        // Company owners/admins can view all company branches
        if ($user->hasAnyRole(['manager', 'company_owner', 'company_admin'])) {
            return true;
        }

        // Other company users can view branches (backward compatibility)
        return $user->company_id === $branch->company_id;
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
        ]);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Branch $branch): bool
    {
        // Admin can update all branches
        if ($user->hasRole('admin')) {
            return true;
        }

        // Managers can update branches in their company
        if ($user->hasRole('manager') && $user->company_id === $branch->company_id) {
            return true;
        }

        // Branch managers can update their branch (old role)
        if ($user->hasRole('branch_manager') && $user->branch_id === $branch->id) {
            return true;
        }

        // Customer Portal: company_manager can update their assigned branch (Phase 2)
        // Currently read-only in Phase 1
        // if ($user->hasRole('company_manager') && $user->branch_id === $branch->id) {
        //     return true;
        // }

        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Branch $branch): bool
    {
        // Can't delete the main branch
        if ($branch->is_main) {
            return false;
        }

        // Only admins and managers can delete branches
        if ($user->hasRole('admin')) {
            return true;
        }

        if ($user->hasRole('manager') && $user->company_id === $branch->company_id) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Branch $branch): bool
    {
        if ($user->hasRole('admin')) {
            return true;
        }

        if ($user->hasRole('manager') && $user->company_id === $branch->company_id) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Branch $branch): bool
    {
        return $user->hasRole('super_admin');
    }

    /**
     * Determine whether the user can manage branch services.
     */
    public function manageServices(User $user, Branch $branch): bool
    {
        if ($user->hasRole('admin')) {
            return true;
        }

        // Company managers can manage services
        if ($user->hasRole('manager') && $user->company_id === $branch->company_id) {
            return true;
        }

        // Branch managers can manage their branch's services
        if ($user->hasRole('branch_manager') && $user->branch_id === $branch->id) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can manage branch staff.
     */
    public function manageStaff(User $user, Branch $branch): bool
    {
        if ($user->hasRole('admin')) {
            return true;
        }

        // Company managers can manage staff assignments
        if ($user->hasRole('manager') && $user->company_id === $branch->company_id) {
            return true;
        }

        // Branch managers can manage their branch's staff
        if ($user->hasRole('branch_manager') && $user->branch_id === $branch->id) {
            return true;
        }

        return false;
    }
}