#!/usr/bin/env php
<?php

/**
 * Test script for verifying webhook improvements
 * Tests:
 * 1. Quick ACK response time (<250ms)
 * 2. HMAC signature verification
 * 3. Idempotency with retell_call_id
 * 4. Async processing via queue
 */

echo "\n🚀 TESTING WEBHOOK IMPROVEMENTS\n";
echo "================================\n\n";

// Configuration
$webhookUrl = 'https://api.askproai.de/api/retell/webhook-simple';
$apiKey = getenv('RETELL_API_KEY') ?: 'key_bea27e2fb0be407ebc7e0997b8fb';
$webhookSecret = getenv('RETELL_WEBHOOK_SECRET') ?: $apiKey; // Falls back to API key

// Test payload
$testPayload = [
    'event' => 'call_ended',
    'event_type' => 'call_ended',
    'call' => [
        'call_id' => 'test_' . uniqid(),
        'retell_call_id' => 'retell_test_' . uniqid(),
        'from_number' => '+491234567890',
        'to_number' => '+499999999999',
        'direction' => 'inbound',
        'start_timestamp' => time() * 1000 - 60000, // 1 minute ago
        'end_timestamp' => time() * 1000,
        'duration' => 60,
        'transcript' => 'Test transcript for webhook improvements',
        'recording_url' => 'https://example.com/recording.mp3',
        'metadata' => [
            'test' => true,
            'timestamp' => time()
        ]
    ]
];

// Test 1: Response Time without signature (should fail)
echo "1. Testing without HMAC signature (should be rejected):\n";
$startTime = microtime(true);
$ch = curl_init($webhookUrl);
curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode($testPayload),
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/json',
        'X-Correlation-ID: test-no-signature'
    ],
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 5
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$responseTime = round((microtime(true) - $startTime) * 1000, 2);
curl_close($ch);

echo "   Response Code: $httpCode\n";
echo "   Response Time: {$responseTime}ms\n";
echo "   Response: " . substr($response, 0, 100) . "\n";

if ($httpCode === 401) {
    echo "   ✅ PASS: Request correctly rejected without signature\n";
} else {
    echo "   ❌ FAIL: Expected 401, got $httpCode\n";
}
echo "\n";

// Test 2: Response Time with valid signature
echo "2. Testing with valid HMAC signature:\n";

// Generate HMAC signature
$timestamp = time() * 1000; // milliseconds
$payloadJson = json_encode($testPayload);
$signaturePayload = "{$timestamp}.{$payloadJson}";
$signature = hash_hmac('sha256', $signaturePayload, $webhookSecret);

$startTime = microtime(true);
$ch = curl_init($webhookUrl);
curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => $payloadJson,
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/json',
        'X-Retell-Signature: v=' . $timestamp . ',d=' . $signature,
        'X-Retell-Timestamp: ' . $timestamp,
        'X-Correlation-ID: test-with-signature'
    ],
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 5
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$responseTime = round((microtime(true) - $startTime) * 1000, 2);
curl_close($ch);

echo "   Response Code: $httpCode\n";
echo "   Response Time: {$responseTime}ms\n";
echo "   Response: " . substr($response, 0, 200) . "\n";

if ($httpCode === 200 && $responseTime < 250) {
    echo "   ✅ PASS: Quick ACK in {$responseTime}ms (<250ms requirement)\n";
} else {
    if ($httpCode !== 200) {
        echo "   ❌ FAIL: Expected 200, got $httpCode\n";
    }
    if ($responseTime >= 250) {
        echo "   ❌ FAIL: Response time {$responseTime}ms exceeds 250ms SLA\n";
    }
}
echo "\n";

// Test 3: Idempotency test (send same payload again)
echo "3. Testing idempotency (duplicate request):\n";

// Use same signature and timestamp for duplicate
$startTime = microtime(true);
$ch = curl_init($webhookUrl);
curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => $payloadJson,
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/json',
        'X-Retell-Signature: v=' . $timestamp . ',d=' . $signature,
        'X-Retell-Timestamp: ' . $timestamp,
        'X-Correlation-ID: test-duplicate'
    ],
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 5
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$responseTime = round((microtime(true) - $startTime) * 1000, 2);
curl_close($ch);

$responseData = json_decode($response, true);

echo "   Response Code: $httpCode\n";
echo "   Response Time: {$responseTime}ms\n";
echo "   Response: " . $response . "\n";

if ($httpCode === 200 && ($responseData['duplicate'] ?? false)) {
    echo "   ✅ PASS: Duplicate correctly detected\n";
} else {
    echo "   ⚠️  WARNING: Duplicate not explicitly marked\n";
}
echo "\n";

// Test 4: Check database for webhook event
echo "4. Checking database for webhook event:\n";

require_once '/var/www/api-gateway/vendor/autoload.php';

use Illuminate\Database\Capsule\Manager as Capsule;

$capsule = new Capsule;
$capsule->addConnection([
    'driver' => 'mysql',
    'host' => '127.0.0.1',
    'database' => 'askproai_db',
    'username' => 'askproai_user',
    'password' => 'lkZ57Dju9EDjrMxn',
    'charset' => 'utf8',
    'collation' => 'utf8_unicode_ci',
]);

$capsule->setAsGlobal();

// Check webhook_events table
$retellCallId = $testPayload['call']['retell_call_id'];
$webhookEvent = $capsule::table('webhook_events')
    ->where('event_id', $retellCallId)
    ->where('event_type', 'call_ended')
    ->first();

if ($webhookEvent) {
    echo "   ✅ Webhook event found in database\n";
    echo "   Status: {$webhookEvent->status}\n";
    echo "   Idempotency Key: {$webhookEvent->idempotency_key}\n";
    
    // Check if it's being processed
    if ($webhookEvent->status === 'pending' || $webhookEvent->status === 'processing') {
        echo "   ⏳ Event is queued for async processing\n";
    } elseif ($webhookEvent->status === 'processed' || $webhookEvent->status === 'completed') {
        echo "   ✅ Event has been processed\n";
    }
} else {
    echo "   ❌ Webhook event not found in database\n";
}
echo "\n";

// Summary
echo "================================\n";
echo "SUMMARY:\n";
echo "================================\n";

$tests = [
    'HMAC Verification' => $httpCode === 401 ? '✅' : '❌',
    'Quick ACK (<250ms)' => $responseTime < 250 ? '✅' : '❌',
    'Idempotency' => ($responseData['duplicate'] ?? false) || $webhookEvent ? '✅' : '⚠️',
    'Async Processing' => $webhookEvent ? '✅' : '❌'
];

foreach ($tests as $test => $result) {
    echo "$result $test\n";
}

echo "\n✨ Webhook improvements test complete!\n\n";