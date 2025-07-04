<?php
// Webhook logger endpoint - logs all incoming webhook requests

$logFile = __DIR__ . '/../storage/logs/webhook-debug.log';

// Ensure log directory exists
$logDir = dirname($logFile);
if (!is_dir($logDir)) {
    mkdir($logDir, 0777, true);
}

// Get all request data
$headers = getallheaders();
$body = file_get_contents('php://input');

$logEntry = [
    'timestamp' => date('Y-m-d H:i:s.u'),
    'method' => $_SERVER['REQUEST_METHOD'],
    'uri' => $_SERVER['REQUEST_URI'],
    'query' => $_GET,
    'headers' => $headers,
    'body_raw' => $body,
    'body_decoded' => json_decode($body, true),
    'ip' => $_SERVER['REMOTE_ADDR'],
    'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
    'retell_signature' => $headers['X-Retell-Signature'] ?? null,
    'server' => [
        'HTTP_HOST' => $_SERVER['HTTP_HOST'],
        'SERVER_NAME' => $_SERVER['SERVER_NAME'],
        'REQUEST_TIME' => $_SERVER['REQUEST_TIME'],
    ]
];

// Write to log
$logLine = date('Y-m-d H:i:s') . ' | WEBHOOK | ' . json_encode($logEntry, JSON_PRETTY_PRINT) . "\n" . str_repeat('-', 80) . "\n";
file_put_contents($logFile, $logLine, FILE_APPEND | LOCK_EX);

// Also create a simpler log for quick viewing
$simpleLog = date('Y-m-d H:i:s') . " | " . $_SERVER['REQUEST_METHOD'] . " " . $_SERVER['REQUEST_URI'] . " | From: " . $_SERVER['REMOTE_ADDR'] . " | Retell: " . ($headers['X-Retell-Signature'] ? 'YES' : 'NO') . "\n";
file_put_contents($logDir . '/webhook-simple.log', $simpleLog, FILE_APPEND | LOCK_EX);

// Forward to the real webhook handler
// But first, let's just return success to see if we get any webhooks
http_response_code(200);
header('Content-Type: application/json');
echo json_encode([
    'status' => 'logged',
    'message' => 'Webhook received and logged',
    'timestamp' => date('c')
]);