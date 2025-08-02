<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';

// Enable error reporting
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Boot the application
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

// Test with cookies from a real session
$cookies = [
    'XSRF-TOKEN' => 'test',
    'askproai_portal_session' => 'test'
];

// Create request with session cookies
$request = \Illuminate\Http\Request::create(
    'https://api.askproai.de/business/billing',
    'GET',
    [],
    $cookies,
    [],
    [
        'HTTP_HOST' => 'api.askproai.de',
        'HTTPS' => 'on',
        'HTTP_ACCEPT' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
        'HTTP_USER_AGENT' => 'Mozilla/5.0 (compatible; Test)',
    ]
);

try {
    $response = $kernel->handle($request);
    
    echo "Status Code: " . $response->getStatusCode() . "\n";
    
    if ($response->getStatusCode() === 500) {
        $content = $response->getContent();
        
        // Save the error page
        file_put_contents('billing-500-error.html', $content);
        echo "Error page saved to billing-500-error.html\n";
        
        // Try to extract error details
        if (strpos($content, 'Whoops') !== false) {
            echo "Laravel error page detected\n";
            
            // Extract exception message
            if (preg_match('/<div class="exception-message"[^>]*>(.*?)<\/div>/s', $content, $matches)) {
                echo "Exception: " . strip_tags(trim($matches[1])) . "\n";
            }
            
            // Extract file and line
            if (preg_match('/<span class="exception-file"[^>]*>(.*?)<\/span>/s', $content, $matches)) {
                echo "File: " . strip_tags(trim($matches[1])) . "\n";
            }
        } else {
            // Check for generic error message
            if (preg_match('/<title>(.*?)<\/title>/i', $content, $matches)) {
                echo "Title: " . $matches[1] . "\n";
            }
            
            if (preg_match('/<h1[^>]*>(.*?)<\/h1>/i', $content, $matches)) {
                echo "H1: " . strip_tags($matches[1]) . "\n";
            }
        }
    } elseif ($response->getStatusCode() === 302) {
        echo "Redirecting to: " . $response->headers->get('Location') . "\n";
    }
    
    // Terminate the kernel
    $kernel->terminate($request, $response);
    
} catch (Exception $e) {
    echo "Exception caught: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "\nStack trace:\n" . $e->getTraceAsString() . "\n";
}