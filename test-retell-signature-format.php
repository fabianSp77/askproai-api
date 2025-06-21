<?php

// Test different signature formats for Retell webhook

$webhookUrl = 'https://api.askproai.de/api/retell/webhook';
$apiKey = 'key_6ff998ba48e842092e04a5455d19';

// Create a test payload
$payload = [
    'event' => 'call_ended',
    'call' => [
        'call_id' => '8fe67ef8-3cd7-4e6b-9ad1-' . substr(uniqid(), 0, 12),
        'agent_id' => 'agent_9a8202a740cd3120d96fcfda1e',
        'call_type' => 'inbound',
        'call_status' => 'ended',
        'start_timestamp' => (time() - 120) * 1000,
        'end_timestamp' => time() * 1000,
        'duration_ms' => 120000,
        'from_number' => '+4915234567890',
        'to_number' => '+493083793369',
        'transcript' => 'Test call with different signature formats',
        'transcript_object' => [],
        'cost' => 0.25
    ]
];

$jsonPayload = json_encode($payload);
$timestamp = time();

echo "Testing different Retell signature formats...\n\n";

// Format 1: timestamp.payload with separate headers
echo "=== Format 1: Separate timestamp header ===\n";
$signaturePayload1 = $timestamp . '.' . $jsonPayload;
$signature1 = hash_hmac('sha256', $signaturePayload1, $apiKey);

$ch = curl_init($webhookUrl);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonPayload);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Accept: application/json',
    'X-Retell-Signature: ' . $signature1,
    'X-Retell-Timestamp: ' . $timestamp
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "Response Code: $httpCode\n";
echo ($httpCode == 200) ? "✅ SUCCESS!\n" : "❌ FAILED\n";
echo "Response: " . substr($response, 0, 200) . "\n\n";

// Format 2: v=timestamp,signature format
echo "=== Format 2: v=timestamp,signature format ===\n";
$signaturePayload2 = $timestamp . '.' . $jsonPayload;
$signature2 = hash_hmac('sha256', $signaturePayload2, $apiKey);
$headerValue2 = 'v=' . $timestamp . ',' . $signature2;

$ch = curl_init($webhookUrl);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonPayload);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Accept: application/json',
    'X-Retell-Signature: ' . $headerValue2
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "Response Code: $httpCode\n";
echo ($httpCode == 200) ? "✅ SUCCESS!\n" : "❌ FAILED\n";
echo "Response: " . substr($response, 0, 200) . "\n\n";

// Format 3: Just payload without timestamp
echo "=== Format 3: Just payload (no timestamp) ===\n";
$signature3 = hash_hmac('sha256', $jsonPayload, $apiKey);

$ch = curl_init($webhookUrl);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonPayload);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Accept: application/json',
    'X-Retell-Signature: ' . $signature3
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "Response Code: $httpCode\n";
echo ($httpCode == 200) ? "✅ SUCCESS!\n" : "❌ FAILED\n";
echo "Response: " . substr($response, 0, 200) . "\n\n";

// Format 4: Base64 encoded signature
echo "=== Format 4: Base64 encoded signature ===\n";
$signaturePayload4 = $timestamp . '.' . $jsonPayload;
$signature4 = base64_encode(hash_hmac('sha256', $signaturePayload4, $apiKey, true));

$ch = curl_init($webhookUrl);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonPayload);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Accept: application/json',
    'X-Retell-Signature: ' . $signature4,
    'X-Retell-Timestamp: ' . $timestamp
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "Response Code: $httpCode\n";
echo ($httpCode == 200) ? "✅ SUCCESS!\n" : "❌ FAILED\n";
echo "Response: " . substr($response, 0, 200) . "\n";