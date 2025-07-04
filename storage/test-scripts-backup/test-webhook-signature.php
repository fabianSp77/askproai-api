<?php

/**
 * Test Retell Webhook Signature Verification
 */

// Load environment
require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

echo "\n🧪 Testing Retell Webhook Signature Verification\n";
echo "================================================\n\n";

// Get API key from config
$apiKey = config('services.retell.api_key') ?? config('services.retell.token');
if (!$apiKey) {
    die("❌ Error: No Retell API key configured!\n");
}

echo "✅ API Key found: " . substr($apiKey, 0, 10) . "...\n\n";

// Test payload
$payload = [
    'event' => 'call_ended',
    'call' => [
        'call_id' => 'test_' . uniqid(),
        'call_type' => 'phone_call',
        'from_number' => '+1234567890',
        'to_number' => '+0987654321',
        'direction' => 'inbound',
        'agent_id' => 'test_agent',
        'start_timestamp' => time() * 1000,
        'end_timestamp' => (time() + 120) * 1000,
        'duration' => 120000,
        'disconnection_reason' => 'user_hangup'
    ]
];

$jsonPayload = json_encode($payload);

echo "📝 Test Cases:\n\n";

// Test 1: Valid signature (body only)
echo "1. Valid signature (body only method):\n";
$signature1 = hash_hmac('sha256', $jsonPayload, $apiKey);
echo "   Signature: " . substr($signature1, 0, 20) . "...\n";
testWebhook($jsonPayload, $signature1, "Valid body-only signature");

// Test 2: Valid signature (with timestamp)
echo "\n2. Valid signature (timestamp method):\n";
$timestamp = time() * 1000; // milliseconds
$payloadWithTimestamp = "$timestamp.$jsonPayload";
$signature2 = hash_hmac('sha256', $payloadWithTimestamp, $apiKey);
$signatureHeader = "v=$timestamp,d=$signature2";
echo "   Signature: $signatureHeader\n";
testWebhook($jsonPayload, $signatureHeader, "Valid timestamp signature");

// Test 3: Invalid signature
echo "\n3. Invalid signature:\n";
$invalidSignature = hash_hmac('sha256', $jsonPayload, 'wrong_key');
echo "   Signature: " . substr($invalidSignature, 0, 20) . "...\n";
testWebhook($jsonPayload, $invalidSignature, "Invalid signature");

// Test 4: Missing signature
echo "\n4. Missing signature:\n";
testWebhook($jsonPayload, null, "Missing signature");

// Test 5: Empty signature
echo "\n5. Empty signature:\n";
testWebhook($jsonPayload, '', "Empty signature");

echo "\n📊 Summary:\n";
echo "===========\n";
echo "If tests 1 and 2 pass (200/204) and tests 3-5 fail (401), signature verification is working correctly!\n\n";

function testWebhook($payload, $signature, $testName) {
    $ch = curl_init();
    
    $headers = [
        'Content-Type: application/json'
    ];
    
    if ($signature !== null) {
        $headers[] = 'X-Retell-Signature: ' . $signature;
    }
    
    curl_setopt_array($ch, [
        CURLOPT_URL => 'http://localhost/api/retell/webhook',
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $payload,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HEADER => true,
        CURLOPT_TIMEOUT => 10
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $body = substr($response, $headerSize);
    
    curl_close($ch);
    
    $icon = ($httpCode == 200 || $httpCode == 204) ? '✅' : '❌';
    $expectedCode = (strpos($testName, 'Valid') !== false) ? '200/204' : '401';
    $status = (
        (strpos($testName, 'Valid') !== false && ($httpCode == 200 || $httpCode == 204)) ||
        (strpos($testName, 'Valid') === false && $httpCode == 401)
    ) ? 'PASS' : 'FAIL';
    
    echo "   Result: $icon HTTP $httpCode (Expected: $expectedCode) - $status\n";
    
    if ($body && $httpCode != 204) {
        $bodyPreview = substr(str_replace(["\n", "\r"], ' ', $body), 0, 100);
        echo "   Response: $bodyPreview\n";
    }
}

// Also create a curl command for manual testing
echo "\n📋 Manual Test Commands:\n";
echo "======================\n\n";

$testSignature = hash_hmac('sha256', $jsonPayload, $apiKey);
echo "# Test with valid signature:\n";
echo "curl -X POST http://localhost/api/retell/webhook \\\n";
echo "  -H 'Content-Type: application/json' \\\n";
echo "  -H 'X-Retell-Signature: $testSignature' \\\n";
echo "  -d '" . json_encode($payload) . "'\n\n";

echo "# Test with invalid signature:\n";
echo "curl -X POST http://localhost/api/retell/webhook \\\n";
echo "  -H 'Content-Type: application/json' \\\n";
echo "  -H 'X-Retell-Signature: invalid_signature_123' \\\n";
echo "  -d '" . json_encode($payload) . "'\n";