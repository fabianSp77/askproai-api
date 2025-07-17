<?php

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

// Test directly without tenant scope
$call = \App\Models\Call::withoutGlobalScope(\App\Scopes\TenantScope::class)
    ->with([
        'branch:id,name',
        'customer:id,name,email,phone',
        'callNotes.user:id,name',
        'callAssignments.assignedTo:id,name',
        'callAssignments.assignedBy:id,name',
    ])
    ->find(262);

if ($call) {
    // Simulate what the API controller returns
    $currentAssignment = $call->callAssignments()->latest()->first();
    
    $apiResponse = [
        'call' => [
            'id' => $call->id,
            'from_number' => $call->from_number,
            'to_number' => $call->to_number,
            'branch' => $call->branch,
            'status' => $call->status,
            'call_status' => null,
            'duration_sec' => $call->duration_sec,
            'duration_formatted' => $call->duration_sec ? gmdate('i:s', $call->duration_sec) : '-',
            'created_at' => $call->created_at->format('d.m.Y H:i:s'),
            'created_at_iso' => $call->created_at->toISOString(),
            'ended_at' => $call->ended_at ? $call->ended_at->format('d.m.Y H:i:s') : null,
            'summary' => $call->summary,
            'transcript' => $call->transcript,
            'assigned_to' => $currentAssignment ? $currentAssignment->assignedTo : null,
            'notes' => $call->callNotes->map(function ($note) {
                return [
                    'id' => $note->id,
                    'content' => $note->content,
                    'user' => $note->user ? [
                        'id' => $note->user->id,
                        'name' => $note->user->name,
                    ] : null,
                    'created_at' => $note->created_at->format('d.m.Y H:i'),
                ];
            }),
            'assignment_history' => [],
            'analysis_score' => $call->analysis_score,
            'start_price' => $call->start_price,
            'total_cost' => $call->total_cost,
            'metadata' => $call->metadata,
            'recording_url' => $call->recording_url,
            'customer' => $call->customer,
            'extracted_data' => $call->metadata,
        ],
    ];
    
    echo "API Response structure:\n";
    echo json_encode($apiResponse, JSON_PRETTY_PRINT);
} else {
    echo "Call 262 not found\n";
}