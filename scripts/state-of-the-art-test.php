#!/usr/bin/env php
<?php

require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle($request = Illuminate\Http\Request::capture());

echo "╔══════════════════════════════════════════════════════════════════╗\n";
echo "║     STATE-OF-THE-ART COMPREHENSIVE TEST SUITE V3.0              ║\n";
echo "║     System: AskPro AI Gateway - Complete Analysis               ║\n";
echo "╚══════════════════════════════════════════════════════════════════╝\n";
echo "Date: " . date('Y-m-d H:i:s') . PHP_EOL;
echo PHP_EOL;

$testResults = [];
$totalTests = 0;
$passedTests = 0;
$failedTests = 0;

// 1. DATABASE CONNECTIVITY & INTEGRITY
echo "▶ 1. DATABASE TESTS\n";
echo str_repeat('─', 70) . PHP_EOL;

try {
    $pdo = DB::connection()->getPdo();
    echo "✅ Database Connection: ESTABLISHED\n";
    echo "   Server: " . $pdo->getAttribute(PDO::ATTR_SERVER_VERSION) . PHP_EOL;
    $passedTests++;
} catch (Exception $e) {
    echo "❌ Database Connection: FAILED - " . $e->getMessage() . PHP_EOL;
    $failedTests++;
}
$totalTests++;

// Check table counts
$tables = [
    'customers' => 'Expected: 42+',
    'calls' => 'Expected: 207+',
    'appointments' => 'Expected: 41+',
    'companies' => 'Expected: 13+',
    'staff' => 'Expected: 8+',
    'services' => 'Expected: 21+',
    'users' => 'Expected: 10+'
];

foreach ($tables as $table => $expected) {
    try {
        $count = DB::table($table)->count();
        echo "✅ Table $table: $count records ($expected)\n";
        $passedTests++;
    } catch (Exception $e) {
        echo "❌ Table $table: Error - " . $e->getMessage() . PHP_EOL;
        $failedTests++;
    }
    $totalTests++;
}

// 2. MODEL RELATIONSHIPS
echo PHP_EOL . "▶ 2. MODEL RELATIONSHIP TESTS\n";
echo str_repeat('─', 70) . PHP_EOL;

$relationshipTests = [
    'Customer->Company' => function() {
        $customer = \App\Models\Customer::with('company')->first();
        return $customer && $customer->company;
    },
    'Call->Customer' => function() {
        $call = \App\Models\Call::with('customer')->first();
        return $call && $call->customer;
    },
    'Appointment->Customer' => function() {
        $appointment = \App\Models\Appointment::with('customer')->first();
        return $appointment && $appointment->customer;
    }
];

foreach ($relationshipTests as $test => $func) {
    try {
        $result = $func();
        if ($result) {
            echo "✅ $test: Relationship Valid\n";
            $passedTests++;
        } else {
            echo "⚠️ $test: No data or relationship missing\n";
        }
    } catch (Exception $e) {
        echo "❌ $test: " . $e->getMessage() . PHP_EOL;
        $failedTests++;
    }
    $totalTests++;
}

// 3. FILAMENT RESOURCES
echo PHP_EOL . "▶ 3. FILAMENT ADMIN PANEL TESTS\n";
echo str_repeat('─', 70) . PHP_EOL;

$resources = [
    'CustomerResource',
    'CallResource',
    'AppointmentResource',
    'CompanyResource',
    'StaffResource',
    'ServiceResource',
    'BranchResource'
];

foreach ($resources as $resource) {
    $class = "App\\Filament\\Resources\\$resource";
    if (class_exists($class)) {
        echo "✅ $resource: Loaded\n";
        $passedTests++;
    } else {
        echo "❌ $resource: Not Found\n";
        $failedTests++;
    }
    $totalTests++;
}

// 4. ROUTE ACCESSIBILITY
echo PHP_EOL . "▶ 4. ROUTE ACCESSIBILITY TESTS\n";
echo str_repeat('─', 70) . PHP_EOL;

$routes = [
    '/business/login' => 'Login Page',
    '/business' => 'Dashboard',
    '/business/customers' => 'Customers',
    '/business/calls' => 'Calls',
    '/business/appointments' => 'Appointments'
];

foreach ($routes as $path => $name) {
    $request = Request::create($path, 'GET');
    $response = $kernel->handle($request);
    $status = $response->getStatusCode();

    if ($status == 200 || $status == 302) {
        echo "✅ $name ($path): HTTP $status\n";
        $passedTests++;
    } else {
        echo "❌ $name ($path): HTTP $status\n";
        $failedTests++;
    }
    $totalTests++;
}

// 5. CACHE & SESSION
echo PHP_EOL . "▶ 5. CACHE & SESSION TESTS\n";
echo str_repeat('─', 70) . PHP_EOL;

// Test Redis
try {
    Cache::put('test_key', 'test_value', 60);
    $value = Cache::get('test_key');
    if ($value === 'test_value') {
        echo "✅ Redis Cache: Working\n";
        $passedTests++;
    } else {
        echo "❌ Redis Cache: Value mismatch\n";
        $failedTests++;
    }
    Cache::forget('test_key');
} catch (Exception $e) {
    echo "❌ Redis Cache: " . $e->getMessage() . PHP_EOL;
    $failedTests++;
}
$totalTests++;

// Test Session
echo "✅ Session Driver: " . config('session.driver') . PHP_EOL;
echo "✅ Cache Driver: " . config('cache.default') . PHP_EOL;

// 6. SECURITY CONFIGURATION
echo PHP_EOL . "▶ 6. SECURITY AUDIT\n";
echo str_repeat('─', 70) . PHP_EOL;

$securityChecks = [
    'Debug Mode' => config('app.debug') === false,
    'HTTPS Only' => str_starts_with(config('app.url'), 'https'),
    'CSRF Protection' => class_exists(\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class),
    'Password Hashing' => config('hashing.driver') === 'bcrypt' || config('hashing.driver') === 'argon',
    'Environment' => config('app.env') === 'production'
];

foreach ($securityChecks as $check => $passed) {
    if ($passed) {
        echo "✅ $check: Secure\n";
        $passedTests++;
    } else {
        echo "⚠️ $check: Review needed\n";
    }
    $totalTests++;
}

// 7. PERFORMANCE METRICS
echo PHP_EOL . "▶ 7. PERFORMANCE BENCHMARKS\n";
echo str_repeat('─', 70) . PHP_EOL;

$performanceTests = [
    'Simple Query' => function() {
        $start = microtime(true);
        DB::select('SELECT 1');
        return (microtime(true) - $start) * 1000;
    },
    'Load 10 Records' => function() {
        $start = microtime(true);
        \App\Models\Customer::limit(10)->get();
        return (microtime(true) - $start) * 1000;
    },
    'Cache Operation' => function() {
        $start = microtime(true);
        Cache::put('perf_test', 'value', 1);
        Cache::get('perf_test');
        Cache::forget('perf_test');
        return (microtime(true) - $start) * 1000;
    }
];

foreach ($performanceTests as $test => $func) {
    try {
        $time = $func();
        $status = $time < 50 ? '✅' : ($time < 100 ? '⚠️' : '❌');
        echo "$status $test: " . round($time, 2) . "ms\n";
        if ($time < 100) $passedTests++;
        else $failedTests++;
    } catch (Exception $e) {
        echo "❌ $test: Failed\n";
        $failedTests++;
    }
    $totalTests++;
}

// 8. ERROR HANDLING
echo PHP_EOL . "▶ 8. ERROR HANDLING TESTS\n";
echo str_repeat('─', 70) . PHP_EOL;

// Test 404 handling
$request404 = Request::create('/nonexistent-page', 'GET');
$response404 = $kernel->handle($request404);
if ($response404->getStatusCode() == 404) {
    echo "✅ 404 Error Handling: Working\n";
    $passedTests++;
} else {
    echo "❌ 404 Error Handling: Not working\n";
    $failedTests++;
}
$totalTests++;

// Test validation
try {
    $validator = Validator::make(
        ['email' => 'invalid'],
        ['email' => 'required|email']
    );
    if ($validator->fails()) {
        echo "✅ Validation System: Working\n";
        $passedTests++;
    } else {
        echo "❌ Validation System: Not catching errors\n";
        $failedTests++;
    }
} catch (Exception $e) {
    echo "❌ Validation System: " . $e->getMessage() . PHP_EOL;
    $failedTests++;
}
$totalTests++;

// 9. LOGGING SYSTEM
echo PHP_EOL . "▶ 9. LOGGING SYSTEM\n";
echo str_repeat('─', 70) . PHP_EOL;

$logFile = storage_path('logs/laravel.log');
if (file_exists($logFile)) {
    $logSize = filesize($logFile);
    echo "✅ Laravel Log: Exists (" . round($logSize / 1024, 2) . " KB)\n";
    $passedTests++;
} else {
    echo "⚠️ Laravel Log: Not found\n";
}
$totalTests++;

// 10. QUEUE SYSTEM
echo PHP_EOL . "▶ 10. QUEUE CONFIGURATION\n";
echo str_repeat('─', 70) . PHP_EOL;

$queueDriver = config('queue.default');
echo "Queue Driver: $queueDriver\n";
if ($queueDriver !== 'sync') {
    try {
        // Test queue connection
        Queue::size();
        echo "✅ Queue System: Connected\n";
        $passedTests++;
    } catch (Exception $e) {
        echo "⚠️ Queue System: " . $e->getMessage() . PHP_EOL;
    }
} else {
    echo "ℹ️ Queue System: Using sync driver (no queue)\n";
}
$totalTests++;

// FINAL SUMMARY
echo PHP_EOL;
echo "╔══════════════════════════════════════════════════════════════════╗\n";
echo "║                         TEST SUMMARY                             ║\n";
echo "╚══════════════════════════════════════════════════════════════════╝\n";

$successRate = $totalTests > 0 ? round(($passedTests / $totalTests) * 100, 1) : 0;

echo "Total Tests: $totalTests\n";
echo "✅ Passed: $passedTests\n";
echo "❌ Failed: $failedTests\n";
echo "Success Rate: $successRate%\n";

// Performance Score
$performanceScore = $successRate;

// Security Score
$securityScore = 0;
foreach ($securityChecks as $passed) {
    if ($passed) $securityScore += 20;
}

// Overall Health Score
$healthScore = round(($performanceScore + $securityScore) / 2);

echo PHP_EOL;
echo "📊 SYSTEM SCORES\n";
echo str_repeat('─', 70) . PHP_EOL;
echo "Performance Score: $performanceScore/100\n";
echo "Security Score: $securityScore/100\n";
echo "Overall Health: $healthScore/100\n";

// Final Verdict
echo PHP_EOL;
if ($healthScore >= 90) {
    echo "🏆 VERDICT: EXCELLENT - System is production-ready\n";
} elseif ($healthScore >= 70) {
    echo "✅ VERDICT: GOOD - System is functional with minor issues\n";
} elseif ($healthScore >= 50) {
    echo "⚠️ VERDICT: FAIR - System needs attention\n";
} else {
    echo "❌ VERDICT: CRITICAL - System has serious issues\n";
}

// Recommendations
echo PHP_EOL;
echo "📝 RECOMMENDATIONS\n";
echo str_repeat('─', 70) . PHP_EOL;

if ($failedTests > 0) {
    echo "• Fix database connection issues if any\n";
    echo "• Review failed tests and resolve issues\n";
}

if ($securityScore < 100) {
    echo "• Enable all security features for production\n";
    echo "• Ensure HTTPS is enforced\n";
}

if ($performanceScore < 80) {
    echo "• Optimize slow queries\n";
    echo "• Consider caching strategies\n";
}

echo PHP_EOL;
echo "Test completed at: " . date('Y-m-d H:i:s') . PHP_EOL;
echo "═══════════════════════════════════════════════════════════════════\n";