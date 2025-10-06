<?php

namespace App\Policies;

use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class SystemSettingPolicy
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
    public function view(User $user, SystemSetting $systemSetting): bool
    {
        // Global settings are viewable by all admins/managers
        if ($systemSetting->company_id === null) {
            return $user->hasAnyRole(['admin', 'manager']);
        }

        // Company-specific settings require company match
        return $user->company_id === $systemSetting->company_id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasRole('admin');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, SystemSetting $systemSetting): bool
    {
        // Global settings only editable by super_admin (handled in before())
        if ($systemSetting->company_id === null) {
            return false;
        }

        // Company settings editable by company admin
        return $user->hasRole('admin') && $user->company_id === $systemSetting->company_id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, SystemSetting $systemSetting): bool
    {
        // Global settings only deletable by super_admin
        if ($systemSetting->company_id === null) {
            return false;
        }

        return $user->hasRole('admin') && $user->company_id === $systemSetting->company_id;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, SystemSetting $systemSetting): bool
    {
        if ($systemSetting->company_id === null) {
            return false;
        }

        return $user->hasRole('admin') && $user->company_id === $systemSetting->company_id;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, SystemSetting $systemSetting): bool
    {
        return $user->hasRole('super_admin');
    }
}
