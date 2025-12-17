<?php

namespace App\Services\CustomerPortal;

use App\Exceptions\UserManagementException;
use App\Models\Company;
use App\Models\Role;
use App\Models\User;
use App\Models\UserInvitation;
use App\Notifications\UserInvitationNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

/**
 * User Management Service
 *
 * SECURITY PRINCIPLES:
 * - Privilege escalation prevention (cannot invite higher role)
 * - Multi-tenant isolation (company-scoped)
 * - Branch isolation (company_manager → own branch only)
 * - Token-based invitation (cryptographically secure)
 * - Audit trail for all actions
 *
 * ROLE HIERARCHY:
 * 1. super_admin (platform-wide)
 * 2. owner (company-wide)
 * 3. admin (company-wide)
 * 4. company_manager (branch-scoped)
 * 5. company_staff (self-scoped)
 *
 * INVITATION FLOW:
 * 1. User creates invitation → Token generated
 * 2. Email sent → Link with token
 * 3. Recipient clicks → Registration form
 * 4. Registration → User created + invitation marked accepted
 * 5. Welcome email → Portal access granted
 */
class UserManagementService
{
    /**
     * Invite user to company
     *
     * @throws UserManagementException
     */
    public function inviteUser(
        Company $company,
        string $email,
        Role $role,
        User $invitedBy,
        ?array $metadata = null
    ): UserInvitation {
        // ==========================================
        // STEP 1: AUTHORIZATION
        // ==========================================
        $this->authorizeInvitation($company, $role, $invitedBy);

        // ==========================================
        // STEP 2: VALIDATION
        // ==========================================
        $this->validateInvitation($company, $email, $role);

        try {
            return DB::transaction(function () use ($company, $email, $role, $invitedBy, $metadata) {
                // ==========================================
                // STEP 3: CREATE INVITATION
                // ==========================================
                $invitation = UserInvitation::create([
                    'company_id' => $company->id,
                    'email' => $email,
                    'role_id' => $role->id,
                    'invited_by' => $invitedBy->id,
                    'token' => UserInvitation::generateToken(),
                    'expires_at' => now()->addHours(config('portal.invitation_expiry_hours', 72)),
                    'metadata' => $metadata,
                ]);

                // ==========================================
                // STEP 4: QUEUE EMAIL (NON-BLOCKING)
                // ==========================================
                $invitation->inviter->notify(new UserInvitationNotification($invitation));

                // ==========================================
                // STEP 5: AUDIT LOG
                // ==========================================
                activity()
                    ->performedOn($invitation)
                    ->causedBy($invitedBy)
                    ->withProperties([
                        'email' => $email,
                        'role' => $role->name,
                        'company' => $company->name,
                    ])
                    ->log('user_invited');

                return $invitation;
            });

        } catch (\Exception $e) {
            Log::error('User invitation failed', [
                'company_id' => $company->id,
                'email' => $email,
                'role' => $role->name,
                'invited_by' => $invitedBy->id,
                'error' => $e->getMessage(),
            ]);

            throw new UserManagementException(
                'Failed to create user invitation. Please try again.',
                500,
                $e
            );
        }
    }

    /**
     * Accept invitation and create user
     *
     * @throws UserManagementException
     */
    public function acceptInvitation(
        string $token,
        array $userData
    ): User {
        // ==========================================
        // STEP 1: FIND INVITATION
        // ==========================================
        $invitation = UserInvitation::where('token', $token)->firstOrFail();

        // ==========================================
        // STEP 2: VALIDATE INVITATION
        // ==========================================
        if (!$invitation->isValid()) {
            throw new UserManagementException(
                $invitation->isExpired()
                    ? 'This invitation has expired. Please request a new one.'
                    : 'This invitation has already been used.',
                422
            );
        }

        // Verify email matches
        if ($invitation->email !== $userData['email']) {
            throw new UserManagementException(
                'Email does not match invitation.',
                422
            );
        }

        try {
            return DB::transaction(function () use ($invitation, $userData) {
                // ==========================================
                // STEP 3: CREATE USER
                // ==========================================
                $user = User::create([
                    'name' => $userData['name'],
                    'email' => $userData['email'],
                    'password' => Hash::make($userData['password']),
                    'company_id' => $invitation->company_id,
                    'phone' => $userData['phone'] ?? null,
                    'email_verified_at' => now(), // Auto-verify via invitation
                ]);

                // ==========================================
                // STEP 4: ASSIGN ROLE
                // ==========================================
                $user->roles()->attach($invitation->role_id);

                // ==========================================
                // STEP 5: ASSIGN STAFF (IF PROVIDED)
                // ==========================================
                if (isset($invitation->metadata['staff_id'])) {
                    $user->staff_id = $invitation->metadata['staff_id'];
                    $user->save();
                }

                // ==========================================
                // STEP 6: MARK INVITATION ACCEPTED
                // ==========================================
                $invitation->markAsAccepted();

                // ==========================================
                // STEP 7: SEND WELCOME EMAIL
                // ==========================================
                // TODO: Implement welcome email

                // ==========================================
                // STEP 8: AUDIT LOG
                // ==========================================
                activity()
                    ->performedOn($user)
                    ->causedBy($user)
                    ->withProperties([
                        'invitation_id' => $invitation->id,
                        'role' => $invitation->role->name,
                    ])
                    ->log('invitation_accepted');

                return $user;
            });

        } catch (\Exception $e) {
            Log::error('Invitation acceptance failed', [
                'token' => substr($token, 0, 8) . '...',
                'email' => $userData['email'],
                'error' => $e->getMessage(),
            ]);

            throw new UserManagementException(
                'Failed to create user account. Please try again.',
                500,
                $e
            );
        }
    }

    /**
     * Update user details
     *
     * @throws UserManagementException
     */
    public function updateUser(
        User $user,
        array $data,
        User $updatedBy
    ): User {
        // Authorization
        if (Gate::forUser($updatedBy)->denies('update', $user)) {
            throw new UserManagementException(
                'You are not authorized to update this user.',
                403
            );
        }

        // Validation
        $this->validateUserUpdate($user, $data, $updatedBy);

        try {
            return DB::transaction(function () use ($user, $data, $updatedBy) {
                $oldValues = $user->only(['name', 'email', 'phone']);

                $user->update([
                    'name' => $data['name'] ?? $user->name,
                    'email' => $data['email'] ?? $user->email,
                    'phone' => $data['phone'] ?? $user->phone,
                ]);

                // Audit log
                activity()
                    ->performedOn($user)
                    ->causedBy($updatedBy)
                    ->withProperties([
                        'old' => $oldValues,
                        'new' => $user->only(['name', 'email', 'phone']),
                    ])
                    ->log('user_updated');

                return $user;
            });

        } catch (\Exception $e) {
            Log::error('User update failed', [
                'user_id' => $user->id,
                'updated_by' => $updatedBy->id,
                'error' => $e->getMessage(),
            ]);

            throw new UserManagementException(
                'Failed to update user. Please try again.',
                500,
                $e
            );
        }
    }

    /**
     * Deactivate user (soft delete)
     *
     * @throws UserManagementException
     */
    public function deactivateUser(User $user, User $deactivatedBy): void
    {
        // Authorization
        if (Gate::forUser($deactivatedBy)->denies('delete', $user)) {
            throw new UserManagementException(
                'You are not authorized to deactivate this user.',
                403
            );
        }

        // Cannot deactivate self
        if ($user->id === $deactivatedBy->id) {
            throw new UserManagementException(
                'You cannot deactivate your own account.',
                422
            );
        }

        try {
            DB::transaction(function () use ($user, $deactivatedBy) {
                $user->delete();

                // Audit log
                activity()
                    ->performedOn($user)
                    ->causedBy($deactivatedBy)
                    ->log('user_deactivated');
            });

        } catch (\Exception $e) {
            Log::error('User deactivation failed', [
                'user_id' => $user->id,
                'deactivated_by' => $deactivatedBy->id,
                'error' => $e->getMessage(),
            ]);

            throw new UserManagementException(
                'Failed to deactivate user. Please try again.',
                500,
                $e
            );
        }
    }

    // ==========================================
    // AUTHORIZATION
    // ==========================================

    private function authorizeInvitation(Company $company, Role $role, User $invitedBy): void
    {
        // Rule 1: User must belong to company
        if ($invitedBy->company_id !== $company->id) {
            throw new UserManagementException(
                'You can only invite users to your own company.',
                403
            );
        }

        // Rule 2: Cannot invite to higher privilege role
        $inviterHighestRole = $invitedBy->roles()->orderBy('level', 'desc')->first();
        if ($role->level > $inviterHighestRole->level) {
            throw new UserManagementException(
                'You cannot invite users with higher privileges than yourself.',
                403
            );
        }

        // Rule 3: Permission check
        if (Gate::forUser($invitedBy)->denies('invite-users', $company)) {
            throw new UserManagementException(
                'You do not have permission to invite users.',
                403
            );
        }
    }

    // ==========================================
    // VALIDATION
    // ==========================================

    private function validateInvitation(Company $company, string $email, Role $role): void
    {
        // Rule 1: Email already registered in company
        if (UserInvitation::emailExistsInCompany($email, $company->id)) {
            throw new UserManagementException(
                'A user with this email already exists in your company.',
                422
            );
        }

        // Rule 2: Pending invitation exists
        if (UserInvitation::hasPendingInvitation($email, $company->id)) {
            throw new UserManagementException(
                'An invitation has already been sent to this email address.',
                422
            );
        }

        // Rule 3: Valid role
        if (!in_array($role->name, ['owner', 'admin', 'company_manager', 'company_staff'])) {
            throw new UserManagementException(
                'Invalid role specified.',
                422
            );
        }
    }

    private function validateUserUpdate(User $user, array $data, User $updatedBy): void
    {
        // Rule 1: Email uniqueness (if changing)
        if (isset($data['email']) && $data['email'] !== $user->email) {
            $exists = User::where('email', $data['email'])
                ->where('company_id', $user->company_id)
                ->where('id', '!=', $user->id)
                ->exists();

            if ($exists) {
                throw new UserManagementException(
                    'This email is already in use.',
                    422
                );
            }
        }
    }
}
