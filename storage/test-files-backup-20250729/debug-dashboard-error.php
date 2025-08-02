<?php
// Temporarily enable debug mode to see the error
$_SERVER['APP_DEBUG'] = 'true';
$_SERVER['APP_ENV'] = 'local';

require __DIR__ . '/../vendor/autoload.php';

// Bootstrap Laravel
$app = require_once __DIR__ . '/../bootstrap/app.php';

// Enable debug mode
config(['app.debug' => true]);
config(['app.env' => 'local']);

$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

// Create request to optimized dashboard
$request = Illuminate\Http\Request::create('/admin/optimized-dashboard', 'GET');

// Copy cookies from current request
foreach ($_COOKIE as $key => $value) {
    $request->cookies->set($key, $value);
}

// Copy session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
foreach ($_SESSION as $key => $value) {
    $request->session()->put($key, $value);
}

// Set the request in the container
app()->instance('request', $request);

try {
    $response = $kernel->handle($request);
    
    // If it's a 500 error, show the content
    if ($response->getStatusCode() === 500) {
        echo $response->getContent();
    } else {
        // Redirect to the actual page
        header('Location: /admin/optimized-dashboard');
    }
} catch (Exception $e) {
    echo '<pre>';
    echo 'Exception: ' . $e->getMessage() . "\n";
    echo 'File: ' . $e->getFile() . "\n";
    echo 'Line: ' . $e->getLine() . "\n";
    echo "\nStack Trace:\n";
    echo $e->getTraceAsString();
    echo '</pre>';
}

$kernel->terminate($request, $response);