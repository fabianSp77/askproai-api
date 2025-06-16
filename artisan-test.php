<?php
// Test Laravel bootstrap
ini_set('memory_limit', '512M');
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "Loading Laravel...\n";

try {
    require __DIR__.'/vendor/autoload.php';
    echo "Autoload OK\n";
    
    $app = require_once __DIR__.'/bootstrap/app.php';
    echo "Bootstrap OK\n";
    
    $kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
    echo "Kernel OK\n";
    
    $kernel->bootstrap();
    echo "Bootstrap complete!\n";
    
} catch (\Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
    echo "\nTrace:\n" . $e->getTraceAsString() . "\n";
}