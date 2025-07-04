<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Branch;
use Illuminate\Auth\Access\HandlesAuthorization;

class BranchPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        // Allow if user has the permission
        if ($user->can('view_any_branch')) {
            return true;
        }
        
        // Also allow if user has a company (can view their company's branches)
        if ($user->company_id) {
            return true;
        }
        
        return false;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Branch $branch): bool
    {
        // Allow if user has the permission
        if ($user->can('view_branch')) {
            return true;
        }
        
        // Also allow if branch belongs to user's company
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
        // Allow if user has the permission
        if ($user->can('create_branch')) {
            return true;
        }
        
        // Also allow if user has a company (can create branches for their company)
        if ($user->company_id) {
            return true;
        }
        
        return false;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Branch $branch): bool
    {
        // Allow if user has the permission
        if ($user->can('update_branch')) {
            return true;
        }
        
        // Also allow if branch belongs to user's company
        if ($user->company_id === $branch->company_id) {
            return true;
        }
        
        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Branch $branch): bool
    {
        // Super admin can always delete
        if ($user->hasRole('super_admin')) {
            return true;
        }
        
        // Check specific permission
        if ($user->can('delete_branch')) {
            return true;
        }
        
        // Company admin can delete branches from their own company
        if ($user->hasRole('company_admin') && $user->company_id === $branch->company_id) {
            return true;
        }
        
        return false;
    }

    /**
     * Determine whether the user can bulk delete.
     */
    public function deleteAny(User $user): bool
    {
        // Super admin can always delete
        if ($user->hasRole('super_admin')) {
            return true;
        }
        
        // Check specific permission
        if ($user->can('delete_any_branch')) {
            return true;
        }
        
        // Company admin can bulk delete branches from their own company
        if ($user->hasRole('company_admin') && $user->company_id) {
            return true;
        }
        
        return false;
    }

    /**
     * Determine whether the user can permanently delete.
     */
    public function forceDelete(User $user, Branch $branch): bool
    {
        return $user->can('force_delete_branch');
    }

    /**
     * Determine whether the user can permanently bulk delete.
     */
    public function forceDeleteAny(User $user): bool
    {
        return $user->can('force_delete_any_branch');
    }

    /**
     * Determine whether the user can restore.
     */
    public function restore(User $user, Branch $branch): bool
    {
        return $user->can('restore_branch');
    }

    /**
     * Determine whether the user can bulk restore.
     */
    public function restoreAny(User $user): bool
    {
        return $user->can('restore_any_branch');
    }

    /**
     * Determine whether the user can replicate.
     */
    public function replicate(User $user, Branch $branch): bool
    {
        return $user->can('replicate_branch');
    }

    /**
     * Determine whether the user can reorder.
     */
    public function reorder(User $user): bool
    {
        return $user->can('reorder_branch');
    }
}
