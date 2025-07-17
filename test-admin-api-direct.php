<?php

// Direct API test without authentication
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

// Temporarily disable auth for testing
app()->bind('auth', function() {
    return new class {
        public function guard() {
            return new class {
                public function check() { return true; }
                public function user() { 
                    return (object)['id' => 1, 'name' => 'Test Admin'];
                }
            };
        }
    };
});

// Test CompanyController directly
echo "Testing CompanyController directly:\n";
echo "===================================\n\n";

try {
    $controller = app(\App\Http\Controllers\Admin\Api\CompanyController::class);
    $request = new \Illuminate\Http\Request();
    
    echo "1. Testing index():\n";
    $response = $controller->index($request);
    $data = json_decode($response->getContent(), true);
    echo "   Status: " . $response->getStatusCode() . "\n";
    echo "   Companies found: " . count($data['data'] ?? []) . "\n";
    echo "   First company: " . json_encode($data['data'][0] ?? 'No companies') . "\n\n";
    
    echo "2. Testing stats():\n";
    $response = $controller->stats();
    $stats = json_decode($response->getContent(), true);
    echo "   Total companies: " . ($stats['total_companies'] ?? 'N/A') . "\n";
    echo "   Active companies: " . ($stats['active_companies'] ?? 'N/A') . "\n";
    echo "   Total calls: " . ($stats['total_calls'] ?? 'N/A') . "\n\n";
    
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "Trace:\n" . $e->getTraceAsString() . "\n";
}

// Test DashboardController
echo "\n3. Testing DashboardController:\n";
try {
    $controller = app(\App\Http\Controllers\Admin\Api\DashboardController::class);
    $request = new \Illuminate\Http\Request(['simple' => true]);
    
    $response = $controller->stats($request);
    $stats = json_decode($response->getContent(), true);
    echo "   Dashboard stats: " . json_encode($stats) . "\n";
} catch (\Exception $e) {
    echo "   Error: " . $e->getMessage() . "\n";
}

// Test CallController
echo "\n4. Testing CallController:\n";
try {
    $controller = app(\App\Http\Controllers\Admin\Api\CallController::class);
    $request = new \Illuminate\Http\Request();
    
    $response = $controller->index($request);
    $data = json_decode($response->getContent(), true);
    echo "   Calls found: " . count($data['data'] ?? []) . "\n";
    
    $response = $controller->stats();
    $stats = json_decode($response->getContent(), true);
    echo "   Total calls: " . ($stats['total_calls'] ?? 'N/A') . "\n";
    echo "   Calls today: " . ($stats['calls_today'] ?? 'N/A') . "\n";
} catch (\Exception $e) {
    echo "   Error: " . $e->getMessage() . "\n";
}

$kernel->terminate($request, $response);