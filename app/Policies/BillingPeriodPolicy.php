<?php

namespace App\Policies;

use App\Models\BillingPeriod;
use App\Models\User;

class BillingPeriodPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, BillingPeriod $billingPeriod): bool
    {
        // Super admins can view all
        if ($user->hasRole('Super Admin')) {
            return true;
        }
        
        // Check if user's company matches the billing period's company
        return $user->company_id === $billingPeriod->company_id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // Only super admins can manually create billing periods
        return $user->hasRole('Super Admin');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, BillingPeriod $billingPeriod): bool
    {
        // Super admins can update all
        if ($user->hasRole('Super Admin')) {
            return true;
        }
        
        return $user->company_id === $billingPeriod->company_id;
    }

    /**
     * Determine whether the user can edit the model.
     * (Alias for update - some frameworks use 'edit' instead of 'update')
     */
    public function edit(User $user, BillingPeriod $billingPeriod): bool
    {
        return $this->update($user, $billingPeriod);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, BillingPeriod $billingPeriod): bool
    {
        // Only allow deletion if not invoiced
        return ($user->company_id === $billingPeriod->company_id || $user->hasRole('Super Admin')) 
            && !$billingPeriod->is_invoiced;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, BillingPeriod $billingPeriod): bool
    {
        return $user->hasRole('Super Admin');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, BillingPeriod $billingPeriod): bool
    {
        return $user->hasRole('Super Admin');
    }
}