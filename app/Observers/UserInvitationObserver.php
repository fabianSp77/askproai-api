<?php

namespace App\Observers;

use App\Models\UserInvitation;
use Illuminate\Support\Facades\Log;

/**
 * UserInvitationObserver
 *
 * PURPOSE: Enforces business rules for user invitations
 *
 * CONSTRAINTS ENFORCED:
 * - Uniqueness: One pending invitation per email+company (MySQL partial index workaround)
 * - Security: Prevents duplicate active invitations
 * - Data Integrity: Validates state transitions
 */
class UserInvitationObserver
{
    /**
     * Handle the UserInvitation "creating" event.
     *
     * CONSTRAINT: Prevent duplicate pending invitations
     * MySQL doesn't support partial unique indexes (WHERE accepted_at IS NULL)
     * so we enforce this at the application level
     *
     * NOTE: This check provides protection against sequential duplicate creations.
     * True race conditions (concurrent requests) should be handled by wrapping
     * the invitation creation in a database transaction with appropriate locking.
     */
    public function creating(UserInvitation $invitation): void
    {
        Log::info('[UserInvitation Observer] Creating invitation', [
            'email' => $invitation->email,
            'company_id' => $invitation->company_id,
            'invited_by' => $invitation->invited_by,
        ]);

        // Check for existing pending invitation with same email+company
        $existingPending = UserInvitation::where('email', $invitation->email)
            ->where('company_id', $invitation->company_id)
            ->whereNull('accepted_at')
            ->lockForUpdate() // Add row-level lock for better race condition protection
            ->exists();

        if ($existingPending) {
            Log::warning('[UserInvitation Observer] Blocked duplicate pending invitation', [
                'email' => $invitation->email,
                'company_id' => $invitation->company_id,
            ]);

            throw new \Exception(
                "A pending invitation already exists for {$invitation->email} in this company. " .
                "Please cancel or wait for the existing invitation to expire before creating a new one."
            );
        }
    }

    /**
     * Handle the UserInvitation "created" event.
     */
    public function created(UserInvitation $invitation): void
    {
        activity()
            ->performedOn($invitation)
            ->causedBy($invitation->invited_by)
            ->withProperties([
                'email' => $invitation->email,
                'role_id' => $invitation->role_id,
                'expires_at' => $invitation->expires_at->toIso8601String(),
            ])
            ->log('invitation_created');

        Log::info('[UserInvitation Observer] Invitation created', [
            'invitation_id' => $invitation->id,
            'email' => $invitation->email,
            'company_id' => $invitation->company_id,
        ]);
    }

    /**
     * Handle the UserInvitation "updating" event.
     */
    public function updating(UserInvitation $invitation): void
    {
        // If invitation is being accepted, validate it's still valid
        if ($invitation->isDirty('accepted_at') && $invitation->accepted_at) {
            if ($invitation->isExpired()) {
                throw new \Exception(
                    'Cannot accept expired invitation. Please request a new invitation.'
                );
            }

            Log::info('[UserInvitation Observer] Invitation accepted', [
                'invitation_id' => $invitation->id,
                'email' => $invitation->email,
                'company_id' => $invitation->company_id,
            ]);
        }
    }

    /**
     * Handle the UserInvitation "updated" event.
     */
    public function updated(UserInvitation $invitation): void
    {
        // Log acceptance
        if ($invitation->wasChanged('accepted_at') && $invitation->accepted_at) {
            activity()
                ->performedOn($invitation)
                ->withProperties([
                    'email' => $invitation->email,
                    'accepted_at' => $invitation->accepted_at->toIso8601String(),
                ])
                ->log('invitation_accepted');
        }
    }

    /**
     * Handle the UserInvitation "deleted" event.
     */
    public function deleted(UserInvitation $invitation): void
    {
        activity()
            ->performedOn($invitation)
            ->withProperties([
                'email' => $invitation->email,
                'was_accepted' => !is_null($invitation->accepted_at),
            ])
            ->log('invitation_deleted');

        Log::info('[UserInvitation Observer] Invitation deleted', [
            'invitation_id' => $invitation->id,
            'email' => $invitation->email,
            'company_id' => $invitation->company_id,
        ]);
    }
}
