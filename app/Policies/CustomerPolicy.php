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
     *
     * Dual-Role Support:
     * - Admin Panel: admin, manager, staff, receptionist
     * - Customer Portal: company_owner, company_admin, company_manager, company_staff
     */
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole([
            // Admin Panel roles
            'admin',
            'manager',
            'staff',
            'receptionist',
            // Customer Portal roles
            'company_owner',
            'company_admin',
            'company_manager',
            'company_staff',
        ]);
    }

    /**
     * Determine whether the user can view the model.
     *
     * Multi-Level Access Control:
     * 1. Admin: See all customers
     * 2. Company isolation: Must belong to same company
     * 3. Branch isolation: company_manager sees only their branch customers
     * 4. Staff isolation: company_staff sees only assigned customers
     */
    public function view(User $user, Customer $customer): bool
    {
        // Level 1: Admin can view all customers
        if ($user->hasRole('admin')) {
            return true;
        }

        // Level 2: Company isolation (CRITICAL for multi-tenancy)
        if ($user->company_id !== $customer->company_id) {
            return false;
        }

        // Level 3: Branch isolation for company_manager
        // Managers can only view customers from their assigned branch
        if ($user->hasRole('company_manager') && $user->branch_id) {
            return $user->branch_id === $customer->branch_id;
        }

        // Level 4: Staff isolation for company_staff
        // Staff can view customers assigned to them (preferred_staff_id points to staff.id, not user.id)
        if ($user->hasAnyRole(['staff', 'company_staff']) && $user->staff_id) {
            return $user->staff_id === $customer->preferred_staff_id;
        }

        // Company owners/admins can view all company customers
        if ($user->hasAnyRole(['manager', 'company_owner', 'company_admin'])) {
            return true;
        }

        // Backward compatibility: other company users can view company customers
        return $user->company_id === $customer->company_id;
    }

    /**
     * Determine whether the user can create models.
     *
     * Customer Portal: Read-only in Phase 1 (no create)
     */
    public function create(User $user): bool
    {
        return $user->hasAnyRole([
            // Admin Panel roles
            'admin',
            'manager',
            'staff',
            'receptionist',
            // Customer Portal roles (Phase 2)
            // 'company_owner',
            // 'company_admin',
        ]);
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