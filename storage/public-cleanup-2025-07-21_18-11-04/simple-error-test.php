<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    // Bootstrap Laravel
    require __DIR__.'/../vendor/autoload.php';
    $app = require_once __DIR__.'/../bootstrap/app.php';
    
    echo "Laravel bootstrapped successfully\n";
    
    $kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
    echo "Kernel created successfully\n";
    
    $request = Illuminate\Http\Request::capture();
    echo "Request captured successfully\n";
    
    $response = $kernel->handle($request);
    echo "Request handled successfully\n";
    
    // Test session
    echo "\nSession test:\n";
    echo "Session driver: " . config('session.driver') . "\n";
    echo "APP_KEY exists: " . (config('app.key') ? 'YES' : 'NO') . "\n";
    echo "APP_KEY value: " . substr(config('app.key'), 0, 20) . "...\n";
    
    // Don't try to start session if no key
    if (config('app.key')) {
        echo "Session started: " . (session()->isStarted() ? 'YES' : 'NO') . "\n";
        echo "Session ID: " . session()->getId() . "\n";
    } else {
        echo "Cannot start session - no encryption key\n";
    }
    
    $kernel->terminate($request, $response);
    
} catch (\Exception $e) {
    echo "\nERROR: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
    echo "\nTrace:\n" . $e->getTraceAsString();
}