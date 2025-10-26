<?php

namespace App\Policies;

use App\Models\User;
use App\Models\RetellCallSession;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * RetellCallSession Authorization Policy
 *
 * Security: Multi-tenant isolation for call session access
 *
 * Access Levels:
 * - Company Scope: Users can only see sessions from their company
 * - Branch Scope: Managers can only see sessions from their branches
 * - Staff Scope: Staff can only see their own sessions
 *
 * Customer Portal: READ-ONLY access (no create/update/delete)
 *
 * @see App\Models\RetellCallSession
 * @see config/companyscope.php
 */
class RetellCallSessionPolicy
{
    use HandlesAuthorization;

    /**
     * Determine if user can view any call sessions.
     *
     * Multi-tenancy: Users must belong to a company
     *
     * @param User $user
     * @return bool
     */
    public function viewAny(User $user): bool
    {
        // Must belong to a company (multi-tenancy)
        if ($user->company_id === null) {
            return false;
        }

        // All company users can view sessions (filtered by scope)
        return $user->hasAnyRole([
            'company_owner',
            'company_admin',
            'company_manager',
            'company_staff',
        ]);
    }

    /**
     * Determine if user can view a specific call session.
     *
     * Security:
     * - Company isolation: Must be same company
     * - Branch isolation: Managers only see their branches
     * - Staff isolation: Staff only see their own calls
     *
     * @param User $user
     * @param RetellCallSession $session
     * @return bool
     */
    public function view(User $user, RetellCallSession $session): bool
    {
        // Company-level isolation (CRITICAL)
        if ($user->company_id !== $session->company_id) {
            return false;
        }

        // Branch-level isolation for managers
        if ($user->hasRole('company_manager')) {
            // TODO: Implement branch_id check when users.branch_id column exists
            // For now, managers can see all company sessions
            // Future: return $user->branch_id === $session->branch_id;
            return true;
        }

        // Staff-level isolation
        if ($user->hasRole('company_staff')) {
            // Staff can only see sessions they handled
            // TODO: Add staff_id to retell_call_sessions table
            // For now, allow all sessions (will be restricted in Phase 2)
            return true;
        }

        // Owners and admins can see all company sessions
        return $user->hasAnyRole(['company_owner', 'company_admin']);
    }

    /**
     * Determine if user can create call sessions.
     *
     * Customer Portal: NO CREATE (read-only)
     * Only Retell AI webhook can create sessions via API
     *
     * @param User $user
     * @return bool
     */
    public function create(User $user): bool
    {
        // Customer portal users cannot create sessions
        // Sessions are created automatically by Retell webhook
        return false;
    }

    /**
     * Determine if user can update call sessions.
     *
     * Customer Portal: NO UPDATE (read-only)
     * Admin panel: Only admins can update for troubleshooting
     *
     * @param User $user
     * @param RetellCallSession $session
     * @return bool
     */
    public function update(User $user, RetellCallSession $session): bool
    {
        // Customer portal users cannot update sessions
        return false;
    }

    /**
     * Determine if user can delete call sessions.
     *
     * Customer Portal: NO DELETE (read-only)
     * Data retention: Call sessions should never be deleted
     *
     * @param User $user
     * @param RetellCallSession $session
     * @return bool
     */
    public function delete(User $user, RetellCallSession $session): bool
    {
        // Customer portal users cannot delete sessions
        // Data retention for compliance
        return false;
    }

    /**
     * Determine if user can restore deleted call sessions.
     *
     * @param User $user
     * @param RetellCallSession $session
     * @return bool
     */
    public function restore(User $user, RetellCallSession $session): bool
    {
        // Not applicable (sessions should never be soft-deleted)
        return false;
    }

    /**
     * Determine if user can permanently delete call sessions.
     *
     * @param User $user
     * @param RetellCallSession $session
     * @return bool
     */
    public function forceDelete(User $user, RetellCallSession $session): bool
    {
        // Data retention: Never allow permanent deletion
        return false;
    }
}
