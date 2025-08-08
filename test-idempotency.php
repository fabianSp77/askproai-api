#!/usr/bin/env php
<?php

// Test idempotency with valid signature
$webhookSecret = 'key_6ff998ba48e842092e04a5455d19';
$url = 'https://api.askproai.de/api/retell/webhook-simple';

// Test payload
$payload = [
    'event' => 'call_ended',
    'retell_call_id' => 'test_idempotency_' . time(),
    'call_id' => 'test_' . time(),
    'from_number' => '+491234567890',
    'to_number' => '+499999999999'
];

$payloadJson = json_encode($payload);
$timestamp = time() * 1000;
$signaturePayload = "{$timestamp}.{$payloadJson}";
$signature = hash_hmac('sha256', $signaturePayload, $webhookSecret);

echo "Testing idempotency with duplicate requests...\n\n";

// Send first request
echo "Request 1: ";
$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => $payloadJson,
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/json',
        'X-Retell-Signature: v=' . $timestamp . ',d=' . $signature,
        'X-Retell-Timestamp: ' . $timestamp,
        'X-Correlation-ID: test-1'
    ],
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 5
]);

$response1 = curl_exec($ch);
$httpCode1 = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP $httpCode1 - Response: $response1\n";

// Wait a moment
usleep(100000); // 100ms

// Send duplicate request
echo "Request 2 (duplicate): ";
$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => $payloadJson,
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/json',
        'X-Retell-Signature: v=' . $timestamp . ',d=' . $signature,
        'X-Retell-Timestamp: ' . $timestamp,
        'X-Correlation-ID: test-2'
    ],
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 5
]);

$response2 = curl_exec($ch);
$httpCode2 = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP $httpCode2 - Response: $response2\n";

$response2Data = json_decode($response2, true);
if (isset($response2Data['duplicate']) && $response2Data['duplicate'] === true) {
    echo "\n✅ IDEMPOTENCY WORKS! Duplicate correctly detected.\n";
} else {
    echo "\n⚠️  Duplicate not explicitly marked, but may still be handled correctly.\n";
}