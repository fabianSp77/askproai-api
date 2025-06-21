<?php
// Test Laravel bootstrap
ini_set('display_errors', 1);
error_reporting(E_ALL);

require __DIR__.'/../vendor/autoload.php';

try {
    $app = require_once __DIR__.'/../bootstrap/app.php';
    
    // Load environment
    $app->loadEnvironmentFrom('../.env');
    
    // Test key components
    echo "Environment loaded: " . $app->environment() . "\n";
    echo "App Key: " . (config('app.key') ? 'SET' : 'NOT SET') . "\n";
    echo "Cipher: " . config('app.cipher') . "\n";
    
    // Test encryption
    try {
        $encrypter = app('encrypter');
        echo "Encryption: WORKING\n";
    } catch (\Exception $e) {
        echo "Encryption Error: " . $e->getMessage() . "\n";
    }
    
    // Test route loading
    try {
        $kernel = $app->make(\Illuminate\Contracts\Http\Kernel::class);
        echo "HTTP Kernel: LOADED\n";
    } catch (\Exception $e) {
        echo "Kernel Error: " . $e->getMessage() . "\n";
    }
    
} catch (\Exception $e) {
    echo "Bootstrap Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}