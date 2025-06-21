<?php

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle($request = Illuminate\Http\Request::capture());

use App\Services\MCP\WebhookMCPServer;
use App\Services\MCP\CalcomMCPServer;
use App\Services\MCP\RetellMCPServer;
use App\Services\MCP\DatabaseMCPServer;
use App\Services\MCP\QueueMCPServer;

echo "\n" . str_repeat('=', 60) . "\n";
echo "MCP WEBHOOK DEBUG TEST\n";
echo str_repeat('=', 60) . "\n\n";

// Test webhook payload
$payload = [
    'event' => 'call_ended',
    'call' => [
        'call_id' => 'test_debug_' . time(),
        'agent_id' => 'agent_9a8202a740cd3120d96fcfda1e',
        'from_number' => '+491234567890',
        'to_number' => '+493083793369',
        'direction' => 'inbound',
        'call_status' => 'ended',
        'start_timestamp' => now()->subMinutes(5)->timestamp * 1000,
        'end_timestamp' => now()->timestamp * 1000,
        'duration_ms' => 300000, // 5 minutes
        'transcript' => 'Test transcript',
        'summary' => 'Test appointment booking',
        'call_analysis' => [
            'appointment_requested' => true,
            'customer_name' => 'Test Customer',
            'sentiment' => 'positive'
        ],
        'retell_llm_dynamic_variables' => [
            'booking_confirmed' => true,
            'name' => 'Test Customer',
            'datum' => '2025-06-25',
            'uhrzeit' => '14:00',
            'dienstleistung' => 'Beratungsgespräch'
        ]
    ]
];

// Create MCP servers
$calcomMCP = new CalcomMCPServer();
$retellMCP = new RetellMCPServer();
$databaseMCP = new DatabaseMCPServer();
$queueMCP = new QueueMCPServer();

// Create webhook processor
$webhookMCP = new WebhookMCPServer($calcomMCP, $retellMCP, $databaseMCP, $queueMCP);

echo "Testing webhook processing...\n\n";

// Enable detailed logging
\Illuminate\Support\Facades\Log::listen(function ($message) {
    echo "[LOG] " . $message->message . "\n";
});

// Process webhook
$result = $webhookMCP->processRetellWebhook($payload);

echo "\nResult:\n";
print_r($result);

// Check what happened
echo "\n\nChecking database...\n";

// Check call
$call = \App\Models\Call::where('retell_call_id', $payload['call']['call_id'])->first();
if ($call) {
    echo "✅ Call created: ID " . $call->id . "\n";
    echo "  - Customer ID: " . $call->customer_id . "\n";
    echo "  - Branch ID: " . $call->branch_id . "\n";
    echo "  - Extracted date: " . $call->extracted_date . "\n";
    echo "  - Extracted time: " . $call->extracted_time . "\n";
    echo "  - Appointment ID: " . ($call->appointment_id ?: 'None') . "\n";
} else {
    echo "❌ No call found\n";
}

// Check appointment
if ($call && $call->appointment_id) {
    $appointment = \App\Models\Appointment::find($call->appointment_id);
    if ($appointment) {
        echo "\n✅ Appointment created:\n";
        echo "  - Date: " . $appointment->appointment_date . "\n";
        echo "  - Time: " . $appointment->start_time . "\n";
        echo "  - Service: " . $appointment->service_id . "\n";
    }
}

echo "\n" . str_repeat('=', 60) . "\n";
echo "DEBUG COMPLETE\n";
echo str_repeat('=', 60) . "\n";