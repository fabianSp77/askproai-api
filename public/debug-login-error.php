<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

require __DIR__.'/../vendor/autoload.php';

$app = require_once __DIR__.'/../bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

// Create a mock login request
$request = Illuminate\Http\Request::create(
    '/business/login',
    'POST',
    [
        '_token' => 'test-token',
        'email' => 'demo@askproai.de',
        'password' => 'password123'
    ]
);

// Set up session
$request->setLaravelSession($app['session.store']);

try {
    $response = $kernel->handle($request);
    
    echo "Response Status: " . $response->getStatusCode() . "\n";
    
    if ($response->getStatusCode() >= 400) {
        echo "Response Content:\n";
        echo $response->getContent();
    }
    
    if ($response->getStatusCode() == 302) {
        echo "Redirect to: " . $response->headers->get('Location') . "\n";
    }
} catch (\Exception $e) {
    echo "Exception: " . get_class($e) . "\n";
    echo "Message: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "\nStack trace:\n" . $e->getTraceAsString() . "\n";
}

$kernel->terminate($request, $response);