<?php

namespace App\Policies;

use App\Models\CalcomEventType;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class CalcomEventTypePolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        // Super Admin und Company Users können Event Types sehen
        return $user->hasRole('super_admin') || $user->company_id !== null;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, CalcomEventType $calcomEventType): bool
    {
        // Super Admin kann alles sehen
        if ($user->hasRole('super_admin')) {
            return true;
        }
        
        // User kann nur Event Types seiner Company sehen
        return $user->company_id === $calcomEventType->company_id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // Nur Super Admin und Company Admin können erstellen
        return $user->hasRole(['super_admin', 'admin']) || 
               ($user->company_id !== null && $user->is_admin);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, CalcomEventType $calcomEventType): bool
    {
        // Super Admin kann alles bearbeiten
        if ($user->hasRole('super_admin')) {
            return true;
        }
        
        // User kann nur Event Types seiner Company bearbeiten
        return $user->company_id === $calcomEventType->company_id 
            && ($user->is_admin || $user->hasRole('admin'));
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, CalcomEventType $calcomEventType): bool
    {
        // Super Admin kann alles löschen
        if ($user->hasRole('super_admin')) {
            return true;
        }
        
        // User kann nur Event Types seiner Company löschen
        return $user->company_id === $calcomEventType->company_id 
            && ($user->is_admin || $user->hasRole('admin'));
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, CalcomEventType $calcomEventType): bool
    {
        return $this->delete($user, $calcomEventType);
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, CalcomEventType $calcomEventType): bool
    {
        return $user->hasRole('super_admin');
    }
    
    /**
     * Determine whether the user can manage staff assignments.
     */
    public function manageStaffAssignments(User $user, CalcomEventType $calcomEventType): bool
    {
        // Super Admin kann alles
        if ($user->hasRole('super_admin')) {
            return true;
        }
        
        // User kann nur Mitarbeiter-Zuordnungen seiner Company verwalten
        return $user->company_id === $calcomEventType->company_id 
            && ($user->is_admin || $user->hasRole('admin'));
    }
    
    /**
     * Determine whether the user can sync with Cal.com.
     */
    public function sync(User $user): bool
    {
        // Nur Admins können synchronisieren
        return $user->hasRole(['super_admin', 'admin']) || 
               ($user->company_id !== null && $user->is_admin);
    }
}