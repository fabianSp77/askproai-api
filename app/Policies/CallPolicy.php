<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Call;
use Illuminate\Auth\Access\HandlesAuthorization;

class CallPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        // Super admin can always view
        if ($user->hasRole('super_admin')) {
            return true;
        }
        
        // Users with company_id can view their company's calls
        if ($user->company_id) {
            return true;
        }
        
        // Check for specific permission
        return $user->can('view_any_call');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Call $call): bool
    {
        // Super admin can always view
        if ($user->hasRole('super_admin')) {
            return true;
        }
        
        // Users can view calls from their own company
        if ($user->company_id && $call->company_id === $user->company_id) {
            return true;
        }
        
        // Check for specific permission
        return $user->can('view_call');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // Super admin can always create
        if ($user->hasRole('super_admin')) {
            return true;
        }
        
        // Users with company_id can create calls for their company
        if ($user->company_id) {
            return true;
        }
        
        // Check for specific permission
        return $user->can('create_call');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Call $call): bool
    {
        // Super admin can always update
        if ($user->hasRole('super_admin')) {
            return true;
        }
        
        // Users can update calls from their own company
        if ($user->company_id && $call->company_id === $user->company_id) {
            return true;
        }
        
        // Check for specific permission
        return $user->can('update_call');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Call $call): bool
    {
        // Super admin can always delete
        if ($user->hasRole('super_admin')) {
            return true;
        }
        
        // Users can delete calls from their own company
        if ($user->company_id && $call->company_id === $user->company_id) {
            return true;
        }
        
        // Check for specific permission
        return $user->can('delete_call');
    }

    /**
     * Determine whether the user can bulk delete.
     */
    public function deleteAny(User $user): bool
    {
        return $user->can('delete_any_call');
    }

    /**
     * Determine whether the user can permanently delete.
     */
    public function forceDelete(User $user, Call $call): bool
    {
        return $user->can('force_delete_call');
    }

    /**
     * Determine whether the user can permanently bulk delete.
     */
    public function forceDeleteAny(User $user): bool
    {
        return $user->can('force_delete_any_call');
    }

    /**
     * Determine whether the user can restore.
     */
    public function restore(User $user, Call $call): bool
    {
        return $user->can('restore_call');
    }

    /**
     * Determine whether the user can bulk restore.
     */
    public function restoreAny(User $user): bool
    {
        return $user->can('restore_any_call');
    }

    /**
     * Determine whether the user can replicate.
     */
    public function replicate(User $user, Call $call): bool
    {
        return $user->can('replicate_call');
    }

    /**
     * Determine whether the user can reorder.
     */
    public function reorder(User $user): bool
    {
        return $user->can('reorder_call');
    }
}
