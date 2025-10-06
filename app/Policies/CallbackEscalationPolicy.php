<?php

namespace App\Policies;

use App\Models\CallbackEscalation;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class CallbackEscalationPolicy
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
    public function view(User $user, CallbackEscalation $callbackEscalation): bool
    {
        return $user->company_id === $callbackEscalation->company_id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'manager', 'staff']);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, CallbackEscalation $callbackEscalation): bool
    {
        // Can update if same company AND (admin OR escalated_to staff member)
        if ($user->company_id !== $callbackEscalation->company_id) {
            return false;
        }

        return $user->hasRole('admin') || $user->staff_id === $callbackEscalation->escalated_to;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, CallbackEscalation $callbackEscalation): bool
    {
        return $user->hasRole('admin') && $user->company_id === $callbackEscalation->company_id;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, CallbackEscalation $callbackEscalation): bool
    {
        return $user->hasRole('admin') && $user->company_id === $callbackEscalation->company_id;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, CallbackEscalation $callbackEscalation): bool
    {
        return $user->hasRole('super_admin');
    }
}
