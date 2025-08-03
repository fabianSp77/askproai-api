<?php
// Debug the exact 500 error
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Create a temporary log handler
$errorLog = [];
set_error_handler(function($errno, $errstr, $errfile, $errline) use (&$errorLog) {
    $errorLog[] = [
        'type' => 'error',
        'errno' => $errno,
        'message' => $errstr,
        'file' => $errfile,
        'line' => $errline
    ];
});

set_exception_handler(function($e) use (&$errorLog) {
    $errorLog[] = [
        'type' => 'exception',
        'class' => get_class($e),
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => $e->getTraceAsString()
    ];
});

require __DIR__.'/vendor/autoload.php';

try {
    $app = require_once __DIR__.'/bootstrap/app.php';
    $kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
    
    // Create login request
    $request = Illuminate\Http\Request::create(
        '/business/login',
        'POST',
        [
            '_token' => 'test',
            'email' => 'demo@askproai.de',
            'password' => 'password123'
        ],
        [], // cookies
        [], // files
        [
            'HTTP_ACCEPT' => 'text/html',
            'CONTENT_TYPE' => 'application/x-www-form-urlencoded',
        ]
    );
    
    // Handle the request
    $response = $kernel->handle($request);
    
    echo "Response Status: " . $response->getStatusCode() . "\n";
    
    if ($response->getStatusCode() == 500) {
        $content = $response->getContent();
        
        // Try to extract error from Ignition/Flare error page
        if (preg_match('/<div class="text-2xl"[^>]*>([^<]+)</', $content, $matches)) {
            echo "Error: " . $matches[1] . "\n";
        }
        
        if (preg_match('/<span class="font-mono"[^>]*>([^<]+)</', $content, $matches)) {
            echo "Exception: " . $matches[1] . "\n";
        }
        
        // Look for stack trace
        if (preg_match_all('/<div class="text-sm"[^>]*>([^<]+\.php):(\d+)</', $content, $matches)) {
            echo "\nStack trace:\n";
            for ($i = 0; $i < min(5, count($matches[0])); $i++) {
                echo "  " . $matches[1][$i] . ":" . $matches[2][$i] . "\n";
            }
        }
    }
    
    $kernel->terminate($request, $response);
    
} catch (Throwable $e) {
    echo "Caught exception: " . get_class($e) . "\n";
    echo "Message: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
}

// Show any errors collected
if (!empty($errorLog)) {
    echo "\n=== Errors/Exceptions Collected ===\n";
    foreach ($errorLog as $error) {
        echo $error['type'] . ": " . ($error['message'] ?? $error['class']) . "\n";
        echo "  at " . $error['file'] . ":" . $error['line'] . "\n";
        if (isset($error['trace'])) {
            echo "  Trace:\n";
            $lines = explode("\n", $error['trace']);
            foreach (array_slice($lines, 0, 5) as $line) {
                echo "    " . $line . "\n";
            }
        }
    }
}