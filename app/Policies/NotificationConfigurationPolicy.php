<?php

namespace App\Policies;

use App\Models\NotificationConfiguration;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class NotificationConfigurationPolicy
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
    public function view(User $user, NotificationConfiguration $notificationConfiguration): bool
    {
        $configCompanyId = $this->getCompanyId($notificationConfiguration);

        return $user->company_id === $configCompanyId;
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
    public function update(User $user, NotificationConfiguration $notificationConfiguration): bool
    {
        $configCompanyId = $this->getCompanyId($notificationConfiguration);

        return $user->hasRole('admin') && $user->company_id === $configCompanyId;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, NotificationConfiguration $notificationConfiguration): bool
    {
        $configCompanyId = $this->getCompanyId($notificationConfiguration);

        return $user->hasRole('admin') && $user->company_id === $configCompanyId;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, NotificationConfiguration $notificationConfiguration): bool
    {
        $configCompanyId = $this->getCompanyId($notificationConfiguration);

        return $user->hasRole('admin') && $user->company_id === $configCompanyId;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, NotificationConfiguration $notificationConfiguration): bool
    {
        return $user->hasRole('super_admin');
    }

    /**
     * Get company_id from polymorphic configurable relationship.
     */
    protected function getCompanyId(NotificationConfiguration $notificationConfiguration): ?int
    {
        $configurable = $notificationConfiguration->configurable;

        // If configurable is a Company
        if ($configurable instanceof \App\Models\Company) {
            return $configurable->id;
        }

        // If configurable has company_id (Branch, Service, Staff)
        if (isset($configurable->company_id)) {
            return $configurable->company_id;
        }

        return null;
    }
}
