<?php
// Test Translation API directly
require_once __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

// Create a request
$request = Illuminate\Http\Request::create(
    '/admin-api/translations/languages',
    'GET',
    [],
    [],
    [],
    [
        'HTTP_ACCEPT' => 'application/json',
        'HTTP_AUTHORIZATION' => 'Bearer 45|WYhFfzNjL2H6T0dTaZVaOJoCPQUAr2ktsH02QqPu34d2e786'
    ]
);

$response = $kernel->handle($request);
$content = $response->getContent();

header('Content-Type: application/json');
echo json_encode([
    'status' => $response->getStatusCode(),
    'content' => json_decode($content, true) ?: $content,
    'headers' => $response->headers->all()
], JSON_PRETTY_PRINT);

$kernel->terminate($request, $response);