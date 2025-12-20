<?php

namespace App\Policies;

use App\Models\ServiceCase;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ServiceCasePolicy
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
     *
     * Service Desk Support:
     * - Admin Panel: admin, manager, staff, receptionist
     * - Customer Portal: company_owner, company_admin, company_manager
     */
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole([
            // Admin Panel roles
            'admin',
            'manager',
            'staff',
            'receptionist',
            // Customer Portal roles (company users can view their tickets)
            'company_owner',
            'company_admin',
            'company_manager',
        ]);
    }

    /**
     * Determine whether the user can view the model.
     *
     * Multi-Level Access Control:
     * 1. Admin: See all service cases (no restrictions)
     * 2. Company isolation: Must belong to same company
     * 3. Staff isolation: Assigned staff can view their cases
     */
    public function view(User $user, ServiceCase $serviceCase): bool
    {
        // Level 1: Admin can view all service cases
        if ($user->hasRole('admin')) {
            return true;
        }

        // Level 2: Company isolation (CRITICAL for multi-tenancy)
        if ($user->company_id !== $serviceCase->company_id) {
            return false;
        }

        // Level 3: Staff isolation - assigned staff can view their cases
        if ($user->hasRole('staff') && $user->staff_id) {
            // Staff can only see cases assigned to them
            return $user->staff_id === $serviceCase->assigned_to;
        }

        // Level 4: Company owners/admins/managers see all company cases
        if ($user->hasAnyRole(['manager', 'company_owner', 'company_admin', 'company_manager', 'receptionist'])) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can create models.
     *
     * Service cases are typically created via API (Retell AI calls).
     * Manual creation allowed for admin/manager for testing or manual intake.
     */
    public function create(User $user): bool
    {
        return $user->hasAnyRole([
            'admin',
            'manager',
            'receptionist',
            // Customer Portal roles for manual ticket creation
            'company_owner',
            'company_admin',
            'company_manager',
        ]);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, ServiceCase $serviceCase): bool
    {
        // Admin can update all service cases
        if ($user->hasRole('admin')) {
            return true;
        }

        // Company isolation check
        if ($user->company_id !== $serviceCase->company_id) {
            return false;
        }

        // Managers and receptionists can update company cases
        if ($user->hasAnyRole(['manager', 'receptionist', 'company_owner', 'company_admin', 'company_manager'])) {
            return true;
        }

        // Assigned staff can update their own cases
        if ($user->hasRole('staff') && $user->staff_id === $serviceCase->assigned_to) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, ServiceCase $serviceCase): bool
    {
        // Only closed cases can be deleted (soft delete)
        if ($serviceCase->isOpen()) {
            // Only admins can delete open cases
            return $user->hasRole('admin');
        }

        // Managers can delete closed cases in their company
        if ($user->hasRole('manager') && $user->company_id === $serviceCase->company_id) {
            return true;
        }

        // Admin can delete any case
        return $user->hasRole('admin');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, ServiceCase $serviceCase): bool
    {
        if ($user->hasRole('admin')) {
            return true;
        }

        if ($user->hasRole('manager') && $user->company_id === $serviceCase->company_id) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, ServiceCase $serviceCase): bool
    {
        // Only super_admin can force delete (handled in before())
        return false;
    }

    /**
     * Determine whether the user can assign the case to a staff member.
     */
    public function assign(User $user, ServiceCase $serviceCase): bool
    {
        // Admin can assign any case
        if ($user->hasRole('admin')) {
            return true;
        }

        // Company isolation check
        if ($user->company_id !== $serviceCase->company_id) {
            return false;
        }

        // Managers can assign cases in their company
        if ($user->hasAnyRole(['manager', 'company_owner', 'company_admin', 'company_manager'])) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can resolve the case.
     */
    public function resolve(User $user, ServiceCase $serviceCase): bool
    {
        // Can't resolve already closed cases
        if ($serviceCase->isClosed()) {
            return false;
        }

        // Admin can resolve any case
        if ($user->hasRole('admin')) {
            return true;
        }

        // Company isolation check
        if ($user->company_id !== $serviceCase->company_id) {
            return false;
        }

        // Managers can resolve cases
        if ($user->hasAnyRole(['manager', 'company_owner', 'company_admin', 'company_manager'])) {
            return true;
        }

        // Assigned staff can resolve their own cases
        if ($user->staff_id === $serviceCase->assigned_to) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can close the case.
     */
    public function close(User $user, ServiceCase $serviceCase): bool
    {
        // Can't close already closed cases
        if ($serviceCase->status === ServiceCase::STATUS_CLOSED) {
            return false;
        }

        // Admin can close any case
        if ($user->hasRole('admin')) {
            return true;
        }

        // Company isolation check
        if ($user->company_id !== $serviceCase->company_id) {
            return false;
        }

        // Only managers and admins can officially close cases
        if ($user->hasAnyRole(['manager', 'company_owner', 'company_admin'])) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can reopen the case.
     */
    public function reopen(User $user, ServiceCase $serviceCase): bool
    {
        // Can only reopen closed cases
        if (!$serviceCase->isClosed()) {
            return false;
        }

        // Admin can reopen any case
        if ($user->hasRole('admin')) {
            return true;
        }

        // Company isolation check
        if ($user->company_id !== $serviceCase->company_id) {
            return false;
        }

        // Managers can reopen cases in their company
        if ($user->hasAnyRole(['manager', 'company_owner', 'company_admin', 'company_manager'])) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can retry output delivery.
     */
    public function retryOutput(User $user, ServiceCase $serviceCase): bool
    {
        // Only cases with failed output can be retried
        if ($serviceCase->output_status !== ServiceCase::OUTPUT_FAILED) {
            return false;
        }

        // Admin can retry any case
        if ($user->hasRole('admin')) {
            return true;
        }

        // Company isolation check
        if ($user->company_id !== $serviceCase->company_id) {
            return false;
        }

        // Managers can retry output delivery
        if ($user->hasAnyRole(['manager', 'company_owner', 'company_admin'])) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can escalate the case.
     */
    public function escalate(User $user, ServiceCase $serviceCase): bool
    {
        // Can't escalate closed cases
        if ($serviceCase->isClosed()) {
            return false;
        }

        // Admin can escalate any case
        if ($user->hasRole('admin')) {
            return true;
        }

        // Company isolation check
        if ($user->company_id !== $serviceCase->company_id) {
            return false;
        }

        // Managers and staff can escalate
        if ($user->hasAnyRole(['manager', 'staff', 'receptionist', 'company_owner', 'company_admin', 'company_manager'])) {
            return true;
        }

        return false;
    }
}
