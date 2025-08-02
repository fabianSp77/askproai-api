<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle($request = Illuminate\Http\Request::capture());

echo "=== Testing All Resources Loading ===\n\n";

// Test authentication
$user = auth()->user();
if (!$user) {
    // Try to login
    $user = \App\Models\User::where('email', 'fabian@askproai.de')->first();
    if ($user) {
        auth()->login($user);
        echo "Logged in as: " . $user->email . "\n";
    } else {
        die("No user found or not authenticated\n");
    }
} else {
    echo "Already authenticated as: " . $user->email . "\n";
}

echo "User ID: " . $user->id . "\n";
echo "Company ID: " . ($user->company_id ?? 'NULL') . "\n\n";

// Check app context
echo "=== App Context ===\n";
echo "current_company_id: " . (app()->has('current_company_id') ? app('current_company_id') : 'NOT SET') . "\n";
echo "company_context_source: " . (app()->has('company_context_source') ? app('company_context_source') : 'NOT SET') . "\n\n";

// Simulate EnsureCompanyContext middleware
if (!app()->has('current_company_id') && $user->company_id) {
    app()->instance('current_company_id', $user->company_id);
    app()->instance('company_context_source', 'web_auth');
    echo "Set company context manually\n\n";
}

// Test each resource
$resources = [
    'Calls' => \App\Models\Call::class,
    'Appointments' => \App\Models\Appointment::class,
    'Customers' => \App\Models\Customer::class,
    'Branches' => \App\Models\Branch::class,
    'Services' => \App\Models\Service::class,
    'Staff' => \App\Models\Staff::class,
];

echo "=== Testing Resource Queries ===\n";

foreach ($resources as $name => $modelClass) {
    echo "\n--- $name ---\n";
    
    try {
        // Test basic count
        $count = $modelClass::count();
        echo "Count: $count\n";
        
        // Check SQL query
        $query = $modelClass::query();
        $sql = $query->toSql();
        $bindings = $query->getBindings();
        
        echo "SQL: $sql\n";
        if (!empty($bindings)) {
            echo "Bindings: " . json_encode($bindings) . "\n";
        }
        
        // Check if filtered by company_id
        if (strpos($sql, 'company_id') !== false) {
            echo "✅ Filtered by company_id\n";
        } else if (strpos($sql, '0 = 1') !== false) {
            echo "❌ BLOCKED by CompanyScope (0 = 1)\n";
        } else {
            echo "⚠️ No company filter applied\n";
        }
        
        // Try to load first record
        if ($count > 0) {
            $first = $modelClass::first();
            if ($first && isset($first->company_id)) {
                echo "First record company_id: " . $first->company_id . "\n";
            }
        }
        
    } catch (Exception $e) {
        echo "❌ ERROR: " . $e->getMessage() . "\n";
    }
}

// Test Filament Resource access
echo "\n\n=== Testing Filament Resources ===\n";

$filamentResources = [
    'CallResource' => \App\Filament\Admin\Resources\CallResource::class,
    'AppointmentResource' => \App\Filament\Admin\Resources\AppointmentResource::class,
    'CustomerResource' => \App\Filament\Admin\Resources\CustomerResource::class,
    'BranchResource' => \App\Filament\Admin\Resources\BranchResource::class,
];

foreach ($filamentResources as $name => $resourceClass) {
    echo "\n--- $name ---\n";
    
    try {
        // Check if user can view
        $canView = $resourceClass::canViewAny();
        echo "Can view: " . ($canView ? 'YES' : 'NO') . "\n";
        
        // Check navigation
        $shouldRegister = $resourceClass::shouldRegisterNavigation();
        echo "Shows in navigation: " . ($shouldRegister ? 'YES' : 'NO') . "\n";
        
    } catch (Exception $e) {
        echo "Error: " . $e->getMessage() . "\n";
    }
}

// Check for common issues
echo "\n\n=== Checking Common Issues ===\n";

// 1. Session issues
if (session_status() === PHP_SESSION_ACTIVE) {
    echo "✅ Session is active\n";
    echo "Session ID: " . session_id() . "\n";
} else {
    echo "❌ Session is NOT active\n";
}

// 2. Company context
$companyScope = new \App\Models\Scopes\CompanyScope();
$activeCompanyId = $companyScope->getCurrentCompanyId();
echo "CompanyScope reports company ID: " . ($activeCompanyId ?? 'NULL') . "\n";

// 3. Check a specific problematic query
echo "\n=== Testing Problematic Query ===\n";
try {
    $calls = \App\Models\Call::with(['customer:id,name,phone,email'])
        ->select('calls.*')
        ->limit(5)
        ->get();
    
    echo "Successfully loaded " . count($calls) . " calls\n";
    
    if (count($calls) > 0) {
        $firstCall = $calls->first();
        echo "First call ID: " . $firstCall->id . "\n";
        echo "Has customer: " . ($firstCall->customer ? 'YES' : 'NO') . "\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error loading calls: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
}

echo "\n=== Test Complete ===\n";