<?php

namespace App\Observers;

use App\Models\User;
use Illuminate\Support\Facades\Log;

/**
 * UserObserver
 *
 * PURPOSE: Enforces business rules for users
 *
 * CONSTRAINTS ENFORCED:
 * - Uniqueness: staff_id must be unique when not null (MySQL partial index workaround)
 * - Data Integrity: Validates staff assignments
 */
class UserObserver
{
    /**
     * Handle the User "creating" event.
     *
     * CONSTRAINT: Prevent duplicate staff_id assignments
     * MySQL doesn't support partial unique indexes (WHERE staff_id IS NOT NULL)
     * so we enforce this at the application level
     */
    public function creating(User $user): void
    {
        // Only validate if staff_id is being set
        if ($user->staff_id) {
            $existingUser = User::where('staff_id', $user->staff_id)
                ->where('id', '!=', $user->id)
                ->first();

            if ($existingUser) {
                throw new \Exception(
                    "Staff ID {$user->staff_id} is already assigned to user {$existingUser->name} (ID: {$existingUser->id}). " .
                    "Each staff member must have a unique staff ID."
                );
            }

            Log::info('[User Observer] Creating user with staff_id', [
                'staff_id' => $user->staff_id,
                'email' => $user->email,
            ]);
        }
    }

    /**
     * Handle the User "updating" event.
     *
     * CONSTRAINT: Prevent duplicate staff_id assignments on update
     */
    public function updating(User $user): void
    {
        // Only validate if staff_id is being changed
        if ($user->isDirty('staff_id') && $user->staff_id) {
            $existingUser = User::where('staff_id', $user->staff_id)
                ->where('id', '!=', $user->id)
                ->first();

            if ($existingUser) {
                throw new \Exception(
                    "Staff ID {$user->staff_id} is already assigned to user {$existingUser->name} (ID: {$existingUser->id}). " .
                    "Each staff member must have a unique staff ID."
                );
            }

            Log::info('[User Observer] Updating user staff_id', [
                'user_id' => $user->id,
                'old_staff_id' => $user->getOriginal('staff_id'),
                'new_staff_id' => $user->staff_id,
            ]);
        }
    }

    /**
     * Handle the User "created" event.
     */
    public function created(User $user): void
    {
        if ($user->staff_id) {
            activity()
                ->performedOn($user)
                ->withProperties([
                    'staff_id' => $user->staff_id,
                    'email' => $user->email,
                ])
                ->log('user_staff_assigned');
        }
    }

    /**
     * Handle the User "updated" event.
     */
    public function updated(User $user): void
    {
        if ($user->wasChanged('staff_id')) {
            activity()
                ->performedOn($user)
                ->withProperties([
                    'old_staff_id' => $user->getOriginal('staff_id'),
                    'new_staff_id' => $user->staff_id,
                ])
                ->log('user_staff_updated');
        }
    }
}
