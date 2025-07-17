<?php
// Test what's happening
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>System Test</h1>";
echo "<pre>";

// 1. Check if autoloader works
echo "1. Checking autoloader...\n";
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
    echo "✓ Autoloader loaded\n\n";
} else {
    die("✗ Autoloader not found!\n");
}

// 2. Check if Laravel can bootstrap
echo "2. Bootstrapping Laravel...\n";
try {
    $app = require_once __DIR__ . '/../bootstrap/app.php';
    echo "✓ Laravel app created\n\n";
} catch (Exception $e) {
    echo "✗ Error creating app: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
    die();
}

// 3. Check if TenantScope exists
echo "3. Checking TenantScope class...\n";
$tenantScopePath = __DIR__ . '/../app/Scopes/TenantScope.php';
if (file_exists($tenantScopePath)) {
    echo "✓ TenantScope.php exists\n";
    
    // Check class name
    $content = file_get_contents($tenantScopePath);
    if (strpos($content, 'class TenantScope') !== false) {
        echo "✓ Class name is correct\n";
    } else {
        echo "✗ Class name is wrong in file\n";
    }
    
    // Try to load it
    try {
        if (class_exists('App\Scopes\TenantScope')) {
            echo "✓ TenantScope class can be loaded\n";
        } else {
            echo "✗ TenantScope class cannot be loaded\n";
        }
    } catch (Exception $e) {
        echo "✗ Error loading TenantScope: " . $e->getMessage() . "\n";
    }
} else {
    echo "✗ TenantScope.php does not exist!\n";
}

echo "\n4. Checking model that uses TenantScope...\n";
$phoneNumberPath = __DIR__ . '/../app/Models/PhoneNumber.php';
if (file_exists($phoneNumberPath)) {
    $content = file_get_contents($phoneNumberPath);
    echo "PhoneNumber.php content (first 1000 chars):\n";
    echo htmlspecialchars(substr($content, 0, 1000));
}

echo "</pre>";