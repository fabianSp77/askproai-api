<?php

require_once __DIR__ . '/../vendor/autoload.php';

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

// Bootstrap Laravel
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Login as admin
$admin = User::where('email', 'admin@askproai.de')->first();
if (!$admin) {
    echo "❌ Admin user not found\n";
    exit(1);
}

Auth::login($admin);

echo "\n╔════════════════════════════════════════════════════════════╗\n";
echo "║            TESTING ALL ADMIN PANEL PAGES                  ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n\n";

$errors = [];
$successes = [];

// Define all resources to test
$resources = [
    'appointments' => ['model' => 'App\Models\Appointment', 'resource' => 'App\Filament\Admin\Resources\AppointmentResource'],
    'branches' => ['model' => 'App\Models\Branch', 'resource' => 'App\Filament\Admin\Resources\BranchResource'],
    'calls' => ['model' => 'App\Models\Call', 'resource' => 'App\Filament\Admin\Resources\CallResource'],
    'companies' => ['model' => 'App\Models\Company', 'resource' => 'App\Filament\Admin\Resources\CompanyResource'],
    'customers' => ['model' => 'App\Models\Customer', 'resource' => 'App\Filament\Admin\Resources\CustomerResource'],
    'enhanced-calls' => ['model' => 'App\Models\Call', 'resource' => 'App\Filament\Admin\Resources\EnhancedCallResource'],
    'integrations' => ['model' => 'App\Models\Integration', 'resource' => 'App\Filament\Admin\Resources\IntegrationResource'],
    'phone-numbers' => ['model' => 'App\Models\PhoneNumber', 'resource' => 'App\Filament\Admin\Resources\PhoneNumberResource'],
    'retell-agents' => ['model' => 'App\Models\RetellAgent', 'resource' => 'App\Filament\Admin\Resources\RetellAgentResource'],
    'services' => ['model' => 'App\Models\Service', 'resource' => 'App\Filament\Admin\Resources\ServiceResource'],
    'staff' => ['model' => 'App\Models\Staff', 'resource' => 'App\Filament\Admin\Resources\StaffResource'],
    'tenants' => ['model' => 'App\Models\Tenant', 'resource' => 'App\Filament\Admin\Resources\TenantResource'],
    'users' => ['model' => 'App\Models\User', 'resource' => 'App\Filament\Admin\Resources\UserResource'],
    'working-hours' => ['model' => 'App\Models\WorkingHour', 'resource' => 'App\Filament\Admin\Resources\WorkingHourResource'],
];

foreach ($resources as $slug => $config) {
    echo "Testing: $slug\n";
    echo str_repeat('-', 40) . "\n";
    
    $resourceClass = $config['resource'];
    $modelClass = $config['model'];
    
    // Test 1: Check if resource class exists
    if (!class_exists($resourceClass)) {
        $errors[] = "$slug: Resource class not found";
        echo "  ❌ Resource class not found\n\n";
        continue;
    }
    
    // Test 2: Check if model class exists
    if (!class_exists($modelClass)) {
        $errors[] = "$slug: Model class not found";
        echo "  ❌ Model class not found\n\n";
        continue;
    }
    
    // Test 3: Check pages
    try {
        $pages = $resourceClass::getPages();
        $pageTypes = ['index', 'create', 'view', 'edit'];
        
        foreach ($pageTypes as $pageType) {
            if (isset($pages[$pageType])) {
                // Get the page class
                $pageClass = is_object($pages[$pageType]) 
                    ? get_class($pages[$pageType]) 
                    : $pages[$pageType];
                
                // Extract class name from route registration
                if (strpos($pageClass, '::route') !== false) {
                    // It's a PageRegistration, extract the class
                    $pageClass = str_replace('::route', '', $pageClass);
                    $pageClass = preg_replace('/\(.*\)/', '', $pageClass);
                    $pageClass = trim($pageClass);
                }
                
                // Check if page class exists
                if (class_exists($pageClass)) {
                    echo "  ✅ " . ucfirst($pageType) . " page exists\n";
                    $successes[] = "$slug: " . ucfirst($pageType) . " page works";
                } else {
                    echo "  ⚠️  " . ucfirst($pageType) . " page class not found: $pageClass\n";
                }
            } else {
                echo "  ❌ " . ucfirst($pageType) . " page not registered\n";
            }
        }
        
        // Test 4: Check if we can get a record
        try {
            $record = $modelClass::first();
            if ($record) {
                echo "  📊 Sample record found (ID: {$record->id})\n";
                
                // Test if infolist method exists (needed for View pages)
                if (method_exists($resourceClass, 'infolist')) {
                    echo "  ✅ Infolist method exists\n";
                } else {
                    echo "  ⚠️  No infolist method (View pages might not display data)\n";
                    $errors[] = "$slug: Missing infolist method for View pages";
                }
            } else {
                echo "  ℹ️  No records in database\n";
            }
        } catch (Exception $e) {
            echo "  ❌ Error accessing model: " . $e->getMessage() . "\n";
            $errors[] = "$slug: Model error - " . $e->getMessage();
        }
        
    } catch (Exception $e) {
        echo "  ❌ Error getting pages: " . $e->getMessage() . "\n";
        $errors[] = "$slug: " . $e->getMessage();
    }
    
    echo "\n";
}

// Summary
echo "\n╔════════════════════════════════════════════════════════════╗\n";
echo "║                         SUMMARY                           ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n\n";

if (count($errors) > 0) {
    echo "❌ ERRORS FOUND:\n";
    foreach ($errors as $error) {
        echo "  • $error\n";
    }
    echo "\n";
}

echo "📊 Statistics:\n";
echo "  • Total resources tested: " . count($resources) . "\n";
echo "  • Successful checks: " . count($successes) . "\n";
echo "  • Errors found: " . count($errors) . "\n";

if (count($errors) === 0) {
    echo "\n✅ All pages appear to be configured correctly!\n";
} else {
    echo "\n⚠️  Some issues need to be fixed.\n";
}