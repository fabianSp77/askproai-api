<?php

/**
 * Emergency Login Test
 * 
 * This script tests the login functionality directly
 * without middleware complications.
 */

require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

// Set up environment
$request = Illuminate\Http\Request::capture();
$response = $kernel->handle($request);

echo "🔍 Testing Login Functionality\n";
echo "==============================\n\n";

// Test 1: Admin Login URL
echo "1. Testing Admin Login URL:\n";
try {
    $ch = curl_init('https://api.askproai.de/admin/login');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, true);
    curl_setopt($ch, CURLOPT_NOBODY, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    echo "   - HTTP Status: $httpCode " . ($httpCode == 200 ? '✅' : '❌') . "\n";
} catch (Exception $e) {
    echo "   - Error: " . $e->getMessage() . " ❌\n";
}

// Test 2: Business Login URL
echo "\n2. Testing Business Login URL:\n";
try {
    $ch = curl_init('https://api.askproai.de/business/login');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, true);
    curl_setopt($ch, CURLOPT_NOBODY, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    echo "   - HTTP Status: $httpCode " . ($httpCode == 200 ? '✅' : '❌') . "\n";
} catch (Exception $e) {
    echo "   - Error: " . $e->getMessage() . " ❌\n";
}

// Test 3: Check if routes exist
echo "\n3. Checking Routes:\n";
$adminLoginRoute = Route::has('filament.admin.auth.login');
$businessLoginRoute = Route::has('business.login');

echo "   - Admin login route exists: " . ($adminLoginRoute ? 'Yes ✅' : 'No ❌') . "\n";
echo "   - Business login route exists: " . ($businessLoginRoute ? 'Yes ✅' : 'No ❌') . "\n";

// Test 4: Session configuration
echo "\n4. Session Configuration:\n";
echo "   - Driver: " . config('session.driver') . "\n";
echo "   - Cookie: " . config('session.cookie') . "\n";
echo "   - Secure: " . (config('session.secure') ? 'true ✅' : 'false ❌') . "\n";
echo "   - Domain: " . (config('session.domain') ?: 'null') . "\n";

// Test 5: Check for errors in storage
echo "\n5. Recent Errors:\n";
$logFile = storage_path('logs/laravel.log');
if (file_exists($logFile)) {
    $lines = file($logFile);
    $recentErrors = array_slice($lines, -10);
    $errorCount = 0;
    foreach ($recentErrors as $line) {
        if (stripos($line, 'ERROR') !== false || stripos($line, 'CRITICAL') !== false) {
            echo "   - " . trim($line) . "\n";
            $errorCount++;
        }
    }
    if ($errorCount == 0) {
        echo "   - No recent errors found ✅\n";
    }
} else {
    echo "   - Log file not found\n";
}

echo "\n🎯 Summary:\n";
echo "   - The issue appears to be with middleware or routing\n";
echo "   - Try accessing: https://api.askproai.de/admin\n";
echo "   - Or: https://api.askproai.de/business\n";
echo "\n✅ Test completed!\n";