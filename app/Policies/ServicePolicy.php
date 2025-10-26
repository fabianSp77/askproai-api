<?php

namespace App\Policies;

use App\Models\Service;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ServicePolicy
{
    use HandlesAuthorization;

    public function before(User $user, string $ability): ?bool
    {
        // Allow all super admin role variations
        if ($user->hasAnyRole(['super_admin', 'Super Admin', 'super-admin'])) {
            return true;
        }
        return null;
    }

    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole([
            'admin', 'Admin',
            'manager',
            'staff',
            'receptionist',
            'company_owner',
            'company_admin',
            'company_manager',
            'reseller_owner',
            'reseller_admin'
        ]);
    }

    public function view(User $user, Service $service): bool
    {
        // Admins can view all
        if ($user->hasAnyRole(['admin', 'Admin', 'reseller_owner', 'reseller_admin'])) {
            return true;
        }

        // Company isolation (CRITICAL for multi-tenancy)
        if ($user->company_id !== $service->company_id) {
            return false;
        }

        // Branch isolation for company_manager
        // Note: Services are company-wide, not branch-specific
        // So company_manager can see all company services
        if ($user->hasRole('company_manager') && $user->branch_id) {
            // Allow viewing (services are company-wide resources)
            return true;
        }

        // Company users can view their own company's services
        return $user->company_id === $service->company_id;
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole([
            'admin', 'Admin',
            'manager',
            'company_owner',
            'company_admin',
            'company_manager',
            'reseller_owner',
            'reseller_admin'
        ]);
    }

    public function update(User $user, Service $service): bool
    {
        // Admins can update all
        if ($user->hasAnyRole(['admin', 'Admin', 'reseller_owner', 'reseller_admin'])) {
            return true;
        }
        // Company managers/owners can update their own company's services
        if ($user->hasAnyRole(['manager', 'company_owner', 'company_admin', 'company_manager'])
            && $user->company_id === $service->company_id) {
            return true;
        }
        return false;
    }

    public function delete(User $user, Service $service): bool
    {
        // Admins can delete all
        if ($user->hasAnyRole(['admin', 'Admin', 'reseller_owner', 'reseller_admin'])) {
            return true;
        }
        // Company managers/owners can delete their own company's services
        if ($user->hasAnyRole(['manager', 'company_owner', 'company_admin', 'company_manager'])
            && $user->company_id === $service->company_id) {
            return true;
        }
        return false;
    }

    public function restore(User $user, Service $service): bool
    {
        return $this->delete($user, $service);
    }

    public function forceDelete(User $user, Service $service): bool
    {
        return $user->hasRole('super_admin');
    }
}