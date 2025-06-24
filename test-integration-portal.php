<?php

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Filament\Admin\Pages\CompanyIntegrationPortal;

// Login as the user
$user = User::where('email', 'fabian@askproai.de')->first();
if (!$user) {
    die("User not found\n");
}

auth()->login($user);

echo "Testing Company Integration Portal for user: {$user->email}\n";
echo "User Company ID: {$user->company_id}\n";
echo "User has roles: " . $user->roles->pluck('name')->join(', ') . "\n\n";

// Create instance of the page
try {
    $page = new CompanyIntegrationPortal();
    
    // Call mount method
    $page->mount();
    
    echo "Companies loaded: " . count($page->companies) . "\n";
    
    if (count($page->companies) > 0) {
        foreach($page->companies as $company) {
            echo "- Company: {$company['name']} (ID: {$company['id']}, Active: " . ($company['is_active'] ? 'Yes' : 'No') . ")\n";
        }
    } else {
        echo "No companies loaded!\n";
    }
    
    // Check if services are initialized
    echo "\nChecking service initialization:\n";
    $reflection = new ReflectionClass($page);
    
    $calcomService = $reflection->getProperty('calcomService');
    $calcomService->setAccessible(true);
    echo "CalcomService initialized: " . ($calcomService->getValue($page) ? 'Yes' : 'No') . "\n";
    
    $retellService = $reflection->getProperty('retellService');
    $retellService->setAccessible(true);
    echo "RetellService initialized: " . ($retellService->getValue($page) ? 'Yes' : 'No') . "\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Trace:\n" . $e->getTraceAsString() . "\n";
}