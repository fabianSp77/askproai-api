<?php

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== Laravel Admin Debug ===\n\n";

try {
    // Load Laravel
    require __DIR__.'/../vendor/autoload.php';
    echo "✓ Autoloader loaded\n";
    
    $app = require_once __DIR__.'/../bootstrap/app.php';
    echo "✓ Bootstrap loaded\n";
    
    $kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
    echo "✓ Kernel created\n";
    
    // Create a request for /admin
    $request = Illuminate\Http\Request::create('/admin', 'GET');
    echo "✓ Request created for /admin\n";
    
    // Process the request
    $response = $kernel->handle($request);
    echo "✓ Request handled\n";
    echo "Response status: " . $response->getStatusCode() . "\n";
    
    if ($response->getStatusCode() >= 400) {
        echo "\nResponse headers:\n";
        foreach ($response->headers->all() as $key => $values) {
            foreach ($values as $value) {
                echo "  $key: $value\n";
            }
        }
        
        echo "\nResponse content (first 500 chars):\n";
        echo substr($response->getContent(), 0, 500) . "...\n";
    }
    
    // Terminate
    $kernel->terminate($request, $response);
    echo "✓ Kernel terminated\n";
    
} catch (\Exception $e) {
    echo "\n✗ Exception: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
    echo "\nTrace:\n" . $e->getTraceAsString() . "\n";
} catch (\Error $e) {
    echo "\n✗ Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
    echo "\nTrace:\n" . $e->getTraceAsString() . "\n";
}