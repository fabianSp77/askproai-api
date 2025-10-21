<?php

namespace App\Policies;

use App\Models\User;
use App\Models\SystemTestRun;

/**
 * System Testing Policy
 *
 * Authorization for System Testing Dashboard
 * ONLY admin@askproai.de can access
 */
class SystemTestingPolicy
{
    /**
     * The admin email that has exclusive access
     */
    private const ADMIN_EMAIL = 'admin@askproai.de';

    /**
     * Check if user can view any test runs
     */
    public function viewAny(?User $user): bool
    {
        return $user && $this->isAdminUser($user);
    }

    /**
     * Check if user can view a specific test run
     */
    public function view(User $user, SystemTestRun $systemTestRun): bool
    {
        return $this->isAdminUser($user);
    }

    /**
     * Check if user can create a test run
     */
    public function create(User $user): bool
    {
        return $this->isAdminUser($user);
    }

    /**
     * Check if user can update a test run
     */
    public function update(User $user, SystemTestRun $systemTestRun): bool
    {
        return $this->isAdminUser($user);
    }

    /**
     * Check if user can delete a test run
     */
    public function delete(User $user, SystemTestRun $systemTestRun): bool
    {
        return $this->isAdminUser($user);
    }

    /**
     * Check if user can access system testing dashboard
     */
    public function accessTestingDashboard(User $user): bool
    {
        return $this->isAdminUser($user);
    }

    /**
     * Verify user is the admin user
     */
    private function isAdminUser(?User $user): bool
    {
        if (!$user) {
            return false;
        }

        return $user->email === self::ADMIN_EMAIL;
    }
}
