<?php

/**
 * Direct Cal.com Webhook Handler
 * URL: https://api.askproai.de/calcom.php
 *
 * This file handles Cal.com webhooks directly without Laravel routing
 */

// Get request method
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

// Handle GET request (ping test)
if ($method === 'GET') {
    header('Content-Type: application/json');
    header('HTTP/1.1 200 OK');
    echo json_encode(['ping' => 'ok']);
    exit;
}

// Handle POST request (webhook event)
if ($method === 'POST') {
    // Get raw input
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    // Get signature from header
    $signature = $_SERVER['HTTP_X_CAL_SIGNATURE_256'] ?? null;

    // Verify signature (using the secret from Cal.com)
    $secret = '6846aed4d55f6f3df70c40781e02d964aae34147f72763e1ccedd726e66dfff7';

    if ($signature) {
        $expectedSignature = hash_hmac('sha256', $input, $secret);
        if (!hash_equals($expectedSignature, $signature)) {
            header('HTTP/1.1 401 Unauthorized');
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Invalid signature']);
            exit;
        }
    }

    // Log the webhook to a file for debugging
    $logFile = __DIR__ . '/../storage/logs/calcom-webhooks.log';
    $logEntry = date('[Y-m-d H:i:s] ') . json_encode([
        'triggerEvent' => $data['triggerEvent'] ?? 'unknown',
        'payload' => isset($data['payload']) ? 'payload_received' : 'no_payload'
    ]) . PHP_EOL;

    // Ensure log directory exists
    $logDir = dirname($logFile);
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }

    file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);

    // For now, just return success to Cal.com
    // We'll process the webhook asynchronously later
    header('Content-Type: application/json');
    header('HTTP/1.1 200 OK');
    echo json_encode([
        'ok' => true,
        'message' => 'Webhook received',
        'triggerEvent' => $data['triggerEvent'] ?? 'unknown'
    ]);

    // TODO: Process webhook with Laravel in background
    // We can use a queue job or exec() to process asynchronously

    exit;
}

// Method not allowed
header('HTTP/1.1 405 Method Not Allowed');
echo json_encode(['error' => 'Method not allowed']);
exit;