<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Boot Debug</h1><pre>";

// Step 1: Basic PHP
echo "1. PHP Version: " . PHP_VERSION . "\n";
echo "2. Working Directory: " . getcwd() . "\n\n";

// Step 2: Autoloader
echo "3. Loading autoloader...\n";
require __DIR__.'/../vendor/autoload.php';
echo "   ✓ Autoloader loaded\n\n";

// Step 3: Check if we can even create the app
echo "4. Creating Laravel app...\n";
try {
    $app = require_once __DIR__.'/../bootstrap/app.php';
    echo "   ✓ App created\n\n";
} catch (\Throwable $e) {
    echo "   ✗ FAILED at bootstrap/app.php\n";
    echo "   Error: " . $e->getMessage() . "\n";
    echo "   File: " . $e->getFile() . "\n";
    echo "   Line: " . $e->getLine() . "\n";
    echo "   Trace:\n";
    echo $e->getTraceAsString();
    die();
}

// Step 4: Create kernel
echo "5. Creating HTTP Kernel...\n";
try {
    $kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
    echo "   ✓ Kernel created\n\n";
} catch (\Throwable $e) {
    echo "   ✗ FAILED creating kernel\n";
    echo "   Error: " . $e->getMessage() . "\n";
    echo "   File: " . $e->getFile() . "\n";
    echo "   Line: " . $e->getLine() . "\n";
    die();
}

// Step 5: Create request
echo "6. Creating request...\n";
try {
    $request = Illuminate\Http\Request::capture();
    echo "   ✓ Request created\n\n";
} catch (\Throwable $e) {
    echo "   ✗ FAILED creating request\n";
    echo "   Error: " . $e->getMessage() . "\n";
    die();
}

// Step 6: Bootstrap the app (this is where providers load)
echo "7. Bootstrapping application (loading providers)...\n";
try {
    $kernel->bootstrap();
    echo "   ✓ Application bootstrapped\n\n";
} catch (\Throwable $e) {
    echo "   ✗ FAILED during bootstrap\n";
    echo "   Error: " . $e->getMessage() . "\n";
    echo "   File: " . $e->getFile() . "\n";
    echo "   Line: " . $e->getLine() . "\n";
    echo "   \nStack trace:\n";
    $trace = $e->getTrace();
    foreach ($trace as $i => $frame) {
        if ($i > 10) break; // Limit trace
        echo "   #{$i} ";
        if (isset($frame['file'])) {
            echo $frame['file'] . ":" . ($frame['line'] ?? '?');
        }
        if (isset($frame['function'])) {
            echo " - " . $frame['function'] . "()";
        }
        echo "\n";
    }
    die();
}

echo "8. SUCCESS - System can boot!\n";
echo "</pre>";
?>