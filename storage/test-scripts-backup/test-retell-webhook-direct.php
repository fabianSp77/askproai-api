#!/usr/bin/env php
<?php

require_once __DIR__ . '/vendor/autoload.php';

use Illuminate\Support\Facades\Log;

// Colors for output
$red = "\033[31m";
$green = "\033[32m";
$yellow = "\033[33m";
$blue = "\033[34m";
$reset = "\033[0m";

echo "{$blue}=== Retell Webhook Direct Test ==={$reset}\n\n";

// Configuration
$webhookUrl = 'https://api.askproai.de/api/retell/webhook';
$webhookSecret = 'key_6ff998ba48e842092e04a5455d19'; // From .env

// Test payload (minimal valid webhook)
$payload = [
    'event' => 'call_ended',
    'call' => [
        'call_id' => 'test_' . uniqid(),
        'call_type' => 'phone_call',
        'from_number' => '+491234567890',
        'to_number' => '+49308379369',
        'direction' => 'inbound',
        'agent_id' => 'test_agent_id',
        'call_status' => 'ended',
        'start_timestamp' => time() * 1000 - 60000, // 1 minute ago
        'end_timestamp' => time() * 1000,
        'disconnection_reason' => 'user_hangup',
        'transcript' => 'Test call transcript',
        'opt_out_sensitive_data_storage' => false
    ]
];

$jsonPayload = json_encode($payload);
$timestamp = time() * 1000; // milliseconds

echo "{$yellow}Payload:{$reset}\n";
echo json_encode($payload, JSON_PRETTY_PRINT) . "\n\n";

// Generate signature (Method 1: timestamp.body)
$signaturePayload = "{$timestamp}.{$jsonPayload}";
$signature = hash_hmac('sha256', $signaturePayload, $webhookSecret);

// Create the combined header format
$signatureHeader = "v={$timestamp},d={$signature}";

echo "{$yellow}Signature Details:{$reset}\n";
echo "Timestamp: {$timestamp}\n";
echo "Signature: {$signature}\n";
echo "Header: {$signatureHeader}\n\n";

// Send webhook
$ch = curl_init($webhookUrl);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonPayload);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'X-Retell-Signature: ' . $signatureHeader,
    'X-Retell-Timestamp: ' . $timestamp
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HEADER, true);
curl_setopt($ch, CURLOPT_VERBOSE, true);

$verbose = fopen('php://temp', 'w+');
curl_setopt($ch, CURLOPT_STDERR, $verbose);

echo "{$yellow}Sending webhook to: {$webhookUrl}{$reset}\n\n";

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
$headers = substr($response, 0, $headerSize);
$body = substr($response, $headerSize);

rewind($verbose);
$verboseLog = stream_get_contents($verbose);

echo "{$yellow}Verbose Log:{$reset}\n";
echo $verboseLog . "\n";

echo "{$yellow}Response Headers:{$reset}\n";
echo $headers . "\n";

echo "{$yellow}Response Code: {$httpCode}{$reset}\n";
echo "{$yellow}Response Body:{$reset}\n";
echo $body . "\n\n";

if ($httpCode >= 200 && $httpCode < 300) {
    echo "{$green}✓ Webhook sent successfully!{$reset}\n";
} else {
    echo "{$red}✗ Webhook failed!{$reset}\n";
}

curl_close($ch);

// Also test the debug endpoints
echo "\n{$blue}=== Testing Debug Endpoints ==={$reset}\n\n";

$debugEndpoints = [
    '/api/retell/webhook-debug',
    '/api/retell/webhook-nosig',
    '/api/mcp/retell/webhook'
];

foreach ($debugEndpoints as $endpoint) {
    $url = 'https://api.askproai.de' . $endpoint;
    echo "{$yellow}Testing: {$url}{$reset}\n";
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonPayload);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'X-Retell-Signature: ' . $signatureHeader,
        'X-Retell-Timestamp: ' . $timestamp
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    echo "Response Code: {$httpCode}\n";
    if (!empty($response)) {
        echo "Response: " . substr($response, 0, 200) . "...\n";
    }
    echo "\n";
    
    curl_close($ch);
}

echo "\n{$green}Test completed!{$reset}\n";