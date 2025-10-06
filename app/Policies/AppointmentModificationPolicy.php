<?php

namespace App\Policies;

use App\Models\AppointmentModification;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class AppointmentModificationPolicy
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
        return $user->hasAnyRole(['admin', 'manager']);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, AppointmentModification $appointmentModification): bool
    {
        // Users can view modifications from their company
        if ($user->company_id === $appointmentModification->company_id) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // AppointmentModifications are typically created by system
        // Allow admins and managers to manually create if needed
        return $user->hasAnyRole(['admin', 'manager']);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, AppointmentModification $appointmentModification): bool
    {
        // Modifications are immutable audit records
        // Only super_admin can update (handled in before())
        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, AppointmentModification $appointmentModification): bool
    {
        // Modifications are audit records, typically not deleted
        // Only allow admin to delete from their company if needed
        return $user->hasRole('admin') && $user->company_id === $appointmentModification->company_id;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, AppointmentModification $appointmentModification): bool
    {
        return $user->hasRole('admin') && $user->company_id === $appointmentModification->company_id;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, AppointmentModification $appointmentModification): bool
    {
        return $user->hasRole('super_admin');
    }
}
