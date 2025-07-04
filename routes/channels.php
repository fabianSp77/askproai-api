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