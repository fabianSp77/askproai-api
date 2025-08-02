<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->handle(Illuminate\Http\Request::capture());

use App\Models\User;
use App\Models\Company;
use App\Models\Appointment;
use App\Models\Call;
use App\Models\Customer;
use App\Models\Branch;

echo "=== CompanyScope Test ===\n\n";

// Login as first user
$user = User::first();
if (!$user) {
    die("No users found in database\n");
}

auth()->login($user);
echo "Logged in as: " . $user->email . "\n";
echo "User ID: " . $user->id . "\n";
echo "Company ID: " . ($user->company_id ?? 'NULL') . "\n\n";

// Simulate web auth context
app()->instance('current_company_id', $user->company_id);
app()->instance('company_context_source', 'web_auth');

echo "=== App Context ===\n";
echo "current_company_id: " . app('current_company_id') . "\n";
echo "company_context_source: " . app('company_context_source') . "\n\n";

echo "=== Testing Data Access ===\n";

try {
    // Test different models
    $models = [
        'Appointments' => Appointment::class,
        'Calls' => Call::class,
        'Customers' => Customer::class,
        'Branches' => Branch::class,
    ];
    
    foreach ($models as $name => $modelClass) {
        echo "\n$name:\n";
        
        // Check if model uses CompanyScope
        $model = new $modelClass;
        $usesScope = false;
        
        // Check if BelongsToCompany trait is used
        if (in_array('App\Traits\BelongsToCompany', class_uses_recursive($modelClass))) {
            $usesScope = true;
            echo "- Uses BelongsToCompany trait: YES\n";
        } else {
            echo "- Uses BelongsToCompany trait: NO\n";
        }
        
        // Count with scope
        $countWithScope = $modelClass::count();
        echo "- Count with scope: $countWithScope\n";
        
        // Count without scope
        if ($usesScope) {
            $countWithoutScope = $modelClass::withoutGlobalScope(\App\Models\Scopes\CompanyScope::class)->count();
            echo "- Count without scope: $countWithoutScope\n";
            echo "- Records from other companies: " . ($countWithoutScope - $countWithScope) . "\n";
        }
        
        // Get SQL query
        $builder = $modelClass::query();
        $sql = $builder->toSql();
        $bindings = $builder->getBindings();
        
        echo "- SQL: " . $sql . "\n";
        if (!empty($bindings)) {
            echo "- Bindings: " . json_encode($bindings) . "\n";
        }
        
        // Check scope status
        if (strpos($sql, 'company_id') !== false) {
            echo "- ✅ CompanyScope is ACTIVE (filtering by company_id)\n";
        } else if (strpos($sql, '0 = 1') !== false) {
            echo "- ⚠️ CompanyScope BLOCKED all data (no company context)\n";
        } else {
            echo "- ❌ CompanyScope NOT applied\n";
        }
        
        // Test a specific query
        if ($countWithScope > 0) {
            $firstRecord = $modelClass::first();
            if ($firstRecord && isset($firstRecord->company_id)) {
                echo "- First record company_id: " . $firstRecord->company_id . "\n";
                echo "- Matches user company: " . ($firstRecord->company_id == $user->company_id ? 'YES' : 'NO') . "\n";
            }
        }
    }
    
    echo "\n=== Testing Filament Context ===\n";
    
    // Simulate Filament request
    $request = Illuminate\Http\Request::create('/admin/appointments', 'GET');
    $request->setUserResolver(function() use ($user) { return $user; });
    
    // Run middleware
    $middleware = new \App\Http\Middleware\EnsureCompanyContext();
    $middleware->handle($request, function($req) {
        echo "After EnsureCompanyContext middleware:\n";
        echo "- current_company_id: " . app('current_company_id') . "\n";
        echo "- company_context_source: " . app('company_context_source') . "\n";
        return response('OK');
    });
    
} catch (Exception $e) {
    echo "\nError: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
    echo "\nTrace:\n" . $e->getTraceAsString() . "\n";
}

echo "\n=== CompanyScope Test Complete ===\n";