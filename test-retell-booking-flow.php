#!/usr/bin/env php
<?php
/**
 * Test Retell Booking Flow - End-to-End
 * 
 * This script simulates a complete Retell call flow with appointment booking
 */

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Services\MCP\RetellCustomFunctionMCPServer;
use App\Services\Webhooks\RetellWebhookHandler;
use App\Services\WebhookProcessor;
use App\Models\WebhookEvent;
use App\Models\Call;
use App\Models\Appointment;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

echo "\n========================================\n";
echo "RETELL BOOKING FLOW TEST\n";
echo "========================================\n";

// Test configuration
$testCallId = 'test_call_' . Str::uuid();
$testPhoneNumber = '+49 30 837 93 369'; // AskProAI Berlin number
$customerPhone = '+49 151 12345678';
$customerName = 'Test Kunde';

echo "\nTest Configuration:\n";
echo "- Call ID: $testCallId\n";
echo "- Company Phone: $testPhoneNumber\n";
echo "- Customer Phone: $customerPhone\n";
echo "- Customer Name: $customerName\n";

// Step 1: Simulate custom function call (collect_appointment)
echo "\n[STEP 1] Simulating Retell custom function call...\n";

$customFunctionServer = app(RetellCustomFunctionMCPServer::class);

$appointmentData = [
    'call_id' => $testCallId,
    'caller_number' => $customerPhone,
    'to_number' => $testPhoneNumber,
    'name' => $customerName,
    'telefonnummer' => $customerPhone,
    'dienstleistung' => 'Beratungsgespräch',
    'datum' => 'morgen',
    'uhrzeit' => '14:00',
    'notizen' => 'Testbuchung über Retell'
];

$result = $customFunctionServer->collect_appointment($appointmentData);

echo "Custom function result:\n";
echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";

if (!$result['success']) {
    echo "\n❌ Custom function failed!\n";
    exit(1);
}

// Verify cache
$cacheKey = "retell:appointment:{$testCallId}";
$cachedData = Cache::get($cacheKey);

if ($cachedData) {
    echo "\n✅ Data successfully cached\n";
    echo "Cache key: $cacheKey\n";
    echo "Cached data: " . json_encode($cachedData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
} else {
    echo "\n❌ Data NOT found in cache!\n";
    exit(1);
}

// Step 2: Simulate webhook (call_ended)
echo "\n[STEP 2] Simulating Retell webhook (call_ended)...\n";

$webhookPayload = [
    'event' => 'call_ended',
    'call' => [
        'call_id' => $testCallId,
        'from_number' => $customerPhone,
        'to_number' => $testPhoneNumber,
        'direction' => 'inbound',
        'call_duration' => 180,
        'start_timestamp' => (time() - 180) * 1000,
        'end_timestamp' => time() * 1000,
        'disconnection_reason' => 'hangup',
        'agent_id' => 'agent_test123',
        'retell_llm_dynamic_variables' => [
            'appointment_collected' => true
        ]
    ]
];

// Process webhook
$webhookProcessor = app(WebhookProcessor::class);

try {
    $webhookResult = $webhookProcessor->process(
        WebhookEvent::PROVIDER_RETELL,
        $webhookPayload,
        ['x-retell-signature' => ['test_signature']],
        $testCallId
    );
    
    echo "Webhook processing result:\n";
    echo json_encode($webhookResult, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
    
} catch (\Exception $e) {
    echo "\n❌ Webhook processing failed: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}

// Step 3: Check results
echo "\n[STEP 3] Checking results...\n";

// Check if call was created
$call = Call::withoutGlobalScope(\App\Scopes\TenantScope::class)
    ->where('retell_call_id', $testCallId)
    ->first();
if ($call) {
    echo "\n✅ Call record created:\n";
    echo "- ID: {$call->id}\n";
    echo "- Status: {$call->status}\n";
    echo "- Duration: {$call->duration}s\n";
    echo "- From: {$call->from_number}\n";
    echo "- To: {$call->to_number}\n";
    
    if ($call->appointment_id) {
        echo "- Appointment ID: {$call->appointment_id}\n";
        
        $appointment = Appointment::withoutGlobalScope(\App\Scopes\TenantScope::class)
            ->find($call->appointment_id);
        if ($appointment) {
            echo "\n✅ Appointment created:\n";
            echo "- Customer: {$appointment->customer->name}\n";
            echo "- Service: {$appointment->service->name}\n";
            echo "- Date/Time: {$appointment->starts_at}\n";
            echo "- Status: {$appointment->status}\n";
        }
    } else {
        echo "\n⚠️  No appointment linked to call\n";
    }
} else {
    echo "\n❌ No call record found!\n";
}

// Step 4: Debug information
echo "\n[STEP 4] Debug Information...\n";

// Check webhook events
$webhookEvents = WebhookEvent::where('event_id', $testCallId)
    ->orWhere('correlation_id', $testCallId)
    ->get();

echo "\nWebhook Events:\n";
foreach ($webhookEvents as $event) {
    echo "- {$event->event_type} ({$event->status}) - {$event->created_at}\n";
}

// Check logs
echo "\nRecent relevant logs:\n";
$logs = DB::table('webhook_logs')
    ->where('correlation_id', $testCallId)
    ->orWhere('webhook_id', 'LIKE', "%{$testCallId}%")
    ->orderBy('created_at', 'desc')
    ->limit(10)
    ->get();

foreach ($logs as $log) {
    echo "- [{$log->created_at}] {$log->provider} - {$log->event_type} - {$log->status}\n";
    if ($log->error_message) {
        echo "  ERROR: {$log->error_message}\n";
    }
}

// Cleanup
echo "\n[CLEANUP] Removing test data...\n";
Cache::forget($cacheKey);
if (isset($call)) {
    $call->delete();
}

echo "\n========================================\n";
echo "TEST COMPLETED\n";
echo "========================================\n\n";