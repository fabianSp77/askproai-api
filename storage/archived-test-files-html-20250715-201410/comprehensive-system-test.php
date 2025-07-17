<?php
/**
 * Comprehensive System Test
 * Testet ALLE kritischen Funktionen beider Portale
 */

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$request = Illuminate\Http\Request::capture();
$response = $kernel->handle($request);
$kernel->terminate($request, $response);

// Farben für Terminal-Output
$red = "\033[0;31m";
$green = "\033[0;32m";
$yellow = "\033[0;33m";
$blue = "\033[0;34m";
$reset = "\033[0m";

echo "{$blue}=== COMPREHENSIVE SYSTEM TEST ==={$reset}\n";
echo "Datum: " . date('Y-m-d H:i:s') . "\n\n";

$totalTests = 0;
$passedTests = 0;
$failedTests = 0;
$warnings = 0;

function test($description, $condition, &$totalTests, &$passedTests, &$failedTests) {
    global $green, $red, $reset;
    $totalTests++;
    
    echo "Testing: {$description}... ";
    
    if ($condition) {
        $passedTests++;
        echo "{$green}✓ PASSED{$reset}\n";
        return true;
    } else {
        $failedTests++;
        echo "{$red}✗ FAILED{$reset}\n";
        return false;
    }
}

function section($title) {
    global $blue, $reset;
    echo "\n{$blue}=== {$title} ==={$reset}\n";
}

// 1. DATABASE TESTS
section("DATABASE SCHEMA VALIDATION");

// Check critical tables
$criticalTables = [
    'users', 'companies', 'branches', 'staff', 'customers', 
    'appointments', 'calls', 'services', 'portal_users'
];

foreach ($criticalTables as $table) {
    test(
        "Table '{$table}' exists",
        \DB::getSchemaBuilder()->hasTable($table),
        $totalTests, $passedTests, $failedTests
    );
}

// Check problematic columns
$columnChecks = [
    ['staff', 'is_active'],
    ['staff', 'active'],
    ['companies', 'is_active'],
    ['branches', 'is_active'],
    ['portal_users', 'company_id'],
    ['appointments', 'customer_id'],
    ['calls', 'branch_id']
];

foreach ($columnChecks as [$table, $column]) {
    test(
        "Column '{$column}' exists in '{$table}'",
        \DB::getSchemaBuilder()->hasColumn($table, $column),
        $totalTests, $passedTests, $failedTests
    );
}

// 2. AUTHENTICATION TESTS
section("AUTHENTICATION SYSTEM");

// Test Admin Auth
test(
    "Admin guard configured",
    config('auth.guards.admin') !== null,
    $totalTests, $passedTests, $failedTests
);

// Test Portal Auth
test(
    "Portal guard configured", 
    config('auth.guards.portal') !== null,
    $totalTests, $passedTests, $failedTests
);

// Session Configuration
test(
    "Admin session domain configured correctly",
    config('session.domain') !== null,
    $totalTests, $passedTests, $failedTests
);

// 3. ROUTE TESTS
section("ROUTE ACCESSIBILITY");

$routes = [
    ['GET', '/admin', 'Admin Dashboard'],
    ['GET', '/admin/login', 'Admin Login'],
    ['GET', '/business', 'Business Portal'],
    ['GET', '/business/login', 'Business Login'],
    ['GET', '/api/health', 'API Health Check']
];

foreach ($routes as [$method, $uri, $name]) {
    $testRequest = \Illuminate\Http\Request::create($uri, $method);
    
    try {
        $response = $kernel->handle($testRequest);
        $statusCode = $response->getStatusCode();
        
        // Login pages should return 200, others might redirect (302) if auth required
        $isOk = in_array($statusCode, [200, 302, 301]);
        
        test(
            "{$name} ({$method} {$uri}) - Status: {$statusCode}",
            $isOk,
            $totalTests, $passedTests, $failedTests
        );
    } catch (Exception $e) {
        test(
            "{$name} ({$method} {$uri}) - Error: " . $e->getMessage(),
            false,
            $totalTests, $passedTests, $failedTests
        );
    }
}

// 4. MIDDLEWARE TESTS
section("MIDDLEWARE CONFIGURATION");

$middlewareGroups = app(\Illuminate\Contracts\Http\Kernel::class)->getMiddlewareGroups();

test(
    "Web middleware group exists",
    isset($middlewareGroups['web']),
    $totalTests, $passedTests, $failedTests
);

test(
    "API middleware group exists",
    isset($middlewareGroups['api']),
    $totalTests, $passedTests, $failedTests
);

// 5. LIVEWIRE/ALPINE TESTS
section("FRONTEND FRAMEWORKS");

// Check Livewire Routes
$router = app('router');
$livewireRoutes = collect($router->getRoutes()->getRoutes())
    ->filter(fn($route) => str_contains($route->uri(), 'livewire'))
    ->count();

test(
    "Livewire routes registered ({$livewireRoutes} found)",
    $livewireRoutes > 0,
    $totalTests, $passedTests, $failedTests
);

// Check Assets
test(
    "Livewire assets published",
    file_exists(public_path('vendor/livewire/livewire.js')),
    $totalTests, $passedTests, $failedTests
);

// 6. API ENDPOINTS TEST
section("API ENDPOINTS");

$apiEndpoints = [
    '/api/v2/portal/auth/login',
    '/api/v2/portal/dashboard',
    '/api/v2/portal/appointments',
    '/api/v2/portal/calls'
];

foreach ($apiEndpoints as $endpoint) {
    try {
        $route = $router->getRoutes()->match(
            \Illuminate\Http\Request::create($endpoint, 'GET')
        );
        
        test(
            "API Endpoint {$endpoint} exists",
            $route !== null,
            $totalTests, $passedTests, $failedTests
        );
    } catch (Exception $e) {
        test(
            "API Endpoint {$endpoint} exists",
            false,
            $totalTests, $passedTests, $failedTests
        );
    }
}

// 7. BUSINESS PORTAL SPECIFIC
section("BUSINESS PORTAL CONFIGURATION");

// Check React Build
test(
    "React build exists",
    file_exists(public_path('business/index.html')),
    $totalTests, $passedTests, $failedTests
);

// Check Vite manifest
test(
    "Vite manifest exists",
    file_exists(public_path('build/manifest.json')),
    $totalTests, $passedTests, $failedTests
);

// 8. CACHE & PERFORMANCE
section("CACHE & PERFORMANCE");

$redisWorking = false;
try {
    \Illuminate\Support\Facades\Redis::ping();
    $redisWorking = true;
} catch (Exception $e) {
    $redisWorking = false;
}

test(
    "Redis connection working",
    $redisWorking,
    $totalTests, $passedTests, $failedTests
);

test(
    "Cache driver configured",
    config('cache.default') !== null,
    $totalTests, $passedTests, $failedTests
);

// 9. CRITICAL SERVICES
section("CRITICAL SERVICES");

$services = [
    'App\Services\CalcomV2Service',
    'App\Services\RetellV2Service',
    'App\Services\PrepaidBillingService',
    'App\Services\TranslationService'
];

foreach ($services as $service) {
    test(
        "Service {$service} exists",
        class_exists($service),
        $totalTests, $passedTests, $failedTests
    );
}

// 10. ERROR LOG CHECK
section("ERROR LOG ANALYSIS");

$logFile = storage_path('logs/laravel.log');
if (file_exists($logFile)) {
    $recentErrors = [];
    $handle = fopen($logFile, "r");
    
    if ($handle) {
        // Get last 1000 lines
        $lines = [];
        while (!feof($handle)) {
            $lines[] = fgets($handle);
            if (count($lines) > 1000) {
                array_shift($lines);
            }
        }
        fclose($handle);
        
        // Count error types
        $errorCounts = [
            'ERROR' => 0,
            'CRITICAL' => 0,
            'ALERT' => 0,
            'EMERGENCY' => 0
        ];
        
        foreach ($lines as $line) {
            foreach ($errorCounts as $level => &$count) {
                if (strpos($line, "local.{$level}") !== false) {
                    $count++;
                }
            }
        }
        
        echo "Recent errors in log:\n";
        foreach ($errorCounts as $level => $count) {
            if ($count > 0) {
                echo "  - {$level}: {$count}\n";
                $warnings++;
            }
        }
    }
}

// SUMMARY
echo "\n{$blue}=== TEST SUMMARY ==={$reset}\n";
echo "Total Tests: {$totalTests}\n";
echo "{$green}Passed: {$passedTests}{$reset}\n";
echo "{$red}Failed: {$failedTests}{$reset}\n";
echo "{$yellow}Warnings: {$warnings}{$reset}\n";

$successRate = $totalTests > 0 ? round(($passedTests / $totalTests) * 100, 2) : 0;
echo "\nSuccess Rate: {$successRate}%\n";

if ($failedTests === 0) {
    echo "\n{$green}✓ ALL TESTS PASSED!{$reset}\n";
} else {
    echo "\n{$red}✗ SYSTEM HAS ISSUES THAT NEED FIXING!{$reset}\n";
}

// Generate detailed report
$report = [
    'timestamp' => date('Y-m-d H:i:s'),
    'total_tests' => $totalTests,
    'passed' => $passedTests,
    'failed' => $failedTests,
    'warnings' => $warnings,
    'success_rate' => $successRate
];

file_put_contents(
    storage_path('logs/system-test-report-' . date('Y-m-d-His') . '.json'),
    json_encode($report, JSON_PRETTY_PRINT)
);

echo "\nDetailed report saved to storage/logs/\n";