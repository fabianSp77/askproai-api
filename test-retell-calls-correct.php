<?php

// Test Retell API with correct endpoint
$apiKey = 'key_6ff998ba48e842092e04a5455d19';
$baseUrl = 'https://api.retellai.com';

echo "Testing Retell API - Correct Endpoints...\n\n";

// Test 1: List calls with correct endpoint
echo "1. Fetching recent calls (v2 endpoint):\n";
$ch = curl_init($baseUrl . '/v2/list-calls?limit=5&sort_order=descending');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $apiKey,
    'Accept: application/json'
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "   Response Code: $httpCode\n";

if ($httpCode == 200) {
    $data = json_decode($response, true);
    $calls = $data['calls'] ?? $data;
    
    if (is_array($calls)) {
        echo "   ✅ SUCCESS! Found " . count($calls) . " calls\n";
        
        if (!empty($calls)) {
            echo "\n   Recent calls:\n";
            foreach (array_slice($calls, 0, 3) as $call) {
                echo "   - Call ID: " . $call['call_id'] . "\n";
                echo "     Status: " . $call['call_status'] . "\n";
                echo "     Type: " . $call['call_type'] . "\n";
                echo "     From: " . ($call['from_number'] ?? 'N/A') . "\n";
                echo "     To: " . ($call['to_number'] ?? 'N/A') . "\n";
                if (isset($call['start_timestamp'])) {
                    echo "     Date: " . date('Y-m-d H:i:s', $call['start_timestamp'] / 1000) . "\n";
                }
                echo "\n";
            }
        }
    }
} else {
    // Try v1 endpoint
    echo "   Trying v1 endpoint...\n";
    $ch = curl_init($baseUrl . '/list-calls');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $apiKey,
        'Accept: application/json'
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    echo "   V1 Response Code: $httpCode\n";
    if ($httpCode != 200) {
        echo "   ❌ Both endpoints failed\n";
    }
}

// Test 2: Test webhook with correct signature
echo "\n2. Testing webhook signature (manual calculation):\n";

$webhookSecret = $apiKey; // Use API key as webhook secret
$testPayload = [
    'event' => 'test_webhook',
    'timestamp' => time() * 1000,
    'data' => [
        'message' => 'Testing webhook configuration'
    ]
];

$jsonPayload = json_encode($testPayload);
$timestamp = (string)time();

// Try different signature methods
echo "\n   Testing signature methods:\n";

// Method 1: timestamp.payload
$payload1 = $timestamp . '.' . $jsonPayload;
$sig1 = hash_hmac('sha256', $payload1, $webhookSecret);
echo "   - Method 1 (timestamp.payload): " . substr($sig1, 0, 20) . "...\n";

// Method 2: Just payload
$sig2 = hash_hmac('sha256', $jsonPayload, $webhookSecret);
echo "   - Method 2 (payload only): " . substr($sig2, 0, 20) . "...\n";

// Method 3: Stripe-style (timestamp.payload with specific format)
$sig3 = hash_hmac('sha256', $timestamp . $jsonPayload, $webhookSecret);
echo "   - Method 3 (timestamp+payload): " . substr($sig3, 0, 20) . "...\n";

echo "\nDone!\n";