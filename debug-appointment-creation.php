<?php

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle($request = Illuminate\Http\Request::capture());

use App\Services\MCP\WebhookMCPServer;

echo "\n" . str_repeat('=', 60) . "\n";
echo "DEBUG APPOINTMENT CREATION\n";
echo str_repeat('=', 60) . "\n\n";

// Test data that should create an appointment
$callData = [
    'call_id' => 'debug_' . time(),
    'retell_llm_dynamic_variables' => [
        'booking_confirmed' => true,
        'name' => 'Test Customer',
        'datum' => '2025-06-25', 
        'uhrzeit' => '14:00',
        'dienstleistung' => 'Beratung'
    ]
];

// Create a mock WebhookMCPServer to test the logic
class TestWebhookMCP extends App\Services\MCP\WebhookMCPServer {
    public function testShouldCreate($callData) {
        return $this->shouldCreateAppointment($callData);
    }
}

// Use dependency injection to create the server
$webhookMCP = app(TestWebhookMCP::class);

// Test 1: Boolean true
echo "Test 1 - Boolean true:\n";
$callData['retell_llm_dynamic_variables']['booking_confirmed'] = true;
$result = $webhookMCP->testShouldCreate($callData);
echo "Result: " . ($result ? 'YES' : 'NO') . "\n";
echo "Type: " . gettype($callData['retell_llm_dynamic_variables']['booking_confirmed']) . "\n\n";

// Test 2: String 'true'
echo "Test 2 - String 'true':\n";
$callData['retell_llm_dynamic_variables']['booking_confirmed'] = 'true';
$result = $webhookMCP->testShouldCreate($callData);
echo "Result: " . ($result ? 'YES' : 'NO') . "\n";
echo "Type: " . gettype($callData['retell_llm_dynamic_variables']['booking_confirmed']) . "\n\n";

// Test 3: String '1'
echo "Test 3 - String '1':\n";
$callData['retell_llm_dynamic_variables']['booking_confirmed'] = '1';
$result = $webhookMCP->testShouldCreate($callData);
echo "Result: " . ($result ? 'YES' : 'NO') . "\n";
echo "Type: " . gettype($callData['retell_llm_dynamic_variables']['booking_confirmed']) . "\n\n";

// Test 4: Integer 1
echo "Test 4 - Integer 1:\n";
$callData['retell_llm_dynamic_variables']['booking_confirmed'] = 1;
$result = $webhookMCP->testShouldCreate($callData);
echo "Result: " . ($result ? 'YES' : 'NO') . "\n";
echo "Type: " . gettype($callData['retell_llm_dynamic_variables']['booking_confirmed']) . "\n\n";

// Test 5: Check actual webhook processing
echo "Test 5 - Process actual webhook:\n";
$webhookData = [
    'event' => 'call_ended',
    'call' => array_merge($callData, [
        'agent_id' => 'agent_9a8202a740cd3120d96fcfda1e',
        'from_number' => '+491234567890',
        'to_number' => '+493083793369',
        'direction' => 'inbound',
        'call_status' => 'ended',
        'start_timestamp' => now()->subMinutes(5)->timestamp * 1000,
        'end_timestamp' => now()->timestamp * 1000,
        'duration_ms' => 300000,
        'transcript' => 'Test',
        'summary' => 'Test booking'
    ])
];

// Create fresh instance
$webhookMCP = app(App\Services\MCP\WebhookMCPServer::class);

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Process webhook
$processResult = $webhookMCP->processRetellWebhook($webhookData);
echo "Process result:\n";
print_r($processResult);

echo "\n" . str_repeat('=', 60) . "\n";