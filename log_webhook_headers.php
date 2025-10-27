<?php

// Temporary script to log ALL webhook headers and body
// This will help us understand what Retell AI actually sends

$logFile = __DIR__ . '/webhook_debug.log';

$timestamp = date('Y-m-d H:i:s');
$headers = getallheaders();
$body = file_get_contents('php://input');
$method = $_SERVER['REQUEST_METHOD'];
$uri = $_SERVER['REQUEST_URI'];
$ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

$logEntry = "\n" . str_repeat('=', 80) . "\n";
$logEntry .= "[$timestamp] Webhook Received\n";
$logEntry .= str_repeat('=', 80) . "\n";
$logEntry .= "Method: $method\n";
$logEntry .= "URI: $uri\n";
$logEntry .= "IP: $ip\n\n";

$logEntry .= "Headers:\n";
$logEntry .= str_repeat('-', 80) . "\n";
foreach ($headers as $name => $value) {
    $logEntry .= "$name: $value\n";
}

$logEntry .= "\nBody:\n";
$logEntry .= str_repeat('-', 80) . "\n";
$logEntry .= $body . "\n";
$logEntry .= str_repeat('=', 80) . "\n\n";

file_put_contents($logFile, $logEntry, FILE_APPEND);

// Return success
header('Content-Type: application/json');
echo json_encode([
    'success' => true,
    'logged' => true,
    'timestamp' => $timestamp,
]);
