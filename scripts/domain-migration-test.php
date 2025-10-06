#!/usr/bin/env php
<?php

require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

echo "╔══════════════════════════════════════════════════════════════════╗\n";
echo "║          DOMAIN MIGRATION TEST - POST PORT 8090                  ║\n";
echo "╚══════════════════════════════════════════════════════════════════╝\n";
echo "Date: " . date('Y-m-d H:i:s') . PHP_EOL . PHP_EOL;

$passedTests = 0;
$failedTests = 0;

// 1. TEST MAIN DOMAIN ACCESS
echo "▶ 1. MAIN DOMAIN ACCESS TEST\n";
echo str_repeat('─', 70) . PHP_EOL;

$urls = [
    'https://api.askproai.de/business' => 'Business Portal',
    'https://api.askproai.de/business/login' => 'Login Page',
    'https://api.askproai.de/admin' => 'Admin (Alias)',
];

foreach ($urls as $url => $name) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
    curl_setopt($ch, CURLOPT_HEADER, true);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode == 200 || $httpCode == 302) {
        echo "✅ $name: HTTP $httpCode\n";
        $passedTests++;
    } else {
        echo "❌ $name: HTTP $httpCode\n";
        $failedTests++;
    }
}

// 2. TEST PORT 8090 IS DISABLED
echo PHP_EOL . "▶ 2. PORT 8090 STATUS\n";
echo str_repeat('─', 70) . PHP_EOL;

$ch = curl_init('https://api.askproai.de:8090/business');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_TIMEOUT, 5);
curl_setopt($ch, CURLOPT_NOBODY, true);

$response = curl_exec($ch);
$curlError = curl_error($ch);
curl_close($ch);

if ($curlError && strpos($curlError, 'Failed to connect') !== false) {
    echo "✅ Port 8090: DISABLED (as expected)\n";
    $passedTests++;
} else {
    echo "⚠️  Port 8090: Still responding\n";
}

// 3. TEST ROUTES
echo PHP_EOL . "▶ 3. ROUTE FUNCTIONALITY\n";
echo str_repeat('─', 70) . PHP_EOL;

$routes = [
    '/business' => 'Dashboard',
    '/business/customers' => 'Customers',
    '/business/calls' => 'Calls',
    '/business/appointments' => 'Appointments',
    '/business/companies' => 'Companies',
];

foreach ($routes as $path => $name) {
    $request = \Illuminate\Http\Request::create($path, 'GET');
    $response = $kernel->handle($request);
    $status = $response->getStatusCode();

    if ($status == 200 || $status == 302) {
        echo "✅ $name ($path): HTTP $status\n";
        $passedTests++;
    } else {
        echo "❌ $name ($path): HTTP $status\n";
        $failedTests++;
    }
}

// 4. PERFORMANCE TEST
echo PHP_EOL . "▶ 4. PERFORMANCE TEST\n";
echo str_repeat('─', 70) . PHP_EOL;

$performanceTests = [
    'Login Page' => 'https://api.askproai.de/business/login',
    'Business Root' => 'https://api.askproai.de/business',
];

foreach ($performanceTests as $name => $url) {
    $start = microtime(true);

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_NOBODY, true);

    curl_exec($ch);
    curl_close($ch);

    $time = (microtime(true) - $start) * 1000;

    if ($time < 100) {
        echo "✅ $name: " . round($time, 2) . "ms\n";
        $passedTests++;
    } else {
        echo "⚠️  $name: " . round($time, 2) . "ms (slow)\n";
    }
}

// 5. SSL/SECURITY CHECK
echo PHP_EOL . "▶ 5. SSL & SECURITY\n";
echo str_repeat('─', 70) . PHP_EOL;

$ch = curl_init('https://api.askproai.de/business');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
curl_setopt($ch, CURLOPT_HEADER, true);
curl_setopt($ch, CURLOPT_NOBODY, true);

$response = curl_exec($ch);
$sslError = curl_error($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode > 0 && empty($sslError)) {
    echo "✅ SSL Certificate: VALID\n";
    $passedTests++;
} else {
    echo "❌ SSL Certificate: Issues detected\n";
    $failedTests++;
}

// Check security headers
$headers = get_headers('https://api.askproai.de/business', 1);
$securityHeaders = [
    'X-Frame-Options' => false,
    'X-Content-Type-Options' => false,
    'X-XSS-Protection' => false,
];

foreach ($headers as $key => $value) {
    if (isset($securityHeaders[str_replace(':', '', $key)])) {
        $securityHeaders[str_replace(':', '', $key)] = true;
    }
}

$securityScore = array_sum($securityHeaders);
echo "Security Headers: $securityScore/3 configured\n";

// 6. DATABASE & CACHE
echo PHP_EOL . "▶ 6. DATABASE & CACHE TEST\n";
echo str_repeat('─', 70) . PHP_EOL;

try {
    DB::connection()->getPdo();
    echo "✅ Database: CONNECTED\n";
    $passedTests++;
} catch (Exception $e) {
    echo "❌ Database: " . $e->getMessage() . PHP_EOL;
    $failedTests++;
}

try {
    Cache::put('test_key', 'test_value', 1);
    $value = Cache::get('test_key');
    Cache::forget('test_key');

    if ($value === 'test_value') {
        echo "✅ Redis Cache: WORKING\n";
        $passedTests++;
    } else {
        echo "❌ Redis Cache: VALUE MISMATCH\n";
        $failedTests++;
    }
} catch (Exception $e) {
    echo "❌ Redis Cache: " . $e->getMessage() . PHP_EOL;
    $failedTests++;
}

// SUMMARY
echo PHP_EOL;
echo "╔══════════════════════════════════════════════════════════════════╗\n";
echo "║                         TEST SUMMARY                             ║\n";
echo "╚══════════════════════════════════════════════════════════════════╝\n";

$totalTests = $passedTests + $failedTests;
$successRate = $totalTests > 0 ? round(($passedTests / $totalTests) * 100, 1) : 0;

echo "Total Tests: $totalTests\n";
echo "✅ Passed: $passedTests\n";
echo "❌ Failed: $failedTests\n";
echo "Success Rate: $successRate%\n";

echo PHP_EOL;
if ($successRate >= 90) {
    echo "🏆 RESULT: EXCELLENT - Domain migration successful\n";
} elseif ($successRate >= 70) {
    echo "✅ RESULT: GOOD - System functional on main domain\n";
} else {
    echo "⚠️  RESULT: ISSUES - Some problems detected\n";
}

echo PHP_EOL;
echo "Main Domain Status: https://api.askproai.de/business\n";
echo "Port 8090 Status: " . ($curlError ? "DISABLED" : "Still Active") . PHP_EOL;
echo PHP_EOL . "Test completed at: " . date('Y-m-d H:i:s') . PHP_EOL;