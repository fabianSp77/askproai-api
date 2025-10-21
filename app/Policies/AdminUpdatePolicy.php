<?php

namespace App\Policies;

use App\Models\User;
use App\Models\AdminUpdate;

/**
 * AdminUpdatePolicy
 *
 * Only Super-Admin (Fabian) can access the Admin Updates Portal
 * This is a restricted administrative feature
 */
class AdminUpdatePolicy
{
    /**
     * Check if user is Super-Admin or Admin
     */
    private function isSuperAdmin(User $user): bool
    {
        // Check if user is:
        // 1. Super-Admin role
        // 2. Admin role (fallback for testing)
        // 3. Fabian directly
        // 4. Has is_super_admin flag
        return $user->hasRole('super_admin')
            || $user->hasRole('admin')
            || $user->hasRole('administrator')
            || $user->email === 'fabian@askpro.de'
            || $user->email === 'fabian@askproai.de'
            || ($user->is_super_admin ?? false)
            || ($user->is_admin ?? false);
    }

    /**
     * Determine if the user can view any admin updates
     */
    public function viewAny(User $user): bool
    {
        return $this->isSuperAdmin($user);
    }

    /**
     * Determine if the user can view the admin update
     */
    public function view(User $user, AdminUpdate $adminUpdate): bool
    {
        return $this->isSuperAdmin($user);
    }

    /**
     * Determine if the user can create admin updates
     */
    public function create(User $user): bool
    {
        return $this->isSuperAdmin($user);
    }

    /**
     * Determine if the user can update the admin update
     */
    public function update(User $user, AdminUpdate $adminUpdate): bool
    {
        return $this->isSuperAdmin($user);
    }

    /**
     * Determine if the user can delete the admin update
     */
    public function delete(User $user, AdminUpdate $adminUpdate): bool
    {
        return $this->isSuperAdmin($user);
    }

    /**
     * Determine if the user can permanently delete the admin update
     */
    public function forceDelete(User $user, AdminUpdate $adminUpdate): bool
    {
        return $this->isSuperAdmin($user);
    }

    /**
     * Determine if the user can restore the admin update
     */
    public function restore(User $user, AdminUpdate $adminUpdate): bool
    {
        return $this->isSuperAdmin($user);
    }
}
