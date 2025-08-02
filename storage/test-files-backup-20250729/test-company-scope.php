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

// Check if authenticated
if (!auth()->check()) {
    die("Please login first to test CompanyScope\n");
}

$user = auth()->user();
echo "Authenticated as: " . $user->email . "\n";
echo "User ID: " . $user->id . "\n";
echo "Company ID: " . ($user->company_id ?? 'NULL') . "\n\n";

// Test app context
echo "=== App Context ===\n";
echo "current_company_id: " . (app()->has('current_company_id') ? app('current_company_id') : 'NOT SET') . "\n";
echo "company_context_source: " . (app()->has('company_context_source') ? app('company_context_source') : 'NOT SET') . "\n\n";

// Test data access with scope
echo "=== Testing Data Access ===\n";

try {
    // Test different models
    $models = [
        'Companies' => Company::class,
        'Appointments' => Appointment::class,
        'Calls' => Call::class,
        'Customers' => Customer::class,
        'Branches' => Branch::class,
    ];
    
    foreach ($models as $name => $modelClass) {
        echo "\n$name:\n";
        
        // Count with scope
        $countWithScope = $modelClass::count();
        echo "- Count with scope: $countWithScope\n";
        
        // Count without scope (if user is super admin)
        if ($user->hasRole('super_admin') || $user->hasRole('Super Admin')) {
            $countWithoutScope = $modelClass::withoutGlobalScope(\App\Models\Scopes\CompanyScope::class)->count();
            echo "- Count without scope: $countWithoutScope\n";
            echo "- Scope filtering: " . ($countWithoutScope - $countWithScope) . " records\n";
        }
        
        // Get SQL query being executed
        $query = $modelClass::toSql();
        echo "- SQL: " . $query . "\n";
        
        // Check if company_id filter is applied
        if (strpos($query, 'company_id') !== false) {
            echo "- ✓ CompanyScope is active\n";
        } else if (strpos($query, '0 = 1') !== false) {
            echo "- ⚠️ CompanyScope blocked all data (no company context)\n";
        } else {
            echo "- ✗ CompanyScope NOT applied\n";
        }
    }
    
} catch (Exception $e) {
    echo "\nError: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
}

echo "\n=== CompanyScope Test Complete ===\n";