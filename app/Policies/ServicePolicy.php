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
        if ($user->hasRole('super_admin')) {
            return true;
        }
        return null;
    }

    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'manager', 'staff', 'receptionist']);
    }

    public function view(User $user, Service $service): bool
    {
        if ($user->hasRole('admin')) {
            return true;
        }
        return $user->company_id === $service->company_id;
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'manager']);
    }

    public function update(User $user, Service $service): bool
    {
        if ($user->hasRole('admin')) {
            return true;
        }
        if ($user->hasRole('manager') && $user->company_id === $service->company_id) {
            return true;
        }
        return false;
    }

    public function delete(User $user, Service $service): bool
    {
        if ($user->hasRole('admin')) {
            return true;
        }
        if ($user->hasRole('manager') && $user->company_id === $service->company_id) {
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