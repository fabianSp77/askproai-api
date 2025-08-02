<?php
// Debug Admin 500 Error
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Custom error handler to catch the exact error
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    echo "ERROR: $errstr\n";
    echo "FILE: $errfile:$errline\n";
    echo "ERRNO: $errno\n\n";
    debug_print_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 5);
    return true;
});

// Exception handler
set_exception_handler(function($exception) {
    echo "EXCEPTION: " . $exception->getMessage() . "\n";
    echo "FILE: " . $exception->getFile() . ":" . $exception->getLine() . "\n";
    echo "TRACE:\n" . $exception->getTraceAsString() . "\n";
});

try {
    require_once __DIR__ . '/../vendor/autoload.php';
    $app = require_once __DIR__ . '/../bootstrap/app.php';
    
    echo "✓ Application bootstrapped\n";
    
    $kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
    
    echo "✓ Kernel created\n";
    
    // Create request to /admin
    $request = Illuminate\Http\Request::create('/admin', 'GET');
    
    echo "✓ Request created\n";
    
    // Handle the request
    echo "Handling request...\n";
    $response = $kernel->handle($request);
    
    echo "✓ Request handled\n";
    echo "Response status: " . $response->getStatusCode() . "\n";
    
    if ($response->getStatusCode() === 500) {
        // Try to extract error from response
        $content = $response->getContent();
        
        // Save to file
        file_put_contents('/tmp/admin-500-debug.html', $content);
        echo "Error response saved to /tmp/admin-500-debug.html\n";
        
        // Try to find error in Whoops output
        if (preg_match('/<h1[^>]*class="[^"]*exception-message[^"]*"[^>]*>(.*?)<\/h1>/si', $content, $matches)) {
            echo "\nError Message: " . strip_tags($matches[1]) . "\n";
        }
        
        // Try to find file/line
        if (preg_match('/<span[^>]*class="[^"]*exception-file[^"]*"[^>]*>(.*?)<\/span>/si', $content, $matches)) {
            echo "Error Location: " . strip_tags($matches[1]) . "\n";
        }
    }
    
} catch (Throwable $e) {
    echo "\nCAUGHT EXCEPTION:\n";
    echo "Message: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "Trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\n=== Debug Complete ===\n";