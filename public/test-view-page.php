<?php

// Direct test of View page functionality

require_once __DIR__ . '/../vendor/autoload.php';

use App\Models\User;
use App\Models\PhoneNumber;
use Illuminate\Support\Facades\Auth;

// Bootstrap Laravel
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

// Create a request for the phone number view page
$request = Illuminate\Http\Request::create(
    '/admin/phone-numbers/03513893-d962-4db0-858c-ea5b0e227e9a',
    'GET'
);

// Login admin user
$admin = User::where('email', 'admin@askproai.de')->first();
if ($admin) {
    Auth::login($admin);
    echo "Logged in as: " . $admin->email . "\n\n";
}

try {
    // Handle the request
    $response = $kernel->handle($request);
    
    echo "Response Status: " . $response->getStatusCode() . "\n";
    
    if ($response->getStatusCode() === 500) {
        echo "\n500 Error Details:\n";
        echo "==================\n";
        
        // Get the error from the response
        $content = $response->getContent();
        
        // Look for error message in the content
        if (strpos($content, 'exception') !== false || strpos($content, 'error') !== false) {
            // Try to extract error message
            preg_match('/<title>(.*?)<\/title>/s', $content, $matches);
            if (isset($matches[1])) {
                echo "Page Title: " . $matches[1] . "\n";
            }
            
            // Look for specific error text
            if (strpos($content, 'Server Error') !== false) {
                echo "Server Error detected\n";
            }
            
            // Check for debug mode error display
            if (strpos($content, 'message') !== false) {
                preg_match('/"message":"(.*?)"/s', $content, $matches);
                if (isset($matches[1])) {
                    echo "Error Message: " . $matches[1] . "\n";
                }
            }
        }
        
        // Save full response for debugging
        file_put_contents('/tmp/error-response.html', $content);
        echo "\nFull error response saved to /tmp/error-response.html\n";
    } else {
        echo "Page loaded successfully!\n";
    }
    
} catch (Exception $e) {
    echo "Exception caught: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
    echo "\nStack trace:\n";
    echo $e->getTraceAsString();
}

// Terminate the kernel
$kernel->terminate($request, $response ?? null);