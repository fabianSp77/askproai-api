<?php
// Test Admin Panel without Authentication
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

echo "=== Testing Admin Panel Access Without Authentication ===\n\n";

try {
    // Create a fresh request to /admin without any authentication
    $adminRequest = \Illuminate\Http\Request::create('/admin', 'GET');
    
    // Handle the request
    $adminResponse = $kernel->handle($adminRequest);
    
    echo "Response Status: " . $adminResponse->getStatusCode() . "\n";
    
    if ($adminResponse->getStatusCode() === 302) {
        echo "Redirect Location: " . $adminResponse->headers->get('Location') . "\n";
        
        // Follow the redirect
        $redirectUrl = $adminResponse->headers->get('Location');
        if ($redirectUrl) {
            echo "\nFollowing redirect to: $redirectUrl\n";
            
            // Parse the URL to get the path
            $parts = parse_url($redirectUrl);
            $path = $parts['path'] ?? '/';
            
            // Create new request for redirect
            $redirectRequest = \Illuminate\Http\Request::create($path, 'GET');
            $redirectResponse = $kernel->handle($redirectRequest);
            
            echo "Redirect Response Status: " . $redirectResponse->getStatusCode() . "\n";
            
            if ($redirectResponse->getStatusCode() === 500) {
                // Extract error
                $content = $redirectResponse->getContent();
                
                // Look for specific error patterns
                if (preg_match('/<title>(.*?)<\/title>/si', $content, $matches)) {
                    echo "Page Title: " . strip_tags($matches[1]) . "\n";
                }
                
                if (preg_match('/<div[^>]*class="[^"]*text-2xl[^"]*"[^>]*>(.*?)<\/div>/si', $content, $matches)) {
                    echo "Error: " . strip_tags($matches[1]) . "\n";
                }
                
                // Save error page
                file_put_contents('/tmp/admin-redirect-error.html', $content);
                echo "\nFull error saved to: /tmp/admin-redirect-error.html\n";
                
                // Extract stack trace if available
                if (preg_match('/<div[^>]*class="[^"]*trace[^"]*"[^>]*>(.*?)<\/div>/si', $content, $matches)) {
                    $trace = strip_tags($matches[1]);
                    echo "\nStack trace preview:\n" . substr($trace, 0, 500) . "...\n";
                }
            }
        }
    } elseif ($adminResponse->getStatusCode() === 500) {
        echo "Direct 500 error on /admin\n";
        
        $content = $adminResponse->getContent();
        file_put_contents('/tmp/admin-direct-error.html', $content);
        echo "Error saved to: /tmp/admin-direct-error.html\n";
    }
    
} catch (Exception $e) {
    echo "Exception: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "\nStack Trace:\n";
    echo $e->getTraceAsString() . "\n";
}

echo "\n=== Test Complete ===\n";