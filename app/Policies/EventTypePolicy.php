<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use App\Models\CalcomEventType;
use Illuminate\Auth\Access\HandlesAuthorization;

class EventTypePolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any event types.
     */
    public function viewAny(User $user): bool
    {
        // Allow all authenticated users to view event types for now
        return true;
    }

    /**
     * Determine whether the user can view the event type.
     */
    public function view(User $user, CalcomEventType $eventType): bool
    {
        // Check if user belongs to the same company
        return $user->company_id === $eventType->company_id;
    }

    /**
     * Determine whether the user can create event types.
     */
    public function create(User $user): bool
    {
        // Allow authenticated users with company_id
        return $user->company_id !== null;
    }

    /**
     * Determine whether the user can update the event type.
     */
    public function update(User $user, CalcomEventType $eventType): bool
    {
        // Must belong to same company
        return $user->company_id === $eventType->company_id;
    }

    /**
     * Determine whether the user can delete the event type.
     */
    public function delete(User $user, CalcomEventType $eventType): bool
    {
        // Must belong to same company
        return $user->company_id === $eventType->company_id;
    }

    /**
     * Determine whether the user can assign staff to the event type.
     */
    public function assignStaff(User $user, CalcomEventType $eventType): bool
    {
        // Must belong to same company
        return $user->company_id === $eventType->company_id;
    }

    /**
     * Determine whether the user can sync the event type.
     */
    public function sync(User $user, CalcomEventType $eventType): bool
    {
        // Must belong to same company
        return $user->company_id === $eventType->company_id;
    }
}