<?php

/**
 * Test script to verify Retell webhook signatures
 * Usage: php test-retell-webhook-signatures.php
 */

require_once __DIR__ . '/vendor/autoload.php';

// Configuration
$webhookUrl = 'https://api.askproai.de/api/retell/webhook';
$apiKey = 'key_6ff998ba48e842092e04a5455d19'; // Your actual API key

// Test payload
$payload = [
    'event' => 'call_ended',
    'call' => [
        'call_id' => 'test_' . uniqid(),
        'agent_id' => 'agent_9a8202a740cd3120d96fcfda1e',
        'call_type' => 'inbound',
        'call_status' => 'ended',
        'start_timestamp' => (time() - 120) * 1000,
        'end_timestamp' => time() * 1000,
        'duration_ms' => 120000,
        'from_number' => '+4915234567890',
        'to_number' => '+493083793369',
        'transcript' => 'Test webhook signature verification',
        'cost' => 0.25
    ]
];

$jsonPayload = json_encode($payload);
$timestamp = time();

echo "=== Retell Webhook Signature Test ===\n\n";
echo "Webhook URL: {$webhookUrl}\n";
echo "Timestamp: {$timestamp}\n";
echo "Payload: " . substr($jsonPayload, 0, 100) . "...\n\n";

// Test different signature methods
$tests = [
    [
        'name' => 'Method 1: timestamp.payload with separate headers',
        'payload' => "{$timestamp}.{$jsonPayload}",
        'headers' => function($signature) use ($timestamp) {
            return [
                'X-Retell-Signature: ' . $signature,
                'X-Retell-Timestamp: ' . $timestamp
            ];
        }
    ],
    [
        'name' => 'Method 2: v=timestamp,signature format',
        'payload' => "{$timestamp}.{$jsonPayload}",
        'headers' => function($signature) use ($timestamp) {
            return [
                'X-Retell-Signature: v=' . $timestamp . ',' . $signature
            ];
        }
    ],
    [
        'name' => 'Method 3: Just payload (no timestamp)',
        'payload' => $jsonPayload,
        'headers' => function($signature) {
            return [
                'X-Retell-Signature: ' . $signature
            ];
        }
    ],
    [
        'name' => 'Method 4: Base64 encoded signature',
        'payload' => "{$timestamp}.{$jsonPayload}",
        'headers' => function($signature) use ($timestamp) {
            $base64Sig = base64_encode(hash_hmac('sha256', "{$timestamp}.{$jsonPayload}", $GLOBALS['apiKey'], true));
            return [
                'X-Retell-Signature: ' . $base64Sig,
                'X-Retell-Timestamp: ' . $timestamp
            ];
        },
        'skip_hash' => true
    ]
];

foreach ($tests as $test) {
    echo "Testing: {$test['name']}\n";
    
    // Calculate signature
    if (!isset($test['skip_hash']) || !$test['skip_hash']) {
        $signature = hash_hmac('sha256', $test['payload'], $apiKey);
    } else {
        $signature = null; // Will be calculated in headers function
    }
    
    // Prepare headers
    $headers = array_merge(
        [
            'Content-Type: application/json',
            'Accept: application/json'
        ],
        $test['headers']($signature)
    );
    
    // Send request
    $ch = curl_init($webhookUrl);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonPayload);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        echo "  ❌ CURL Error: {$error}\n\n";
        continue;
    }
    
    echo "  Response Code: {$httpCode}\n";
    
    if ($httpCode == 200 || $httpCode == 201) {
        echo "  ✅ SUCCESS! Signature method works\n";
        $responseData = json_decode($response, true);
        if ($responseData) {
            echo "  Response: " . json_encode($responseData, JSON_PRETTY_PRINT) . "\n";
        }
    } else {
        echo "  ❌ FAILED! ";
        if ($httpCode == 401) {
            echo "Invalid signature\n";
        } elseif ($httpCode == 500) {
            echo "Server error\n";
        } else {
            echo "Unexpected response\n";
        }
        echo "  Response: " . substr($response, 0, 200) . "\n";
    }
    
    echo "\n";
}

// Test with debug endpoint
echo "Testing: Debug endpoint (should work with any signature)\n";
$debugUrl = str_replace('/webhook', '/debug-webhook', $webhookUrl);

$ch = curl_init($debugUrl);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonPayload);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Accept: application/json',
    'X-Retell-Signature: debug_test',
    'X-Retell-Timestamp: ' . $timestamp
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "  Response Code: {$httpCode}\n";
if ($httpCode == 200 || $httpCode == 201) {
    echo "  ✅ Debug endpoint is working\n";
} else {
    echo "  ❌ Debug endpoint failed\n";
}

echo "\n=== Test Complete ===\n";