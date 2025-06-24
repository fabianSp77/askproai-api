<?php

require_once __DIR__ . '/vendor/autoload.php';

use App\Models\Company;
use App\Services\RetellV2Service;

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== Testing Retell Dashboards ===\n\n";

// Test basic configuration
echo "1. Testing Company Configuration\n";
$company = Company::first();
if (!$company) {
    echo "   ❌ No company found\n";
    exit(1);
}
echo "   ✅ Company found: {$company->name}\n";

if (!$company->retell_api_key) {
    echo "   ❌ No Retell API key configured\n";
    exit(1);
}
echo "   ✅ Retell API key configured\n";

// Test API connection
echo "\n2. Testing Retell API Connection\n";
try {
    $apiKey = $company->retell_api_key;
    if (strlen($apiKey) > 50) {
        $apiKey = decrypt($apiKey);
    }
    
    $service = new RetellV2Service($apiKey);
    
    // Test listing agents
    $agentsResult = $service->listAgents();
    $agentCount = count($agentsResult['agents'] ?? []);
    echo "   ✅ Listed {$agentCount} agents\n";
    
    // Test listing phone numbers
    $phonesResult = $service->listPhoneNumbers();
    $phoneCount = count($phonesResult['phone_numbers'] ?? []);
    echo "   ✅ Listed {$phoneCount} phone numbers\n";
    
} catch (\Exception $e) {
    echo "   ❌ API Error: " . $e->getMessage() . "\n";
    exit(1);
}

// Test dashboard classes
echo "\n3. Testing Dashboard Classes\n";
$dashboards = [
    'RetellDashboard' => 'App\Filament\Admin\Pages\RetellDashboard',
    'RetellUltimateDashboard' => 'App\Filament\Admin\Pages\RetellUltimateDashboard',
    'RetellDashboardImproved' => 'App\Filament\Admin\Pages\RetellDashboardImproved',
    'RetellDashboardUltra' => 'App\Filament\Admin\Pages\RetellDashboardUltra',
];

foreach ($dashboards as $name => $class) {
    if (class_exists($class)) {
        echo "   ✅ {$name} exists\n";
        
        // Check if view file exists
        $viewPath = $class::$view ?? null;
        if ($viewPath) {
            $bladePath = resource_path('views/' . str_replace('.', '/', $viewPath) . '.blade.php');
            if (file_exists($bladePath)) {
                echo "      ✅ View file exists\n";
            } else {
                echo "      ❌ View file missing: {$bladePath}\n";
            }
        }
    } else {
        echo "   ❌ {$name} class not found\n";
    }
}

// Check routes
echo "\n4. Testing Routes\n";
$routes = [
    '/admin/retell-dashboard',
    '/admin/retell-ultimate-dashboard',
    '/admin/retell-dashboard-improved',
    '/admin/retell-dashboard-ultra',
];

foreach ($routes as $route) {
    $routeExists = app('router')->getRoutes()->match(
        app('request')->create($route, 'GET')
    );
    
    if ($routeExists) {
        echo "   ✅ Route exists: {$route}\n";
    } else {
        echo "   ❌ Route missing: {$route}\n";
    }
}

echo "\n=== Test Complete ===\n";