<?php
require __DIR__ . '/vendor/autoload.php';

use App\Models\Company;
use App\Models\Branch;
use App\Models\WebhookEvent;
use App\Models\Call;
use Illuminate\Support\Facades\Http;

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "🔍 WEBHOOK FLOW TEST\n";
echo "====================\n\n";

// Test 1: Check webhook configuration
echo "1. WEBHOOK CONFIGURATION CHECK\n";
echo "------------------------------\n";

echo "Webhook URL: https://api.askproai.de/api/retell/webhook\n";
echo "Signature Header: x-retell-signature\n";
echo "Webhook Secret: " . (config('services.retell.secret') ? 'SET' : 'NOT SET - Using API Key') . "\n";

$webhookSecret = config('services.retell.secret') ?: config('services.retell.api_key');
if ($webhookSecret) {
    echo "Secret/Key: " . substr($webhookSecret, 0, 10) . "...\n";
} else {
    echo "❌ No webhook secret or API key found!\n";
}

// Test 2: Simulate a webhook
echo "\n\n2. SIMULATING WEBHOOK CALL\n";
echo "---------------------------\n";

$testPayload = [
    'event' => 'call_ended',
    'call' => [
        'call_id' => 'test_' . uniqid(),
        'call_type' => 'phone_call',
        'from_number' => '+4916043665555',
        'to_number' => '+493083793369', // Berlin branch number
        'direction' => 'inbound',
        'agent_id' => 'agent_9a8202a740cd3120d96fcfda1e',
        'call_status' => 'completed',
        'start_timestamp' => (time() - 300) * 1000, // 5 minutes ago
        'end_timestamp' => time() * 1000,
        'duration' => 300,
        'disconnection_reason' => 'user_hangup',
        'transcript' => 'Test transcript',
        'transcript_object' => [],
        'metadata' => ['test' => true]
    ]
];

$jsonPayload = json_encode($testPayload);
$timestamp = time();
$signaturePayload = $timestamp . '.' . $jsonPayload;
$signature = hash_hmac('sha256', $signaturePayload, $webhookSecret);

echo "Payload: " . json_encode($testPayload, JSON_PRETTY_PRINT) . "\n";
echo "Timestamp: $timestamp\n";
echo "Signature: $signature\n";

// Test 3: Make actual webhook request
echo "\n\n3. SENDING WEBHOOK REQUEST\n";
echo "--------------------------\n";

try {
    $response = Http::withHeaders([
        'Content-Type' => 'application/json',
        'X-Retell-Signature' => 'v=' . $timestamp . ',' . $signature,
        'X-Retell-Timestamp' => (string)$timestamp
    ])->post('http://localhost:8000/api/retell/webhook', $testPayload);
    
    echo "Response Status: " . $response->status() . "\n";
    echo "Response Body: " . $response->body() . "\n";
    
    if ($response->successful()) {
        echo "✅ Webhook accepted!\n";
    } else {
        echo "❌ Webhook rejected!\n";
    }
} catch (\Exception $e) {
    echo "❌ Error sending webhook: " . $e->getMessage() . "\n";
}

// Test 4: Check database
echo "\n\n4. DATABASE CHECK\n";
echo "-----------------\n";

sleep(2); // Give it time to process

$recentWebhook = WebhookEvent::orderBy('created_at', 'desc')->first();
if ($recentWebhook) {
    echo "Most recent webhook:\n";
    echo "  - Provider: " . $recentWebhook->provider . "\n";
    echo "  - Event Type: " . $recentWebhook->event_type . "\n";
    echo "  - Status: " . $recentWebhook->status . "\n";
    echo "  - Created: " . $recentWebhook->created_at->format('Y-m-d H:i:s') . "\n";
} else {
    echo "❌ No webhooks found in database\n";
}

$recentCall = \App\Models\Call::withoutGlobalScope(\App\Scopes\TenantScope::class)
    ->where('retell_call_id', 'LIKE', 'test_%')
    ->orderBy('created_at', 'desc')
    ->first();
    
if ($recentCall) {
    echo "\nTest call found:\n";
    echo "  - ID: " . $recentCall->id . "\n";
    echo "  - Retell ID: " . $recentCall->retell_call_id . "\n";
    echo "  - Created: " . $recentCall->created_at->format('Y-m-d H:i:s') . "\n";
} else {
    echo "\n❌ Test call not found in database\n";
}

// Test 5: Test webhook signature verification
echo "\n\n5. WEBHOOK SIGNATURE VERIFICATION TEST\n";
echo "--------------------------------------\n";

// Test with wrong signature
$wrongSignature = hash_hmac('sha256', 'wrong payload', $webhookSecret);

try {
    $response = Http::withHeaders([
        'Content-Type' => 'application/json',
        'X-Retell-Signature' => 'v=' . $timestamp . ',' . $wrongSignature,
        'X-Retell-Timestamp' => (string)$timestamp
    ])->post('http://localhost:8000/api/retell/webhook', $testPayload);
    
    if ($response->status() === 401) {
        echo "✅ Invalid signature correctly rejected (401)\n";
    } else {
        echo "⚠️  Invalid signature not rejected! Status: " . $response->status() . "\n";
    }
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

// Test 6: Check logs
echo "\n\n6. LOG CHECK\n";
echo "-------------\n";

$logFile = storage_path('logs/laravel.log');
$recentLogs = shell_exec("tail -n 50 $logFile | grep -i 'retell\\|webhook' | tail -n 10");
if ($recentLogs) {
    echo "Recent webhook logs:\n";
    echo $recentLogs;
} else {
    echo "No recent webhook logs found\n";
}

echo "\n✅ Test completed\n";