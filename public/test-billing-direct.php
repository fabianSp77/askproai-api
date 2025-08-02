<?php

// Direct test of billing page bypassing Laravel bootstrap issues

require_once __DIR__ . '/../vendor/autoload.php';

// Set up Laravel
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

// Boot the application
$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

// Test the controller directly
try {
    $controller = new \App\Http\Controllers\Portal\SimpleBillingController();
    
    // Create a mock request
    $request = new \Illuminate\Http\Request();
    
    // Call the index method
    $result = $controller->index($request);
    
    if ($result instanceof \Illuminate\View\View) {
        echo "<h1>✅ Controller works!</h1>";
        echo "<p>View name: " . $result->getName() . "</p>";
        echo "<p>View data:</p>";
        echo "<pre>" . print_r($result->getData(), true) . "</pre>";
    } else {
        echo "<h1>Controller returned: " . get_class($result) . "</h1>";
    }
    
} catch (Exception $e) {
    echo "<h1>❌ Error in controller</h1>";
    echo "<p><strong>Message:</strong> " . $e->getMessage() . "</p>";
    echo "<p><strong>File:</strong> " . $e->getFile() . ":" . $e->getLine() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}