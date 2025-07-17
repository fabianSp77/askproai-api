<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    require_once __DIR__ . '/../vendor/autoload.php';
    $app = require_once __DIR__ . '/../bootstrap/app.php';
    
    // Create a request to business portal
    $request = \Illuminate\Http\Request::create('/business', 'GET');
    
    // Bootstrap the kernel
    $kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
    
    // Handle the request
    $response = $kernel->handle($request);
    
    echo "Response Status: " . $response->getStatusCode() . "\n\n";
    
    if ($response->exception) {
        echo "Exception: " . get_class($response->exception) . "\n";
        echo "Message: " . $response->exception->getMessage() . "\n";
        echo "File: " . $response->exception->getFile() . "\n";
        echo "Line: " . $response->exception->getLine() . "\n\n";
        echo "Stack Trace:\n" . $response->exception->getTraceAsString();
    } else {
        echo "Content:\n" . substr($response->getContent(), 0, 500);
    }
    
} catch (Exception $e) {
    echo "Fatal Error:\n";
    echo "Exception: " . get_class($e) . "\n";
    echo "Message: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n\n";
    echo "Stack Trace:\n" . $e->getTraceAsString();
}