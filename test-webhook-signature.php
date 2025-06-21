<?php

// Test webhook signature with API key as secret
$webhookUrl = 'https://api.askproai.de/api/retell/webhook';
$apiKey = 'key_6ff998ba48e842092e04a5455d19';

// Create a test payload
$payload = [
    'event' => 'call_ended',
    'call' => [
        'call_id' => '8fe67ef8-3cd7-37cc-4e6b-96be08d12345',
        'agent_id' => 'agent_9a8202a740cd3120d96fcfda1e',
        'call_type' => 'inbound',
        'call_status' => 'ended',
        'start_timestamp' => (time() - 120) * 1000,
        'end_timestamp' => time() * 1000,
        'duration_ms' => 120000,
        'from_number' => '+4915234567890',
        'to_number' => '+493083793369',
        'transcript' => 'Test call',
        'transcript_object' => [],
        'cost' => 0.25
    ]
];

$jsonPayload = json_encode($payload);
$timestamp = time();

// Create signature using API key as secret
$signaturePayload = $timestamp . '.' . $jsonPayload;
$signature = hash_hmac('sha256', $signaturePayload, $apiKey);

echo "Testing webhook with proper signature...\n";
echo "Timestamp: $timestamp\n";
echo "Signature: $signature\n\n";

// Send request
$ch = curl_init($webhookUrl);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonPayload);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Accept: application/json',
    'X-Retell-Signature: ' . $signature,
    'X-Retell-Timestamp: ' . $timestamp
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "Response Code: $httpCode\n";

if ($httpCode == 200 || $httpCode == 201) {
    echo "✅ SUCCESS! Webhook accepted with signature verification\n";
    echo "Response: " . substr($response, 0, 200) . "\n";
} else {
    echo "❌ FAILED! Response: " . substr($response, 0, 500) . "\n";
}