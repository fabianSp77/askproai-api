<?php

namespace App\Policies;

use App\Models\ServiceOutputConfiguration;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ServiceOutputConfigurationPolicy
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
     *
     * Output configuration is admin/manager only (sensitive settings).
     */
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole([
            'admin',
            'manager',
            'company_owner',
            'company_admin',
        ]);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, ServiceOutputConfiguration $config): bool
    {
        // Admin can view all configurations
        if ($user->hasRole('admin')) {
            return true;
        }

        // Company isolation
        if ($user->company_id !== $config->company_id) {
            return false;
        }

        // Company managers can view their configurations
        if ($user->hasAnyRole(['manager', 'company_owner', 'company_admin'])) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasAnyRole([
            'admin',
            'manager',
            'company_owner',
            'company_admin',
        ]);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, ServiceOutputConfiguration $config): bool
    {
        // Admin can update all configurations
        if ($user->hasRole('admin')) {
            return true;
        }

        // Company isolation
        if ($user->company_id !== $config->company_id) {
            return false;
        }

        // Company managers can update their configurations
        if ($user->hasAnyRole(['manager', 'company_owner', 'company_admin'])) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, ServiceOutputConfiguration $config): bool
    {
        // Cannot delete configurations that are in use
        if ($config->categories()->exists()) {
            return false;
        }

        // Admin can delete any configuration
        if ($user->hasRole('admin')) {
            return true;
        }

        // Company isolation
        if ($user->company_id !== $config->company_id) {
            return false;
        }

        // Managers can delete unused configurations
        if ($user->hasAnyRole(['manager', 'company_owner', 'company_admin'])) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, ServiceOutputConfiguration $config): bool
    {
        if ($user->hasRole('admin')) {
            return true;
        }

        if ($user->hasRole('manager') && $user->company_id === $config->company_id) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, ServiceOutputConfiguration $config): bool
    {
        return false; // Only super_admin via before()
    }

    /**
     * Determine whether the user can test the output configuration.
     */
    public function test(User $user, ServiceOutputConfiguration $config): bool
    {
        // Admin can test any configuration
        if ($user->hasRole('admin')) {
            return true;
        }

        // Company isolation
        if ($user->company_id !== $config->company_id) {
            return false;
        }

        // Managers can test their configurations
        if ($user->hasAnyRole(['manager', 'company_owner', 'company_admin'])) {
            return true;
        }

        return false;
    }
}
