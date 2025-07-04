<?php

use Illuminate\Support\Facades\Http;
use Illuminate\Http\Request;

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== Testing Livewire Update Endpoint ===\n\n";

// Generate a CSRF token
$token = csrf_token();
echo "1. CSRF Token: " . substr($token, 0, 20) . "...\n\n";

// Test the endpoint locally
echo "2. Testing POST to /livewire/update:\n";

try {
    // Create a mock Livewire update payload
    $payload = [
        'components' => [
            [
                'snapshot' => json_encode([
                    'data' => [],
                    'memo' => [
                        'id' => 'test-component',
                        'name' => 'test-component',
                        'path' => 'test',
                        'method' => 'GET',
                        'children' => [],
                        'scripts' => [],
                        'assets' => [],
                        'errors' => [],
                        'locale' => 'en',
                    ]
                ]),
                'updates' => [],
                'calls' => []
            ]
        ]
    ];
    
    // Make internal request
    $request = Request::create('/livewire/update', 'POST', $payload);
    $request->headers->set('X-Livewire', 'true');
    $request->headers->set('X-CSRF-TOKEN', $token);
    $request->headers->set('Accept', 'application/json');
    
    $response = $app->handle($request);
    
    echo "   - Status Code: " . $response->getStatusCode() . "\n";
    echo "   - Content Type: " . $response->headers->get('Content-Type') . "\n";
    
    if ($response->getStatusCode() !== 200) {
        echo "   - Response: " . substr($response->getContent(), 0, 500) . "\n";
    } else {
        echo "   - Response: Success\n";
    }
    
} catch (\Exception $e) {
    echo "   - Error: " . $e->getMessage() . "\n";
    echo "   - File: " . $e->getFile() . ":" . $e->getLine() . "\n";
}

echo "\n3. Checking middleware order:\n";
$kernel = app()->make(\Illuminate\Contracts\Http\Kernel::class);
$middleware = $kernel->getMiddleware();
foreach ($middleware as $mw) {
    echo "   - Global: {$mw}\n";
}

echo "\n4. Checking if StartSession middleware is running:\n";
if (session()->isStarted()) {
    echo "   - Session is started: ✓\n";
    echo "   - Session ID: " . session()->getId() . "\n";
} else {
    echo "   - Session is NOT started: ✗\n";
}

echo "\n=== Test Complete ===\n";