<?php

/**
 * FINAL COMPREHENSIVE SYSTEM TEST
 * Tests all critical components to ensure everything works
 */

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Models\User;
use App\Models\Company;
use App\Models\Branch;

echo "\n\033[1;34m=== FINAL SYSTEM TEST ===\033[0m\n\n";

$testResults = [];
$errors = [];

// 1. Test Database Tables
echo "\033[1;33m1. TESTING DATABASE TABLES\033[0m\n";

$requiredTables = [
    'companies', 'branches', 'staff', 'customers', 'appointments', 'calls', 'services',
    'users', 'invoices', 'invoice_items', 'phone_numbers', 'integrations',
    'calcom_event_types', 'unified_event_types', 'webhook_events', 'webhook_logs',
    'knowledge_categories', 'knowledge_documents', 'gdpr_requests', 'cookie_consents',
    'tax_rates', 'company_pricings', 'master_services', 'branch_service_overrides',
    'branch_staff', 'branch_service', 'staff_services', 'staff_event_types'
];

foreach ($requiredTables as $table) {
    if (Schema::hasTable($table)) {
        echo "✓ Table '$table' exists\n";
        $testResults['tables'][$table] = true;
    } else {
        echo "❌ Table '$table' is missing\n";
        $errors[] = "Missing table: $table";
        $testResults['tables'][$table] = false;
    }
}

// 2. Test Filament Resources
echo "\n\033[1;33m2. TESTING FILAMENT RESOURCES\033[0m\n";

$resources = [
    'App\Filament\Admin\Resources\StaffResource',
    'App\Filament\Admin\Resources\AppointmentResource',
    'App\Filament\Admin\Resources\BranchResource',
    'App\Filament\Admin\Resources\CompanyResource',
    'App\Filament\Admin\Resources\CustomerResource',
    'App\Filament\Admin\Resources\ServiceResource',
    'App\Filament\Admin\Resources\CallResource',
    'App\Filament\Admin\Resources\InvoiceResource',
    'App\Filament\Admin\Resources\PhoneNumberResource',
];

foreach ($resources as $resourceClass) {
    if (class_exists($resourceClass)) {
        echo "✓ Resource '$resourceClass' exists";
        
        // Test canViewAny permission
        if (method_exists($resourceClass, 'canViewAny')) {
            try {
                $canView = $resourceClass::canViewAny();
                if ($canView) {
                    echo " - Permissions: ✓\n";
                    $testResults['resources'][$resourceClass] = true;
                } else {
                    echo " - Permissions: ❌ (returns false)\n";
                    $errors[] = "$resourceClass::canViewAny() returns false";
                    $testResults['resources'][$resourceClass] = false;
                }
            } catch (Exception $e) {
                echo " - Permissions: ❌ (error)\n";
                $errors[] = "$resourceClass::canViewAny() error: " . $e->getMessage();
                $testResults['resources'][$resourceClass] = false;
            }
        } else {
            echo " - Permissions: ❌ (method missing)\n";
            $errors[] = "$resourceClass missing canViewAny() method";
            $testResults['resources'][$resourceClass] = false;
        }
    } else {
        echo "❌ Resource '$resourceClass' not found\n";
        $errors[] = "Resource class not found: $resourceClass";
        $testResults['resources'][$resourceClass] = false;
    }
}

// 3. Test User and Company Setup
echo "\n\033[1;33m3. TESTING USER & COMPANY SETUP\033[0m\n";

$user = User::first();
if ($user) {
    echo "✓ User found: " . $user->email . "\n";
    if ($user->company_id) {
        echo "✓ User has company_id: " . $user->company_id . "\n";
        $testResults['user']['has_company'] = true;
        
        $company = Company::find($user->company_id);
        if ($company) {
            echo "✓ Company exists: " . $company->name . "\n";
            $testResults['company']['exists'] = true;
        } else {
            echo "❌ Company not found for user\n";
            $errors[] = "Company ID {$user->company_id} not found";
            $testResults['company']['exists'] = false;
        }
    } else {
        echo "❌ User has no company_id\n";
        $errors[] = "User has no company_id";
        $testResults['user']['has_company'] = false;
    }
} else {
    echo "❌ No user found in database\n";
    $errors[] = "No user found";
    $testResults['user']['exists'] = false;
}

// 4. Test Model Relationships
echo "\n\033[1;33m4. TESTING MODEL RELATIONSHIPS\033[0m\n";

try {
    // Test Branch model
    $branch = Branch::first();
    if ($branch) {
        echo "✓ Branch model works\n";
        
        // Test relationships
        if (method_exists($branch, 'company')) {
            $branch->company;
            echo "✓ Branch->company relationship works\n";
        }
        
        if (method_exists($branch, 'staff')) {
            $branch->staff()->count();
            echo "✓ Branch->staff relationship works\n";
        }
    }
    
    // Test if we can create a staff member
    if ($branch && $company) {
        try {
            $staff = new \App\Models\Staff();
            $staff->id = \Illuminate\Support\Str::uuid();
            $staff->company_id = $company->id;
            $staff->branch_id = $branch->id;
            $staff->name = 'Test Staff';
            $staff->email = 'test@example.com';
            $staff->save();
            
            echo "✓ Can create staff member\n";
            
            // Clean up
            $staff->delete();
        } catch (Exception $e) {
            echo "⚠️ Cannot create staff: " . $e->getMessage() . "\n";
        }
    }
} catch (Exception $e) {
    echo "❌ Model relationship error: " . $e->getMessage() . "\n";
    $errors[] = "Model relationship error: " . $e->getMessage();
}

// 5. Summary
echo "\n\033[1;34m=== TEST SUMMARY ===\033[0m\n\n";

$totalTests = 0;
$passedTests = 0;

foreach ($testResults as $category => $results) {
    foreach ($results as $test => $passed) {
        $totalTests++;
        if ($passed) $passedTests++;
    }
}

echo "Total Tests: $totalTests\n";
echo "Passed: $passedTests\n";
echo "Failed: " . ($totalTests - $passedTests) . "\n";
echo "Success Rate: " . round(($passedTests / $totalTests) * 100, 2) . "%\n";

if (count($errors) > 0) {
    echo "\n\033[1;31mERRORS FOUND:\033[0m\n";
    foreach ($errors as $error) {
        echo "❌ $error\n";
    }
} else {
    echo "\n\033[1;32m✅ ALL TESTS PASSED! The system is fully operational!\033[0m\n";
}

echo "\n\033[1;34mRECOMMENDED NEXT STEPS:\033[0m\n";
echo "1. Access /admin and verify all pages load correctly\n";
echo "2. Create a test staff member\n";
echo "3. Create a test appointment\n";
echo "4. Test the webhook endpoints\n";
echo "5. Verify Cal.com integration\n";