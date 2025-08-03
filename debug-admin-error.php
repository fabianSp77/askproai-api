<?php
require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

try {
    $request = Illuminate\Http\Request::create('/admin/login', 'GET');
    $response = $kernel->handle($request);
    
    if ($response->getStatusCode() !== 200) {
        echo "Status Code: " . $response->getStatusCode() . "\n";
        echo "Content: " . substr($response->getContent(), 0, 1000) . "\n";
    } else {
        echo "Admin login page loaded successfully!\n";
    }
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
    echo "Trace:\n" . $e->getTraceAsString() . "\n";
}