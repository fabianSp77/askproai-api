<?php

namespace App\Policies;

use App\Models\CallbackRequest;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class CallbackRequestPolicy
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
    public function view(User $user, CallbackRequest $callbackRequest): bool
    {
        // Users can view callback requests from their company
        if ($user->company_id === $callbackRequest->company_id) {
            return true;
        }

        // Staff can view callback requests assigned to them (company check required)
        if ($user->company_id === $callbackRequest->company_id && $user->id === $callbackRequest->assigned_to) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'manager', 'receptionist']);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, CallbackRequest $callbackRequest): bool
    {
        // Admin and managers can update callbacks in their company
        if ($user->hasAnyRole(['admin', 'manager']) && $user->company_id === $callbackRequest->company_id) {
            return true;
        }

        // Assigned staff can update their callback requests (company check required)
        if ($user->company_id === $callbackRequest->company_id && $user->id === $callbackRequest->assigned_to) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, CallbackRequest $callbackRequest): bool
    {
        return $user->hasRole('admin') && $user->company_id === $callbackRequest->company_id;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, CallbackRequest $callbackRequest): bool
    {
        return $user->hasRole('admin') && $user->company_id === $callbackRequest->company_id;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, CallbackRequest $callbackRequest): bool
    {
        return $user->hasRole('super_admin');
    }

    /**
     * Determine whether the user can assign the callback request.
     */
    public function assign(User $user, CallbackRequest $callbackRequest): bool
    {
        return $user->hasAnyRole(['admin', 'manager']) && $user->company_id === $callbackRequest->company_id;
    }

    /**
     * Determine whether the user can complete the callback request.
     */
    public function complete(User $user, CallbackRequest $callbackRequest): bool
    {
        // Admin and managers can complete any callback in their company
        if ($user->hasAnyRole(['admin', 'manager']) && $user->company_id === $callbackRequest->company_id) {
            return true;
        }

        // Assigned staff can complete their own callbacks (company check required)
        if ($user->company_id === $callbackRequest->company_id && $user->id === $callbackRequest->assigned_to) {
            return true;
        }

        return false;
    }
}
