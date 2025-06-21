<?php

// Test webhook signature verification with exact Retell format
$webhookUrl = 'https://api.askproai.de/api/retell/webhook';
$webhookSecret = 'key_6ff998ba48e842092e04a5455d19'; // Using API key as secret

// Test payload that mimics Retell's format
$payload = [
    'event' => 'call_ended',
    'call' => [
        'call_id' => 'test_' . uniqid(),
        'agent_id' => 'agent_9a8202a740cd3120d96fcfda1e',
        'call_type' => 'inbound',
        'call_status' => 'ended',
        'start_timestamp' => (time() - 300) * 1000, // 5 minutes ago
        'end_timestamp' => time() * 1000,
        'disconnect_reason' => 'user_hangup',
        'duration_ms' => 300000, // 5 minutes
        'from_number' => '+4915234567890',
        'to_number' => '+493083793369',
        'transcript' => 'Hallo, ich möchte einen Termin buchen.',
        'transcript_object' => [],
        'recording_url' => null,
        'public_log_url' => null,
        'e2e_latency' => null,
        'llm_latency' => null,
        'llm_websocket_network_rtt_latencies' => [],
        'cost' => 0.25
    ]
];

// Encode JSON without escaping slashes
$jsonPayload = json_encode($payload, JSON_UNESCAPED_SLASHES);
$timestamp = (string)time();

// Create signature payload as timestamp.body
$signaturePayload = $timestamp . '.' . $jsonPayload;

// Calculate HMAC-SHA256 signature
$signature = hash_hmac('sha256', $signaturePayload, $webhookSecret);

echo "Testing Retell webhook with exact format...\n";
echo "Webhook URL: $webhookUrl\n";
echo "Timestamp: $timestamp\n";
echo "Signature: $signature\n";
echo "Payload length: " . strlen($jsonPayload) . "\n";
echo "Signature payload length: " . strlen($signaturePayload) . "\n\n";

// Send the request
$ch = curl_init($webhookUrl);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonPayload);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Accept: application/json',
    'X-Retell-Signature: ' . $signature,
    'X-Retell-Timestamp: ' . $timestamp,
    'User-Agent: Retell-Webhook/1.0'
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
curl_setopt($ch, CURLOPT_VERBOSE, true);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

echo "Response Code: $httpCode\n";
if ($curlError) {
    echo "CURL Error: $curlError\n";
}

if ($httpCode == 200 || $httpCode == 201) {
    echo "✅ SUCCESS! Webhook accepted\n";
    echo "Response: " . substr($response, 0, 500) . "\n";
} else {
    echo "❌ FAILED! Response: " . substr($response, 0, 500) . "\n";
    
    // If failed, also test with different signature formats
    echo "\n--- Testing alternative signature formats ---\n";
    
    // Test 1: Without timestamp in payload
    $altSignature1 = hash_hmac('sha256', $jsonPayload, $webhookSecret);
    echo "Alt 1 (no timestamp): " . substr($altSignature1, 0, 20) . "...\n";
    
    // Test 2: With v= prefix format
    $altSignature2 = 'v=' . $timestamp . ',' . $signature;
    echo "Alt 2 (v= format): " . substr($altSignature2, 0, 30) . "...\n";
}