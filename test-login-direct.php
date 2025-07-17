<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

echo "=== Testing Direct Login ===\n\n";

// Simulate POST request to login
$request = \Illuminate\Http\Request::create(
    '/api/admin/auth/login',
    'POST',
    [
        'email' => 'admin@askproai.de',
        'password' => 'admin123'
    ],
    [],
    [],
    [
        'HTTP_ACCEPT' => 'application/json',
        'CONTENT_TYPE' => 'application/json'
    ]
);

try {
    $response = $kernel->handle($request);
    
    echo "Response Status: " . $response->getStatusCode() . "\n";
    echo "Response Headers:\n";
    foreach ($response->headers->all() as $key => $values) {
        echo "  $key: " . implode(', ', $values) . "\n";
    }
    echo "\nResponse Body:\n";
    echo $response->getContent() . "\n";
    
    if ($response->getStatusCode() === 200) {
        echo "\n✅ Login successful!\n";
        $data = json_decode($response->getContent(), true);
        if (isset($data['token'])) {
            echo "Token: " . substr($data['token'], 0, 30) . "...\n";
        }
    } else {
        echo "\n❌ Login failed with status " . $response->getStatusCode() . "\n";
    }
} catch (\Exception $e) {
    echo "❌ Exception: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "Trace:\n" . $e->getTraceAsString() . "\n";
}

$kernel->terminate($request, $response);