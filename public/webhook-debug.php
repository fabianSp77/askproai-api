<?php
// Emergency webhook debug endpoint
// This will capture EVERYTHING and show what's causing 500 errors

ini_set('display_errors', 1);
error_reporting(E_ALL);

// Capture everything
$timestamp = date('Y-m-d H:i:s');
$headers = getallheaders();
$body = file_get_contents('php://input');
$ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

// Log to file
$logEntry = [
    'timestamp' => $timestamp,
    'ip' => $ip,
    'headers' => $headers,
    'body' => $body,
    'json' => json_decode($body, true),
    'error' => json_last_error_msg()
];

file_put_contents(
    __DIR__ . '/../storage/logs/webhook-debug.log',
    json_encode($logEntry, JSON_PRETTY_PRINT) . "\n---\n",
    FILE_APPEND | LOCK_EX
);

// Try to process with Laravel
try {
    require_once __DIR__ . '/../vendor/autoload.php';
    $app = require_once __DIR__ . '/../bootstrap/app.php';
    $kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
    $kernel->bootstrap();
    
    // Create request
    $request = \Illuminate\Http\Request::create(
        '/api/retell/webhook-simple',
        'POST',
        [],
        [],
        [],
        $_SERVER,
        $body
    );
    
    // Call controller directly
    $controller = new \App\Http\Controllers\Api\RetellWebhookWorkingController();
    $response = $controller->handle($request);
    
    // Send response
    header('Content-Type: application/json');
    http_response_code($response->getStatusCode());
    echo $response->getContent();
    
} catch (\Throwable $e) {
    // Log error
    $errorLog = [
        'timestamp' => date('Y-m-d H:i:s'),
        'error' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => $e->getTraceAsString()
    ];
    
    file_put_contents(
        __DIR__ . '/../storage/logs/webhook-debug-errors.log',
        json_encode($errorLog, JSON_PRETTY_PRINT) . "\n---\n",
        FILE_APPEND | LOCK_EX
    );
    
    // Return error
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode([
        'error' => 'Internal Server Error',
        'message' => $e->getMessage(),
        'debug' => [
            'file' => basename($e->getFile()),
            'line' => $e->getLine()
        ]
    ]);
}