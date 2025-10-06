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
     */
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'manager', 'staff', 'receptionist']);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Branch $branch): bool
    {
        // Admin can view all branches
        if ($user->hasRole('admin')) {
            return true;
        }

        // Users can view branches from their company
        if ($user->company_id === $branch->company_id) {
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

        // Branch managers can update their branch
        if ($user->hasRole('branch_manager') && $user->branch_id === $branch->id) {
            return true;
        }

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