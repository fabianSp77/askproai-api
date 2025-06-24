<?php

// Simple webhook capture script
$logFile = '/tmp/retell_webhook_capture.log';
$timestamp = date('Y-m-d H:i:s');

// Capture ALL incoming data
$data = [
    'timestamp' => $timestamp,
    'method' => $_SERVER['REQUEST_METHOD'] ?? 'unknown',
    'uri' => $_SERVER['REQUEST_URI'] ?? 'unknown',
    'headers' => getallheaders(),
    'get' => $_GET,
    'post' => $_POST,
    'raw_body' => file_get_contents('php://input'),
    'server' => $_SERVER
];

// Write to log
file_put_contents($logFile, "\n\n=== WEBHOOK CAPTURE $timestamp ===\n" . json_encode($data, JSON_PRETTY_PRINT), FILE_APPEND);

// Return success to Retell
http_response_code(200);
echo json_encode(['status' => 'captured', 'timestamp' => $timestamp]);

echo "\n\nData captured to: $logFile\n";