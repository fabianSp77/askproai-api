<?php

/**
 * Simple webhook capture script to see exactly what Retell sends
 * Deploy this temporarily to capture real webhook data
 * 
 * Usage: 
 * 1. Update a Retell agent webhook URL to: https://your-domain.com/capture-retell-webhook.php
 * 2. Make a test call
 * 3. Check the capture-retell-webhook.log file
 */

// Log file path
$logFile = __DIR__ . '/capture-retell-webhook.log';

// Capture all request data
$requestData = [
    'timestamp' => date('Y-m-d H:i:s'),
    'method' => $_SERVER['REQUEST_METHOD'] ?? 'UNKNOWN',
    'uri' => $_SERVER['REQUEST_URI'] ?? 'UNKNOWN',
    'headers' => getallheaders(),
    'body' => file_get_contents('php://input'),
    'server' => $_SERVER,
];

// Parse body if JSON
$body = $requestData['body'];
if (!empty($body)) {
    $decoded = json_decode($body, true);
    if (json_last_error() === JSON_ERROR_NONE) {
        $requestData['body_parsed'] = $decoded;
    }
}

// Check signature if present
$signature = $requestData['headers']['X-Retell-Signature'] ?? null;
$timestamp = $requestData['headers']['X-Retell-Timestamp'] ?? null;

if ($signature) {
    // Try to verify with known methods
    $apiKey = 'key_6ff998ba48e842092e04a5455d19'; // Replace with your API key
    
    $verificationAttempts = [];
    
    // Method 1: timestamp.body
    if ($timestamp) {
        $payload1 = "{$timestamp}.{$body}";
        $expected1 = hash_hmac('sha256', $payload1, $apiKey);
        $verificationAttempts['timestamp_dot_body'] = [
            'expected' => substr($expected1, 0, 20) . '...',
            'matches' => ($expected1 === $signature)
        ];
    }
    
    // Method 2: Just body
    $expected2 = hash_hmac('sha256', $body, $apiKey);
    $verificationAttempts['body_only'] = [
        'expected' => substr($expected2, 0, 20) . '...',
        'matches' => ($expected2 === $signature)
    ];
    
    // Method 3: Base64 encoded
    if ($timestamp) {
        $payload3 = "{$timestamp}.{$body}";
        $expected3 = base64_encode(hash_hmac('sha256', $payload3, $apiKey, true));
        $verificationAttempts['base64_timestamp_body'] = [
            'expected' => substr($expected3, 0, 20) . '...',
            'matches' => ($expected3 === $signature)
        ];
    }
    
    $requestData['signature_verification'] = [
        'received' => substr($signature, 0, 20) . '...',
        'attempts' => $verificationAttempts
    ];
}

// Write to log file
$logEntry = json_encode($requestData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n\n" . str_repeat('=', 80) . "\n\n";
file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);

// Return success response
header('Content-Type: application/json');
http_response_code(200);
echo json_encode([
    'success' => true,
    'message' => 'Webhook captured successfully',
    'timestamp' => $requestData['timestamp']
]);

// Also output to screen if accessed directly
if (php_sapi_name() === 'cli') {
    echo "\nCaptured webhook data:\n";
    echo $logEntry;
}