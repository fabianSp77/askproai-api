<?php

namespace App\Policies;

use App\Models\CallForwardingConfiguration;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class CallForwardingConfigurationPolicy
{
    use HandlesAuthorization;

    /**
     * Perform pre-authorization checks.
     * Super admins have full access to everything.
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
        return $user->hasAnyRole(['admin', 'manager']);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, CallForwardingConfiguration $callForwardingConfiguration): bool
    {
        // User can view if configuration belongs to their company
        return $user->company_id === $callForwardingConfiguration->company_id;
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
    public function update(User $user, CallForwardingConfiguration $callForwardingConfiguration): bool
    {
        // Only admins from the same company can update
        return $user->hasRole('admin') && $user->company_id === $callForwardingConfiguration->company_id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, CallForwardingConfiguration $callForwardingConfiguration): bool
    {
        // Only admins from the same company can delete
        return $user->hasRole('admin') && $user->company_id === $callForwardingConfiguration->company_id;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, CallForwardingConfiguration $callForwardingConfiguration): bool
    {
        // Only admins from the same company can restore
        return $user->hasRole('admin') && $user->company_id === $callForwardingConfiguration->company_id;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, CallForwardingConfiguration $callForwardingConfiguration): bool
    {
        // Only super admins can force delete
        return $user->hasRole('super_admin');
    }
}
