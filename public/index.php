<?php

use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// Determine if the application is in maintenance mode...
if (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) {
    require $maintenance;
}

// Register the Composer autoloader...
require __DIR__.'/../vendor/autoload.php';

// Special handling for Cal.com webhooks (bypass all middleware)
$requestUri = $_SERVER['REQUEST_URI'] ?? '';
$requestMethod = $_SERVER['REQUEST_METHOD'] ?? '';

// Handle Cal.com webhook ping test (GET request)
if ($requestUri === '/webhooks/calcom' && $requestMethod === 'GET') {
    header('Content-Type: application/json');
    header('HTTP/1.1 200 OK');
    echo json_encode(['ping' => 'ok']);
    exit;
}

// Handle Cal.com webhook POST (pass to Laravel but mark for special handling)
if ($requestUri === '/webhooks/calcom' && $requestMethod === 'POST') {
    // Let Laravel handle it, but set a flag to bypass redirects
    $_SERVER['CALCOM_WEBHOOK'] = true;
}

// Bootstrap Laravel and handle the request...
(require_once __DIR__.'/../bootstrap/app.php')
    ->handleRequest(Request::capture());
