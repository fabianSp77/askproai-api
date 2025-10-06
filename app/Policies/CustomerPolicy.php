<?php

namespace App\Policies;

use App\Models\Customer;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class CustomerPolicy
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
    public function view(User $user, Customer $customer): bool
    {
        // Admin can view all customers
        if ($user->hasRole('admin')) {
            return true;
        }

        // Users can view customers from their company
        if ($user->company_id === $customer->company_id) {
            return true;
        }

        // Staff can view their assigned customers
        if ($user->hasRole('staff') && $customer->preferred_staff_id === $user->id) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'manager', 'staff', 'receptionist']);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Customer $customer): bool
    {
        // Admin can update all customers
        if ($user->hasRole('admin')) {
            return true;
        }

        // Users from the same company can update customers
        if ($user->company_id === $customer->company_id &&
            $user->hasAnyRole(['manager', 'staff', 'receptionist'])) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Customer $customer): bool
    {
        // Only admins and managers can delete customers
        if ($user->hasAnyRole(['admin', 'manager'])) {
            // Check if customer belongs to user's company
            if ($user->hasRole('manager')) {
                return $user->company_id === $customer->company_id;
            }
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Customer $customer): bool
    {
        if ($user->hasRole('admin')) {
            return true;
        }

        if ($user->hasRole('manager') && $user->company_id === $customer->company_id) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Customer $customer): bool
    {
        return $user->hasRole('super_admin');
    }

    /**
     * Determine whether the user can view customer's sensitive data.
     */
    public function viewSensitiveData(User $user, Customer $customer): bool
    {
        if ($user->hasAnyRole(['admin', 'manager'])) {
            return true;
        }

        // Staff can view sensitive data of their assigned customers
        if ($user->hasRole('staff') && $customer->preferred_staff_id === $user->id) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can export customer data.
     */
    public function export(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'manager', 'data_protection_officer']);
    }
}