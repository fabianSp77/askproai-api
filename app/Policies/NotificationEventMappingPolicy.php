<?php

namespace App\Policies;

use App\Models\NotificationEventMapping;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class NotificationEventMappingPolicy
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
    public function view(User $user, NotificationEventMapping $notificationEventMapping): bool
    {
        return $user->company_id === $notificationEventMapping->company_id;
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
    public function update(User $user, NotificationEventMapping $notificationEventMapping): bool
    {
        return $user->hasRole('admin') && $user->company_id === $notificationEventMapping->company_id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, NotificationEventMapping $notificationEventMapping): bool
    {
        return $user->hasRole('admin') && $user->company_id === $notificationEventMapping->company_id;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, NotificationEventMapping $notificationEventMapping): bool
    {
        return $user->hasRole('admin') && $user->company_id === $notificationEventMapping->company_id;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, NotificationEventMapping $notificationEventMapping): bool
    {
        return $user->hasRole('super_admin');
    }
}
