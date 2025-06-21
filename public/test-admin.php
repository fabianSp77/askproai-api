<?php
// Direct test of admin panel without Laravel routing
require __DIR__.'/../vendor/autoload.php';

try {
    $app = require_once __DIR__.'/../bootstrap/app.php';
    $kernel = $app->make(\Illuminate\Contracts\Http\Kernel::class);
    
    // Create a fake request to /admin/login
    $request = \Illuminate\Http\Request::create('/admin/login', 'GET');
    $response = $kernel->handle($request);
    
    echo "Status: " . $response->getStatusCode() . "\n";
    echo "Headers:\n";
    foreach ($response->headers->all() as $key => $value) {
        echo "  $key: " . implode(', ', $value) . "\n";
    }
    
    if ($response->getStatusCode() >= 400) {
        echo "\nError Response:\n";
        echo substr($response->getContent(), 0, 1000) . "\n";
    } else {
        echo "\nSuccess! Admin login page loaded.\n";
    }
    
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . " Line: " . $e->getLine() . "\n";
}