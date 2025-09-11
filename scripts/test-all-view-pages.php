<?php

require_once __DIR__ . '/../vendor/autoload.php';

use App\Models\User;
use App\Models\Branch;
use App\Models\Call;
use App\Models\Company;
use App\Models\Customer;
use App\Models\PhoneNumber;
use App\Models\RetellAgent;
use App\Models\Service;
use App\Models\Staff;
use App\Models\Tenant;
use App\Models\WorkingHour;
use App\Models\Appointment;
use Illuminate\Support\Facades\Auth;

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
echo "║         TESTING ALL VIEW PAGES FOR CONTENT                ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n\n";

$testData = [
    'Tenants' => [
        'model' => Tenant::class,
        'resource' => 'App\Filament\Admin\Resources\TenantResource',
        'viewPage' => 'App\Filament\Admin\Resources\TenantResource\Pages\ViewTenant',
    ],
    'Companies' => [
        'model' => Company::class,
        'resource' => 'App\Filament\Admin\Resources\CompanyResource',
        'viewPage' => 'App\Filament\Admin\Resources\CompanyResource\Pages\ViewCompany',
    ],
    'Branches' => [
        'model' => Branch::class,
        'resource' => 'App\Filament\Admin\Resources\BranchResource',
        'viewPage' => 'App\Filament\Admin\Resources\BranchResource\Pages\ViewBranch',
    ],
    'Calls' => [
        'model' => Call::class,
        'resource' => 'App\Filament\Admin\Resources\CallResource',
        'viewPage' => 'App\Filament\Admin\Resources\CallResource\Pages\ViewCall',
    ],
    'Customers' => [
        'model' => Customer::class,
        'resource' => 'App\Filament\Admin\Resources\CustomerResource',
        'viewPage' => 'App\Filament\Admin\Resources\CustomerResource\Pages\ViewCustomer',
    ],
    'Phone Numbers' => [
        'model' => PhoneNumber::class,
        'resource' => 'App\Filament\Admin\Resources\PhoneNumberResource',
        'viewPage' => 'App\Filament\Admin\Resources\PhoneNumberResource\Pages\ViewPhoneNumber',
    ],
    'Retell Agents' => [
        'model' => RetellAgent::class,
        'resource' => 'App\Filament\Admin\Resources\RetellAgentResource',
        'viewPage' => 'App\Filament\Admin\Resources\RetellAgentResource\Pages\ViewRetellAgent',
    ],
    'Services' => [
        'model' => Service::class,
        'resource' => 'App\Filament\Admin\Resources\ServiceResource',
        'viewPage' => 'App\Filament\Admin\Resources\ServiceResource\Pages\ViewService',
    ],
    'Staff' => [
        'model' => Staff::class,
        'resource' => 'App\Filament\Admin\Resources\StaffResource',
        'viewPage' => 'App\Filament\Admin\Resources\StaffResource\Pages\ViewStaff',
    ],
    'Users' => [
        'model' => User::class,
        'resource' => 'App\Filament\Admin\Resources\UserResource',
        'viewPage' => 'App\Filament\Admin\Resources\UserResource\Pages\ViewUser',
    ],
];

$errors = [];
$warnings = [];

foreach ($testData as $name => $config) {
    echo "Testing $name View Page\n";
    echo str_repeat('─', 50) . "\n";
    
    $modelClass = $config['model'];
    $resourceClass = $config['resource'];
    $viewPageClass = $config['viewPage'];
    
    // Get first record
    $record = $modelClass::first();
    
    if (!$record) {
        echo "  ⚠️  No records found - skipping\n\n";
        continue;
    }
    
    echo "  Record ID: " . $record->id . "\n";
    
    // Check if ViewPage exists
    if (!class_exists($viewPageClass)) {
        echo "  ❌ View page class not found: $viewPageClass\n";
        $errors[] = "$name: View page class missing";
        echo "\n";
        continue;
    }
    
    echo "  ✅ View page class exists\n";
    
    // Check if Resource has infolist method
    if (!method_exists($resourceClass, 'infolist')) {
        echo "  ❌ Resource missing infolist() method\n";
        $errors[] = "$name: Missing infolist() method";
        echo "\n";
        continue;
    }
    
    echo "  ✅ Resource has infolist() method\n";
    
    // Try to create infolist
    try {
        // Create a mock Infolist to test the schema
        $infolist = new \Filament\Infolists\Infolist('test');
        $configuredInfolist = $resourceClass::infolist($infolist);
        
        if ($configuredInfolist) {
            echo "  ✅ Infolist can be created\n";
            
            // Check if schema has components
            $schema = $configuredInfolist->getSchema();
            if (empty($schema)) {
                echo "  ⚠️  Infolist schema is empty\n";
                $warnings[] = "$name: Empty infolist schema";
            } else {
                echo "  ✅ Infolist has " . count($schema) . " component(s)\n";
            }
        }
    } catch (Exception $e) {
        echo "  ❌ Error creating infolist: " . $e->getMessage() . "\n";
        $errors[] = "$name: " . $e->getMessage();
    }
    
    // Generate URL
    $slug = strtolower(str_replace(' ', '-', $name));
    if ($name === 'Phone Numbers') $slug = 'phone-numbers';
    if ($name === 'Retell Agents') $slug = 'retell-agents';
    
    $url = "/admin/$slug/{$record->id}";
    echo "  URL: $url\n";
    
    echo "\n";
}

echo "╔════════════════════════════════════════════════════════════╗\n";
echo "║                         SUMMARY                           ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n\n";

if (count($errors) > 0) {
    echo "❌ ERRORS FOUND:\n";
    foreach ($errors as $error) {
        echo "  • $error\n";
    }
    echo "\n";
}

if (count($warnings) > 0) {
    echo "⚠️  WARNINGS:\n";
    foreach ($warnings as $warning) {
        echo "  • $warning\n";
    }
    echo "\n";
}

if (count($errors) === 0 && count($warnings) === 0) {
    echo "✅ All View pages appear to be configured correctly!\n";
} else {
    echo "🔧 Some issues need attention.\n";
    echo "\nPossible causes for empty pages:\n";
    echo "  1. Custom view files referenced but not existing\n";
    echo "  2. Infolist components not properly configured\n";
    echo "  3. Missing data relationships\n";
    echo "  4. View cache needs clearing\n";
}