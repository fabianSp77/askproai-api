#!/usr/bin/env php
<?php

require __DIR__ . '/vendor/autoload.php';

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "🚀 TRIGGERING TEST WEBHOOK\n";
echo "==========================\n\n";

// Webhook data that mimics a Retell call_ended event
$webhookData = [
    'event_type' => 'call_ended',
    'event_id' => 'test_' . uniqid(),
    'call' => [
        'id' => 'test_call_' . time(),
        'from_number' => '+491234567890',
        'to_number' => '+493083793369',
        'direction' => 'inbound',
        'call_status' => 'ended',
        'duration_seconds' => 180,
        'start_timestamp' => time() - 180,
        'end_timestamp' => time(),
        'disconnection_reason' => 'user_hangup'
    ],
    'retell_llm_dynamic_variables' => [
        'appointment_date' => '2025-06-28',
        'appointment_time' => '14:00',
        'customer_name' => 'Test Kunde',
        'customer_phone' => '+491234567890',
        'service_type' => 'Beratung'
    ]
];

// Generate a signature (in production, Retell would do this)
$secret = config('services.retell.webhook_secret', 'test-secret');
$signature = hash_hmac('sha256', json_encode($webhookData), $secret);

echo "Webhook URL: https://api.askproai.de/api/retell/webhook\n";
echo "Event Type: call_ended\n";
echo "Call ID: {$webhookData['call']['id']}\n\n";

// Send the webhook
try {
    $response = Http::withHeaders([
        'x-retell-signature' => $signature,
        'Content-Type' => 'application/json'
    ])->post('https://api.askproai.de/api/retell/webhook', $webhookData);
    
    echo "Response Status: " . $response->status() . "\n";
    echo "Response Body: " . $response->body() . "\n\n";
    
    if ($response->successful()) {
        echo "✅ Webhook sent successfully!\n";
        echo "\nNow check:\n";
        echo "1. php check-webhook-processing.php\n";
        echo "2. Check the calls dashboard\n";
        echo "3. Check failed jobs: php artisan queue:failed\n";
    } else {
        echo "❌ Webhook failed with status " . $response->status() . "\n";
    }
} catch (\Exception $e) {
    echo "❌ Error sending webhook: " . $e->getMessage() . "\n";
}

// Also try the internal route to bypass signature verification
echo "\n\nTrying internal webhook route (bypassing signature)...\n";
try {
    // Use the test webhook endpoint which doesn't require signature
    $testResponse = Http::post('https://api.askproai.de/api/test/webhook', $webhookData);
    
    echo "Test Response Status: " . $testResponse->status() . "\n";
    echo "Test Response Body: " . $testResponse->body() . "\n";
} catch (\Exception $e) {
    echo "Test endpoint error: " . $e->getMessage() . "\n";
}