<?php
/**
 * Final API Debug - Get the exact error
 */

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$request = Illuminate\Http\Request::capture();
$response = $kernel->handle($request);
$kernel->terminate($request, $response);

echo "=== FINAL API DEBUG ===\n\n";

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// 1. Test the exact same request as the test suite
echo "1. Making the exact same request as test suite...\n";

$request = \Illuminate\Http\Request::create('/api/v2/portal/auth/login', 'POST', [
    'email' => 'portal-test@askproai.de',
    'password' => 'test123'
]);

$request->headers->set('Accept', 'application/json');
$request->headers->set('Content-Type', 'application/json');

// Capture any output
ob_start();
$errorOutput = '';

try {
    // Get fresh kernel instance
    $app = require __DIR__.'/../bootstrap/app.php';
    $kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
    
    $response = $kernel->handle($request);
    $statusCode = $response->getStatusCode();
    $content = $response->getContent();
    
    echo "Status Code: {$statusCode}\n";
    
    if ($statusCode === 500) {
        echo "\n=== 500 ERROR DETAILS ===\n";
        
        // Try to decode JSON error
        $json = json_decode($content, true);
        if ($json) {
            echo "Error Message: " . ($json['message'] ?? 'No message') . "\n";
            if (isset($json['exception'])) {
                echo "Exception: " . $json['exception'] . "\n";
            }
            if (isset($json['file'])) {
                echo "File: " . $json['file'] . "\n";
                echo "Line: " . $json['line'] . "\n";
            }
            if (isset($json['trace']) && is_array($json['trace'])) {
                echo "\nStack Trace (first 5):\n";
                foreach (array_slice($json['trace'], 0, 5) as $i => $trace) {
                    echo "{$i}: " . ($trace['file'] ?? 'unknown') . ":" . ($trace['line'] ?? '?') . " - " . ($trace['function'] ?? 'unknown') . "\n";
                }
            }
        } else {
            echo "Raw Response:\n" . substr($content, 0, 1000) . "\n";
        }
    } else {
        echo "Success! Response:\n";
        $json = json_decode($content, true);
        echo json_encode($json, JSON_PRETTY_PRINT) . "\n";
    }
    
} catch (\Throwable $e) {
    echo "\n=== EXCEPTION CAUGHT ===\n";
    echo "Type: " . get_class($e) . "\n";
    echo "Message: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
    echo "\nTrace:\n" . substr($e->getTraceAsString(), 0, 1000) . "\n";
}

$errorOutput = ob_get_clean();
if ($errorOutput) {
    echo "\n=== CAPTURED OUTPUT ===\n";
    echo $errorOutput;
}

// 2. Check recent log entries
echo "\n\n2. Recent log entries...\n";
$logFile = storage_path('logs/laravel.log');
if (file_exists($logFile)) {
    $lines = file($logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $recentLines = array_slice($lines, -20);
    
    foreach ($recentLines as $line) {
        if (strpos($line, 'ERROR') !== false || strpos($line, 'portal') !== false) {
            echo substr($line, 0, 200) . "\n";
        }
    }
}

// 3. Double-check middleware
echo "\n3. Checking API route middleware...\n";
$router = app('router');
$routes = $router->getRoutes();

foreach ($routes as $route) {
    if ($route->uri() === 'api/v2/portal/auth/login') {
        echo "Route found with middleware: " . implode(', ', $route->middleware()) . "\n";
        break;
    }
}

echo "\n=== DEBUG COMPLETE ===\n";