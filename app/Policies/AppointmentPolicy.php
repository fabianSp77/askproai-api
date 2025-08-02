<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Appointment;
use Illuminate\Auth\Access\HandlesAuthorization;

class AppointmentPolicy
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
        
        // Users with company_id can view their company's appointments
        if ($user->company_id) {
            return true;
        }
        
        // Check for specific permission
        return $user->can('view_any_appointment');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Appointment $appointment): bool
    {
        // Super admin can always view
        if ($user->hasRole('super_admin')) {
            return true;
        }
        
        // Users can view appointments from their own company
        if ($user->company_id && $appointment->company_id === $user->company_id) {
            return true;
        }
        
        // Check for specific permission
        return $user->can('view_appointment');
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
        
        // Users with company_id can create appointments for their company
        if ($user->company_id) {
            return true;
        }
        
        // Check for specific permission
        return $user->can('create_appointment');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Appointment $appointment): bool
    {
        // Super admin can always update
        if ($user->hasRole('super_admin')) {
            return true;
        }
        
        // Users can update appointments from their own company
        if ($user->company_id && $appointment->company_id === $user->company_id) {
            return true;
        }
        
        // Check for specific permission
        return $user->can('update_appointment');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Appointment $appointment): bool
    {
        // Super admin can always delete
        if ($user->hasRole('super_admin')) {
            return true;
        }
        
        // Users can delete appointments from their own company
        if ($user->company_id && $appointment->company_id === $user->company_id) {
            return true;
        }
        
        // Check for specific permission
        return $user->can('delete_appointment');
    }

    /**
     * Determine whether the user can bulk delete.
     */
    public function deleteAny(User $user): bool
    {
        return $user->can('delete_any_appointment');
    }

    /**
     * Determine whether the user can permanently delete.
     */
    public function forceDelete(User $user, Appointment $appointment): bool
    {
        return $user->can('force_delete_appointment');
    }

    /**
     * Determine whether the user can permanently bulk delete.
     */
    public function forceDeleteAny(User $user): bool
    {
        return $user->can('force_delete_any_appointment');
    }

    /**
     * Determine whether the user can restore.
     */
    public function restore(User $user, Appointment $appointment): bool
    {
        return $user->can('restore_appointment');
    }

    /**
     * Determine whether the user can bulk restore.
     */
    public function restoreAny(User $user): bool
    {
        return $user->can('restore_any_appointment');
    }

    /**
     * Determine whether the user can replicate.
     */
    public function replicate(User $user, Appointment $appointment): bool
    {
        return $user->can('replicate_appointment');
    }

    /**
     * Determine whether the user can reorder.
     */
    public function reorder(User $user): bool
    {
        return $user->can('reorder_appointment');
    }
}
