<?php

namespace App\Policies;

use App\Models\ServiceCase;
use App\Models\ServiceCaseNote;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * ServiceCaseNotePolicy
 *
 * Authorization follows ServiceCase's company scope:
 * - User can only manage notes on cases they can access
 * - Multi-tenancy inherited via ServiceCase relationship
 */
class ServiceCaseNotePolicy
{
    use HandlesAuthorization;

    /**
     * Determine if user can view notes for a service case
     */
    public function viewAny(User $user, ServiceCase $serviceCase): bool
    {
        // Super Admins can view all notes
        if ($user->hasRole('super_admin')) {
            return true;
        }

        // User can view notes if they can view the service case
        return $user->company_id === $serviceCase->company_id;
    }

    /**
     * Determine if user can view a specific note
     */
    public function view(User $user, ServiceCaseNote $note): bool
    {
        // Super Admins can view all notes
        if ($user->hasRole('super_admin')) {
            return true;
        }

        return $user->company_id === $note->serviceCase->company_id;
    }

    /**
     * Determine if user can create notes on a service case
     */
    public function create(User $user, ServiceCase $serviceCase): bool
    {
        // Super Admins can create notes on any case
        if ($user->hasRole('super_admin')) {
            return true;
        }

        return $user->company_id === $serviceCase->company_id;
    }

    /**
     * Determine if user can update a note
     */
    public function update(User $user, ServiceCaseNote $note): bool
    {
        // Only note author can edit (within 30 minutes)
        if ($note->user_id !== $user->id) {
            return false;
        }

        // Can edit within 30 minutes of creation
        return $note->created_at->diffInMinutes(now()) <= 30;
    }

    /**
     * Determine if user can delete a note
     */
    public function delete(User $user, ServiceCaseNote $note): bool
    {
        // Only note author can delete (within 30 minutes)
        // Or admins can always delete
        if ($user->hasRole('admin') || $user->hasRole('super_admin')) {
            return $user->company_id === $note->serviceCase->company_id;
        }

        if ($note->user_id !== $user->id) {
            return false;
        }

        return $note->created_at->diffInMinutes(now()) <= 30;
    }

    /**
     * Determine if user can reply to a note
     */
    public function reply(User $user, ServiceCaseNote $note): bool
    {
        // Max 3 levels of nesting (applies to everyone)
        if ($note->depth >= 3) {
            return false;
        }

        // Super Admins can reply to any note
        if ($user->hasRole('super_admin')) {
            return true;
        }

        // Can reply if same company
        return $user->company_id === $note->serviceCase->company_id;
    }
}
