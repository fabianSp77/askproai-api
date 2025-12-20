<?php

namespace App\Policies;

use App\Models\ServiceCaseCategory;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ServiceCaseCategoryPolicy
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
     * Category management is typically admin/manager only.
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
    public function view(User $user, ServiceCaseCategory $category): bool
    {
        // Admin can view all categories
        if ($user->hasRole('admin')) {
            return true;
        }

        // Company isolation
        if ($user->company_id !== $category->company_id) {
            return false;
        }

        // Company managers can view their categories
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
    public function update(User $user, ServiceCaseCategory $category): bool
    {
        // Admin can update all categories
        if ($user->hasRole('admin')) {
            return true;
        }

        // Company isolation
        if ($user->company_id !== $category->company_id) {
            return false;
        }

        // Company managers can update their categories
        if ($user->hasAnyRole(['manager', 'company_owner', 'company_admin'])) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, ServiceCaseCategory $category): bool
    {
        // Cannot delete categories with active cases
        if ($category->cases()->exists()) {
            return false;
        }

        // Admin can delete any category
        if ($user->hasRole('admin')) {
            return true;
        }

        // Company isolation
        if ($user->company_id !== $category->company_id) {
            return false;
        }

        // Managers can delete empty categories
        if ($user->hasAnyRole(['manager', 'company_owner', 'company_admin'])) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, ServiceCaseCategory $category): bool
    {
        if ($user->hasRole('admin')) {
            return true;
        }

        if ($user->hasRole('manager') && $user->company_id === $category->company_id) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, ServiceCaseCategory $category): bool
    {
        return false; // Only super_admin via before()
    }
}
