<?php
// Test script to diagnose admin panel issues
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

try {
    echo "1. Bootstrap successful\n";
    
    // Create request
    $request = Illuminate\Http\Request::create('/admin', 'GET');
    echo "2. Request created\n";
    
    // Handle request
    $response = $kernel->handle($request);
    echo "3. Response status: " . $response->getStatusCode() . "\n";
    
    if ($response->getStatusCode() == 500) {
        echo "4. Error detected - checking logs\n";
        
        // Get last error from log
        $logFile = storage_path('logs/laravel.log');
        if (file_exists($logFile)) {
            $lastLines = shell_exec("tail -50 $logFile | grep -E 'ERROR|Exception' | tail -5");
            echo "Last errors:\n$lastLines\n";
        }
    }
    
    echo "5. Test complete\n";
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
}