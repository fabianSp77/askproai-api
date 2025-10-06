<?php

namespace App\Policies;

use App\Models\PhoneNumber;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class PhoneNumberPolicy
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
        return $user->hasAnyRole(['admin', 'manager', 'staff']);
    }

    public function view(User $user, PhoneNumber $phoneNumber): bool
    {
        if ($user->hasRole('admin')) {
            return true;
        }
        return $user->company_id === $phoneNumber->company_id;
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'manager']);
    }

    public function update(User $user, PhoneNumber $phoneNumber): bool
    {
        if ($user->hasRole('admin')) {
            return true;
        }

        if ($user->hasRole('manager') && $user->company_id === $phoneNumber->company_id) {
            return true;
        }

        return false;
    }

    public function delete(User $user, PhoneNumber $phoneNumber): bool
    {
        // Cannot delete primary phone number
        if ($phoneNumber->is_primary) {
            return false;
        }

        if ($user->hasRole('admin')) {
            return true;
        }

        if ($user->hasRole('manager') && $user->company_id === $phoneNumber->company_id) {
            return true;
        }

        return false;
    }

    public function restore(User $user, PhoneNumber $phoneNumber): bool
    {
        return $this->update($user, $phoneNumber);
    }

    public function forceDelete(User $user, PhoneNumber $phoneNumber): bool
    {
        return $user->hasRole('super_admin');
    }
}