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
     * 1. Admin: See all calls
     * 2. Company isolation: Must belong to same company
     * 3. Branch isolation: company_manager sees only their branch
     * 4. Staff isolation: company_staff sees only their calls
     * 5. Reseller access: See customer companies' calls
     */
    public function view(User $user, Call $call): bool
    {
        // Level 1: Admin can view all calls (including variations)
        if ($user->hasRole(['admin', 'Admin'])) {
            return true;
        }

        // Level 2: Company isolation (CRITICAL for multi-tenancy)
        if ($user->company_id && $user->company_id === $call->company_id) {

            // Level 3: Branch isolation for company_manager
            if ($user->hasRole('company_manager') && $user->branch_id) {
                return $user->branch_id === $call->branch_id;
            }

            // Level 4: Staff isolation for company_staff
            // FIX: Now uses $user->staff_id (which exists after migration)
            if ($user->hasAnyRole(['staff', 'company_staff']) && $user->staff_id) {
                return $user->staff_id === $call->staff_id;
            }

            // Level 5: Company owners/admins see all company calls
            if ($user->hasAnyRole(['manager', 'company_owner', 'company_admin'])) {
                return true;
            }

            return true; // Default: company users can see company calls
        }

        // Reseller access: Can view their customers' calls (parent_company_id match)
        if ($user->hasRole(['reseller_admin', 'reseller_owner', 'reseller_support'])) {
            if ($call->company && $call->company->parent_company_id === $user->company_id) {
                return true;
            }
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
        // FIX: Now uses $user->staff_id (which exists after migration)
        if ($user->hasAnyRole(['staff', 'company_staff']) && $user->staff_id) {
            return $user->staff_id === $call->staff_id;
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
        // FIX: Now uses $user->staff_id (which exists after migration)
        if ($user->hasAnyRole(['staff', 'company_staff']) && $user->staff_id) {
            return $user->staff_id === $call->staff_id;
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
     *
     * Dual-Role Support: Admin Panel + Customer Portal
     */
    public function export(User $user): bool
    {
        return $user->hasAnyRole([
            // Admin Panel roles
            'admin',
            'manager',
            // Customer Portal roles
            'company_owner',
            'company_admin',
            'company_manager',
        ]);
    }
}