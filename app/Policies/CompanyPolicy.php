<?php

namespace App\Policies;

use App\Models\Company;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class CompanyPolicy
{
    use HandlesAuthorization;

    /**
     * Perform pre-authorization checks.
     */
    public function before(User $user, string $ability): ?bool
    {
        // Super admins can do everything
        // FIX 2025-11-05: Check all variations of super_admin role name
        if ($user->hasAnyRole(['super_admin', 'Super Admin', 'super-admin'])) {
            return true;
        }

        return null;
    }

    /**
     * Determine whether the user can view any models.
     *
     * FIX 2025-11-05: Added super_admin and Admin (capitalized) variants
     * to fix missing menu items for Super Admin users
     */
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole([
            'super_admin',     // super_admin variant
            'Super Admin',     // Super Admin variant (with space)
            'admin',           // admin variant
            'Admin',           // Admin variant (capitalized)
            'manager',
            'staff'
        ]);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Company $company): bool
    {
        // Admin can view all companies
        if ($user->hasRole('admin')) {
            return true;
        }

        // Users can view their own company
        if ($user->company_id === $company->id) {
            return true;
        }

        // Managers can view companies in their tenant
        if ($user->hasRole('manager') && $user->tenant_id === $company->tenant_id) {
            return true;
        }

        return false;
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
    public function update(User $user, Company $company): bool
    {
        // Admin can update all companies
        if ($user->hasRole('admin')) {
            return true;
        }

        // Managers can update companies in their tenant
        if ($user->hasRole('manager') && $user->tenant_id === $company->tenant_id) {
            return true;
        }

        // Company owners can update their own company
        if ($user->company_id === $company->id && $user->hasRole('company_owner')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Company $company): bool
    {
        // Only admins can delete companies
        return $user->hasRole('admin');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Company $company): bool
    {
        return $user->hasRole('admin');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Company $company): bool
    {
        return $user->hasRole('super_admin');
    }

    /**
     * Determine whether the user can manage billing.
     */
    public function manageBilling(User $user, Company $company): bool
    {
        if ($user->hasRole('admin')) {
            return true;
        }

        if ($user->company_id === $company->id && $user->hasAnyRole(['company_owner', 'billing_manager'])) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can manage integrations.
     */
    public function manageIntegrations(User $user, Company $company): bool
    {
        if ($user->hasRole('admin')) {
            return true;
        }

        if ($user->company_id === $company->id && $user->hasRole('company_owner')) {
            return true;
        }

        return false;
    }
}