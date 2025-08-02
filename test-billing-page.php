<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';

// Enable error reporting
ini_set('display_errors', 1);
error_reporting(E_ALL);

try {
    // Create a request
    $request = \Illuminate\Http\Request::create('/business/billing', 'GET');
    
    // Get the kernel
    $kernel = $app->make(\Illuminate\Contracts\Http\Kernel::class);
    
    // Handle the request
    $response = $kernel->handle($request);
    
    echo "Status: " . $response->getStatusCode() . "\n";
    
    if ($response->getStatusCode() === 500) {
        echo "\nError Content:\n";
        $content = $response->getContent();
        
        // Try to extract error message from HTML
        if (preg_match('/<div class="title">(.*?)<\/div>/s', $content, $matches)) {
            echo "Error: " . strip_tags($matches[1]) . "\n";
        }
        
        // Look for exception message
        if (preg_match('/<div class="message">(.*?)<\/div>/s', $content, $matches)) {
            echo "Message: " . strip_tags($matches[1]) . "\n";
        }
        
        // Save full response for debugging
        file_put_contents('billing-error.html', $content);
        echo "\nFull error saved to billing-error.html\n";
    }
    
} catch (Exception $e) {
    echo "Exception: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "Trace:\n" . $e->getTraceAsString() . "\n";
}