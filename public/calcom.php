<?php

/**
 * Cal.com Webhook Handler - Queue-Based Processing
 * URL: https://api.askproai.de/calcom.php
 *
 * This file receives Cal.com webhooks and queues them for processing.
 * A Laravel queue worker picks them up and processes asynchronously.
 *
 * Fixed: 2025-11-22 - Simple queue-based approach
 */

// Get request method
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

// Handle GET request (ping test)
if ($method === 'GET') {
    header('Content-Type: application/json');
    header('HTTP/1.1 200 OK');
    echo json_encode(['ping' => 'ok', 'status' => 'ready']);
    exit;
}

// Handle POST request (webhook event)
if ($method === 'POST') {
    try {
        // Get raw input
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);

        if (!$data) {
            throw new Exception('Invalid JSON payload');
        }

        // Get signature from header
        $signature = $_SERVER['HTTP_X_CAL_SIGNATURE_256'] ??
                     $_SERVER['HTTP_CAL_SIGNATURE_256'] ??
                     null;

        // Verify signature
        $secret = '6846aed4d55f6f3df70c40781e02d964aae34147f72763e1ccedd726e66dfff7';

        $valid = [
            hash_hmac('sha256', $input, $secret),
            hash_hmac('sha256', rtrim($input, "\r\n"), $secret),
            'sha256=' . hash_hmac('sha256', $input, $secret),
            'sha256=' . hash_hmac('sha256', rtrim($input, "\r\n"), $secret),
        ];

        if ($signature && !in_array($signature, $valid, true)) {
            header('HTTP/1.1 401 Unauthorized');
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Invalid signature']);
            exit;
        }

        // Quick log for debugging
        $quickLog = __DIR__ . '/../storage/logs/calcom-webhooks-received.log';
        $logEntry = '[' . date('Y-m-d H:i:s') . '] ' . ($data['triggerEvent'] ?? 'unknown') . PHP_EOL;
        @file_put_contents($quickLog, $logEntry, FILE_APPEND | LOCK_EX);

        // Handle PING immediately
        $triggerEvent = $data['triggerEvent'] ?? $data['event'] ?? null;

        if ($triggerEvent === 'PING') {
            header('Content-Type: application/json');
            header('HTTP/1.1 200 OK');
            echo json_encode([
                'received' => true,
                'status' => 'ok',
                'message' => 'PING received'
            ]);
            exit;
        }

        // Queue webhook for processing
        // Write to a queue file that Laravel worker will pick up
        $queueDir = __DIR__ . '/../storage/app/webhook-queue';
        if (!is_dir($queueDir)) {
            mkdir($queueDir, 0755, true);
        }

        $queueFile = $queueDir . '/webhook-' . time() . '-' . uniqid() . '.json';
        file_put_contents($queueFile, $input, LOCK_EX);

        // Trigger Laravel processing in background (fire and forget)
        $laravelPath = realpath(__DIR__ . '/..');
        $command = "cd " . escapeshellarg($laravelPath) . " && php artisan calcom:process-queued-webhooks > /dev/null 2>&1 &";
        @exec($command);

        // Return success immediately to Cal.com
        header('Content-Type: application/json');
        header('HTTP/1.1 200 OK');
        echo json_encode([
            'received' => true,
            'status' => 'queued',
            'triggerEvent' => $triggerEvent
        ]);

    } catch (Exception $e) {
        // Log error
        $errorLog = __DIR__ . '/../storage/logs/calcom-webhooks-error.log';
        $logEntry = '[' . date('Y-m-d H:i:s') . '] ERROR: ' . $e->getMessage() . PHP_EOL;
        @file_put_contents($errorLog, $logEntry, FILE_APPEND | LOCK_EX);

        // Return error
        header('HTTP/1.1 500 Internal Server Error');
        header('Content-Type: application/json');
        echo json_encode([
            'error' => 'Webhook processing failed',
            'message' => $e->getMessage()
        ]);
    }

    exit;
}

// Method not allowed
header('HTTP/1.1 405 Method Not Allowed');
header('Content-Type: application/json');
echo json_encode(['error' => 'Method not allowed']);
exit;
