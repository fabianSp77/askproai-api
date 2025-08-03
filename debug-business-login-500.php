<?php
// Enable all error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
ini_set('log_errors', 1);

// Load Laravel
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';

// Bootstrap the application
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

// Get CSRF token first
$getRequest = Illuminate\Http\Request::create('/business/login', 'GET');
$getResponse = $kernel->handle($getRequest);
$csrfToken = null;
if (preg_match('/name="_token" value="([^"]+)"/', $getResponse->getContent(), $matches)) {
    $csrfToken = $matches[1];
}

echo "=== Business Portal Login Debug ===\n";
echo "CSRF Token: " . substr($csrfToken ?? 'NOT FOUND', 0, 20) . "...\n\n";

// Create a POST request
$request = Illuminate\Http\Request::create(
    '/business/login',
    'POST',
    [
        '_token' => $csrfToken,
        'email' => 'demo@askproai.de',
        'password' => 'password123'
    ],
    [], // cookies
    [], // files
    [
        'HTTP_ACCEPT' => 'text/html,application/xhtml+xml',
        'CONTENT_TYPE' => 'application/x-www-form-urlencoded',
    ]
);

// Set the session from the GET request
$session = $getRequest->getSession();
if ($session) {
    $request->setLaravelSession($session);
    echo "Session ID: " . $session->getId() . "\n";
}

echo "\nAttempting login...\n";

try {
    // Handle the request
    $response = $kernel->handle($request);
    
    echo "\nResponse Status: " . $response->getStatusCode() . "\n";
    echo "Response Headers:\n";
    foreach ($response->headers->all() as $key => $values) {
        echo "  $key: " . implode(', ', $values) . "\n";
    }
    
    if ($response->getStatusCode() >= 400) {
        echo "\nError Response Content:\n";
        $content = $response->getContent();
        
        // Try to extract error message from HTML
        if (preg_match('/<title>([^<]+)<\/title>/', $content, $matches)) {
            echo "Page Title: " . $matches[1] . "\n";
        }
        
        if (preg_match('/class="title">([^<]+)</', $content, $matches)) {
            echo "Error Title: " . $matches[1] . "\n";
        }
        
        if (preg_match('/class="message[^"]*">([^<]+)</', $content, $matches)) {
            echo "Error Message: " . $matches[1] . "\n";
        }
        
        // Show first 1000 chars of content if no specific error found
        if ($response->getStatusCode() == 500) {
            echo "\nFirst 1000 chars of response:\n";
            echo substr(strip_tags($content), 0, 1000) . "\n";
        }
    }
    
    // Check Laravel logs
    $logFile = storage_path('logs/laravel.log');
    if (file_exists($logFile)) {
        echo "\n=== Recent Laravel Log Entries ===\n";
        $lines = file($logFile);
        $recentLines = array_slice($lines, -10);
        foreach ($recentLines as $line) {
            if (strpos($line, 'ERROR') !== false || strpos($line, 'Exception') !== false) {
                echo $line;
            }
        }
    }
    
} catch (\Exception $e) {
    echo "\nException caught: " . get_class($e) . "\n";
    echo "Message: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "\nStack trace:\n";
    echo $e->getTraceAsString() . "\n";
} catch (\Error $e) {
    echo "\nError caught: " . get_class($e) . "\n";
    echo "Message: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "\nStack trace:\n";
    echo $e->getTraceAsString() . "\n";
}

$kernel->terminate($request, $response ?? null);