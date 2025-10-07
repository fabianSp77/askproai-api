<?php

namespace App\Policies;

use App\Models\Call;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class CallPolicy
{
    use HandlesAuthorization;

    /**
     * Perform pre-authorization checks.
     */
    public function before(User $user, string $ability): ?bool
    {
        // Check for multiple variations of super admin role
        if ($user->hasRole(['super_admin', 'Super Admin', 'super-admin'])) {
            return true;
        }

        // Also allow full access for admin role
        if ($user->hasRole(['admin', 'Admin'])) {
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
    public function view(User $user, Call $call): bool
    {
        // Admin can view all calls (including variations)
        if ($user->hasRole(['admin', 'Admin'])) {
            return true;
        }

        // Users can view calls from their company (direct match)
        if ($user->company_id && $user->company_id === $call->company_id) {
            return true;
        }

        // ✅ FIX VULN-002: Resellers can view their customers' calls (parent_company_id match)
        if ($user->hasRole(['reseller_admin', 'reseller_owner', 'reseller_support'])) {
            // Check if call belongs to a customer company where parent_company_id = reseller company_id
            if ($call->company && $call->company->parent_company_id === $user->company_id) {
                return true;
            }
        }

        // Staff can view calls they participated in
        if ($user->staff_id && $call->staff_id === $user->staff_id) {
            return true;
        }

        // Allow viewing calls without company_id for admin users
        if (!$call->company_id && $user->hasRole(['admin', 'Admin', 'manager'])) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // Calls are usually created automatically by the system
        return $user->hasRole('admin');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Call $call): bool
    {
        // Admin can update all calls
        if ($user->hasRole('admin')) {
            return true;
        }

        // Managers can update calls in their company
        if ($user->hasRole('manager') && $user->company_id === $call->company_id) {
            return true;
        }

        // ✅ FIX: Resellers can update their customers' calls
        if ($user->hasRole(['reseller_admin', 'reseller_owner', 'reseller_support'])) {
            if ($call->company && $call->company->parent_company_id === $user->company_id) {
                return true;
            }
        }

        // Staff can update their own calls (e.g., add notes)
        if ($user->staff_id && $call->staff_id === $user->staff_id) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Call $call): bool
    {
        // Only admins can delete call records
        return $user->hasRole('admin');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Call $call): bool
    {
        return $user->hasRole('admin');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Call $call): bool
    {
        return $user->hasRole('super_admin');
    }

    /**
     * Determine whether the user can play the recording.
     */
    public function playRecording(User $user, Call $call): bool
    {
        // Check if recording exists
        if (!$call->recording_url) {
            return false;
        }

        // Admin can play all recordings
        if ($user->hasRole('admin')) {
            return true;
        }

        // Managers can play recordings from their company
        if ($user->hasRole('manager') && $user->company_id === $call->company_id) {
            return true;
        }

        // ✅ FIX: Resellers can play recordings from their customers' calls
        if ($user->hasRole(['reseller_admin', 'reseller_owner', 'reseller_support'])) {
            if ($call->company && $call->company->parent_company_id === $user->company_id) {
                return true;
            }
        }

        // Staff can play recordings of calls they participated in
        if ($user->staff_id && $call->staff_id === $user->staff_id) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can view the transcript.
     */
    public function viewTranscript(User $user, Call $call): bool
    {
        return $this->view($user, $call);
    }

    /**
     * Determine whether the user can export call data.
     */
    public function export(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'manager']);
    }
}