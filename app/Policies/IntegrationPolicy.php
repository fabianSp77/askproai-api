<?php

namespace App\Policies;

use App\Models\Integration;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class IntegrationPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('view_any_integration');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Integration $integration): bool
    {
        return $user->can('view_integration');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('create_integration');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Integration $integration): bool
    {
        return $user->can('update_integration');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Integration $integration): bool
    {
        return $user->can('delete_integration');
    }

    /**
     * Determine whether the user can bulk delete.
     */
    public function deleteAny(User $user): bool
    {
        return $user->can('delete_any_integration');
    }

    /**
     * Determine whether the user can permanently delete.
     */
    public function forceDelete(User $user, Integration $integration): bool
    {
        return $user->can('force_delete_integration');
    }

    /**
     * Determine whether the user can permanently bulk delete.
     */
    public function forceDeleteAny(User $user): bool
    {
        return $user->can('force_delete_any_integration');
    }

    /**
     * Determine whether the user can restore.
     */
    public function restore(User $user, Integration $integration): bool
    {
        return $user->can('restore_integration');
    }

    /**
     * Determine whether the user can bulk restore.
     */
    public function restoreAny(User $user): bool
    {
        return $user->can('restore_any_integration');
    }

    /**
     * Determine whether the user can replicate.
     */
    public function replicate(User $user, Integration $integration): bool
    {
        return $user->can('replicate_integration');
    }

    /**
     * Determine whether the user can reorder.
     */
    public function reorder(User $user): bool
    {
        return $user->can('reorder_integration');
    }
}
