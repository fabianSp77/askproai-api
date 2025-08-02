<?php

use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The given channel authorization callbacks are
| used to check if an authenticated user can listen to the channel.
|
*/

// Company channel - users can only listen to their own company's events
Broadcast::channel('company.{companyId}', function ($user, $companyId) {
    return (int) $user->company_id === (int) $companyId;
});

// User-specific channel
Broadcast::channel('user.{userId}', function ($user, $userId) {
    return (int) $user->id === (int) $userId;
});

// Branch-specific channel
Broadcast::channel('branch.{branchId}', function ($user, $branchId) {
    // Check if user has access to this branch
    return $user->company->branches()->where('id', $branchId)->exists();
});

// Call-specific channel for monitoring individual calls
Broadcast::channel('call.{callId}', function ($user, $callId) {
    // Check if the call belongs to the user's company
    $call = \App\Models\Call::find($callId);
    return $call && $call->company_id === $user->company_id;
});

// Appointment channel
Broadcast::channel('appointment.{appointmentId}', function ($user, $appointmentId) {
    // Check if the appointment belongs to the user's company
    $appointment = \App\Models\Appointment::find($appointmentId);
    return $appointment && $appointment->company_id === $user->company_id;
});

// Command execution channel
Broadcast::channel('execution.{executionId}', function ($user, $executionId) {
    // Check if the execution belongs to the user
    $execution = \App\Models\CommandExecution::find($executionId);
    return $execution && $execution->user_id === $user->id;
});

// Workflow execution channel
Broadcast::channel('workflow-execution.{executionId}', function ($user, $executionId) {
    // Check if the workflow execution belongs to the user
    $execution = \App\Models\WorkflowExecution::find($executionId);
    return $execution && $execution->user_id === $user->id;
});

// Dashboard channels
Broadcast::channel('company.{companyId}.dashboard', function ($user, $companyId) {
    return (int) $user->company_id === (int) $companyId;
});

Broadcast::channel('branch.{branchId}.dashboard', function ($user, $branchId) {
    return $user->company->branches()->where('id', $branchId)->exists();
});

// Notification channels
Broadcast::channel('user.{userId}.notifications', function ($user, $userId) {
    return (int) $user->id === (int) $userId;
});

Broadcast::channel('company.{companyId}.notifications', function ($user, $companyId) {
    return (int) $user->company_id === (int) $companyId;
});

// Staff channels
Broadcast::channel('staff.{staffId}', function ($user, $staffId) {
    // Check if user is the staff member or has access to manage them
    if ($user instanceof \App\Models\StaffMember && $user->id === (int) $staffId) {
        return true;
    }
    
    $staff = \App\Models\StaffMember::find($staffId);
    return $staff && $staff->company_id === $user->company_id;
});

// Presence channels for showing online users
Broadcast::channel('presence.company.{companyId}', function ($user, $companyId) {
    if ((int) $user->company_id === (int) $companyId) {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'avatar_url' => $user->avatar_url
        ];
    }
    return false;
});

// Calls and appointments channels for real-time lists
Broadcast::channel('calls', function ($user) {
    // All authenticated users can listen to calls channel
    // Filtering happens on the frontend based on company_id
    return true;
});

Broadcast::channel('appointments', function ($user) {
    // All authenticated users can listen to appointments channel
    // Filtering happens on the frontend based on company_id
    return true;
});