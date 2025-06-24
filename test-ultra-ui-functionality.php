#!/usr/bin/env php
<?php

/**
 * Ultra UI/UX Functionality Test Script
 * Tests all functionality for Calls, Appointments, and Customers modules
 * 
 * Run: php test-ultra-ui-functionality.php
 */

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';

use App\Models\Customer;
use App\Models\Appointment;
use App\Models\Call;
use App\Models\Company;
use App\Models\Branch;
use App\Models\Service;
use App\Models\Staff;
use Illuminate\Support\Facades\DB;

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "\n🚀 ULTRA UI/UX FUNCTIONALITY TEST SUITE\n";
echo "=====================================\n\n";

$errors = [];
$warnings = [];
$successes = [];

// Test helper functions
function testPassed($message) {
    global $successes;
    $successes[] = $message;
    echo "✅ $message\n";
}

function testFailed($message, $details = '') {
    global $errors;
    $errors[] = compact('message', 'details');
    echo "❌ $message\n";
    if ($details) echo "   Details: $details\n";
}

function testWarning($message) {
    global $warnings;
    $warnings[] = $message;
    echo "⚠️  $message\n";
}

// Get first company for testing
$company = Company::first();
if (!$company) {
    die("❌ No company found in database. Please seed the database first.\n");
}

echo "🏢 Testing with company: {$company->name}\n";

// Set company context for tenant scope
app()->instance('company_id', $company->id);
session(['company_id' => $company->id]);

// Test 1: Resource Classes Existence
echo "\n1️⃣ TESTING RESOURCE CLASSES\n";
echo "----------------------------\n";

$resources = [
    'Calls' => [
        'resource' => 'App\Filament\Admin\Resources\UltimateCallResource',
        'pages' => [
            'index' => 'App\Filament\Admin\Resources\UltimateCallResource\Pages\UltimateListCalls',
            'create' => 'App\Filament\Admin\Resources\UltimateCallResource\Pages\CreateCall',
            'edit' => 'App\Filament\Admin\Resources\UltimateCallResource\Pages\EditCall',
            'view' => 'App\Filament\Admin\Resources\UltimateCallResource\Pages\ViewCall',
        ]
    ],
    'Appointments' => [
        'resource' => 'App\Filament\Admin\Resources\UltimateAppointmentResource',
        'pages' => [
            'index' => 'App\Filament\Admin\Resources\UltimateAppointmentResource\Pages\UltimateListAppointments',
            'create' => 'App\Filament\Admin\Resources\UltimateAppointmentResource\Pages\CreateAppointment',
            'edit' => 'App\Filament\Admin\Resources\UltimateAppointmentResource\Pages\EditAppointment',
            'view' => 'App\Filament\Admin\Resources\UltimateAppointmentResource\Pages\ViewAppointment',
        ]
    ],
    'Customers' => [
        'resource' => 'App\Filament\Admin\Resources\UltimateCustomerResource',
        'pages' => [
            'index' => 'App\Filament\Admin\Resources\UltimateCustomerResource\Pages\UltimateListCustomers',
            'create' => 'App\Filament\Admin\Resources\UltimateCustomerResource\Pages\CreateCustomer',
            'edit' => 'App\Filament\Admin\Resources\UltimateCustomerResource\Pages\EditCustomer',
            'view' => 'App\Filament\Admin\Resources\UltimateCustomerResource\Pages\ViewCustomer',
        ]
    ]
];

foreach ($resources as $module => $config) {
    echo "\nTesting $module module:\n";
    
    // Test resource class
    if (class_exists($config['resource'])) {
        testPassed("$module Resource class exists");
        
        // Check if resource has getPages method
        if (method_exists($config['resource'], 'getPages')) {
            $pages = $config['resource']::getPages();
            testPassed("$module Resource has getPages() method");
            
            // Verify all required pages are registered
            $requiredPages = ['index', 'create', 'edit', 'view'];
            foreach ($requiredPages as $page) {
                if (isset($pages[$page])) {
                    testPassed("$module has '$page' page registered");
                } else {
                    testFailed("$module missing '$page' page registration");
                }
            }
        } else {
            testFailed("$module Resource missing getPages() method");
        }
    } else {
        testFailed("$module Resource class not found");
    }
    
    // Test page classes
    foreach ($config['pages'] as $type => $class) {
        if (class_exists($class)) {
            testPassed("$module $type page class exists");
        } else {
            testFailed("$module $type page class not found", $class);
        }
    }
}

// Test 2: Blade View Files
echo "\n\n2️⃣ TESTING BLADE VIEW FILES\n";
echo "------------------------------\n";

$viewFiles = [
    // Call views
    'resources/views/filament/admin/pages/ultra-call-create.blade.php',
    'resources/views/filament/admin/pages/ultra-call-edit.blade.php',
    'resources/views/filament/admin/pages/ultra-call-view.blade.php',
    
    // Appointment views  
    'resources/views/filament/admin/pages/ultra-appointment-create.blade.php',
    'resources/views/filament/admin/pages/ultra-appointment-edit.blade.php',
    'resources/views/filament/admin/pages/ultra-appointment-view.blade.php',
    
    // Customer views
    'resources/views/filament/admin/pages/ultra-customer-create.blade.php',
    'resources/views/filament/admin/pages/ultra-customer-edit.blade.php',
    'resources/views/filament/admin/pages/ultra-customer-view.blade.php',
    
    // Component views
    'resources/views/filament/admin/components/sentiment-chart.blade.php',
    'resources/views/filament/admin/components/customer-call-history.blade.php',
    'resources/views/filament/admin/components/related-appointments.blade.php',
    'resources/views/filament/admin/components/call-analytics.blade.php',
    'resources/views/filament/admin/components/appointment-timeline.blade.php',
    'resources/views/filament/admin/components/customer-appointment-history.blade.php',
    'resources/views/filament/admin/components/staff-day-schedule.blade.php',
    'resources/views/filament/admin/components/customer-quick-info.blade.php',
    'resources/views/filament/admin/components/customer-analytics-dashboard.blade.php',
    'resources/views/filament/admin/components/customer-appointment-list.blade.php',
    
    // Modal views
    'resources/views/filament/modals/share-call.blade.php',
];

foreach ($viewFiles as $file) {
    if (file_exists($file)) {
        testPassed("View file exists: " . basename($file));
    } else {
        testFailed("View file missing", $file);
    }
}

// Test 3: Database Models and Relationships
echo "\n\n3️⃣ TESTING MODELS & RELATIONSHIPS\n";
echo "------------------------------------\n";

// Test Call model
try {
    $call = new Call();
    $call->company_id = $company->id;
    $call->phone_number = '+491234567890';
    $call->status = 'completed';
    $call->duration = 180;
    $call->retell_call_id = 'test_' . uniqid(); // Add required field
    $call->save();
    testPassed("Call model can be created");
    
    // Test relationships
    if ($call->company) {
        testPassed("Call->company relationship works");
    }
    
    if (method_exists($call, 'customer')) {
        testPassed("Call->customer relationship exists");
    }
    
    $call->delete();
} catch (\Exception $e) {
    testFailed("Call model creation failed", $e->getMessage());
}

// Test Customer model
try {
    $customer = new Customer();
    $customer->company_id = $company->id;
    $customer->name = 'Test Customer';
    $customer->phone = '+49 176 12345678'; // Use proper phone format
    $customer->email = 'test@example.com';
    $customer->save();
    testPassed("Customer model can be created");
    
    // Test relationships
    if (method_exists($customer, 'appointments')) {
        testPassed("Customer->appointments relationship exists");
    }
    
    if (method_exists($customer, 'calls')) {
        testPassed("Customer->calls relationship exists");
    }
    
    $customer->delete();
} catch (\Exception $e) {
    testFailed("Customer model creation failed", $e->getMessage());
}

// Test 4: UI Components in Views
echo "\n\n4️⃣ TESTING UI COMPONENTS\n";
echo "---------------------------\n";

$uiFeatures = [
    'Smart suggestions' => ['smart-suggestions', 'suggestion'],
    'Quick actions' => ['quick-action', 'quick-fill'],
    'Real-time validation' => ['validation', 'duplicate-check'],
    'Progress indicators' => ['progress', 'wizard'],
    'Analytics dashboards' => ['chart', 'analytics'],
    'Timeline views' => ['timeline', 'journey'],
    'Responsive design' => ['@media', 'grid-cols'],
    'Alpine.js interactions' => ['x-data', 'x-show', '@click'],
    'Chart.js visualizations' => ['Chart(', 'new Chart'],
];

foreach ($uiFeatures as $feature => $patterns) {
    $found = false;
    foreach ($viewFiles as $file) {
        if (file_exists($file)) {
            $content = file_get_contents($file);
            foreach ($patterns as $pattern) {
                if (stripos($content, $pattern) !== false) {
                    testPassed("$feature implemented");
                    $found = true;
                    break 2;
                }
            }
        }
    }
    if (!$found) {
        testWarning("$feature not found in any view file");
    }
}

// Test 5: JavaScript Functions
echo "\n\n5️⃣ TESTING JAVASCRIPT FUNCTIONS\n";
echo "----------------------------------\n";

$jsFunctions = [
    'calculateDuration' => 'Duration calculation for calls',
    'checkForDuplicates' => 'Duplicate customer detection',
    'selectCustomerType' => 'Customer type selection',
    'bookAppointment' => 'Quick appointment booking',
    'fillTestCustomer' => 'Test data filling',
    'showTimeSlots' => 'Time slot display',
];

foreach ($jsFunctions as $func => $description) {
    $found = false;
    foreach ($viewFiles as $file) {
        if (file_exists($file)) {
            $content = file_get_contents($file);
            if (strpos($content, "function $func") !== false || strpos($content, "$func(") !== false) {
                testPassed("$description function found");
                $found = true;
                break;
            }
        }
    }
    if (!$found) {
        testWarning("$description function not found");
    }
}

// Test 6: Security Features
echo "\n\n6️⃣ TESTING SECURITY FEATURES\n";
echo "-------------------------------\n";

// Check for multi-tenancy scope
$modelsToTest = [
    Call::class => 'Call',
    Customer::class => 'Customer',
    Appointment::class => 'Appointment',
];

foreach ($modelsToTest as $modelClass => $name) {
    try {
        $model = new $modelClass();
        if (property_exists($model, 'fillable') && is_array($model->fillable) && in_array('company_id', $model->fillable)) {
            testPassed("$name model has company_id in fillable");
        } else {
            testWarning("$name model fillable not properly configured");
        }
        
        // Check for global scope
        if (method_exists($modelClass, 'booted')) {
            testPassed("$name model has booted method for scopes");
        }
    } catch (\Exception $e) {
        testWarning("Could not test $name model: " . $e->getMessage());
    }
}

// Test 7: Performance Metrics
echo "\n\n7️⃣ TESTING PERFORMANCE\n";
echo "-------------------------\n";

// Test customer listing performance
try {
    // Ensure company context is set
    app()->instance('company_id', $company->id);
    session(['company_id' => $company->id]);
    
    $start = microtime(true);
    $customers = Customer::where('company_id', $company->id)
        ->with(['appointments'])
        ->limit(50)
        ->get();
    $customerLoadTime = round((microtime(true) - $start) * 1000, 2);
    if ($customerLoadTime < 100) {
        testPassed("Customer list loads quickly ({$customerLoadTime}ms)");
    } elseif ($customerLoadTime < 500) {
        testWarning("Customer list loads acceptably ({$customerLoadTime}ms)");
    } else {
        testFailed("Customer list loads slowly ({$customerLoadTime}ms)");
    }
} catch (\Exception $e) {
    testWarning("Could not test customer performance: " . $e->getMessage());
}

// Test appointment listing performance
try {
    $start = microtime(true);
    $appointments = Appointment::where('company_id', $company->id)
        ->with(['customer', 'service', 'staff'])
        ->whereDate('starts_at', '>=', today())
        ->limit(50)
        ->get();
    $appointmentLoadTime = round((microtime(true) - $start) * 1000, 2);
    if ($appointmentLoadTime < 100) {
        testPassed("Appointment list loads quickly ({$appointmentLoadTime}ms)");
    } elseif ($appointmentLoadTime < 500) {
        testWarning("Appointment list loads acceptably ({$appointmentLoadTime}ms)");
    } else {
        testFailed("Appointment list loads slowly ({$appointmentLoadTime}ms)");
    }
} catch (\Exception $e) {
    testWarning("Could not test appointment performance: " . $e->getMessage());
}

// Summary Report
echo "\n\n📊 TEST SUMMARY\n";
echo "================\n\n";

$totalTests = count($successes) + count($errors) + count($warnings);
$passRate = $totalTests > 0 ? round((count($successes) / $totalTests) * 100, 1) : 0;

echo "Total Tests: $totalTests\n";
echo "✅ Passed: " . count($successes) . "\n";
echo "❌ Failed: " . count($errors) . "\n";
echo "⚠️  Warnings: " . count($warnings) . "\n";
echo "Pass Rate: $passRate%\n\n";

if (count($errors) > 0) {
    echo "❌ FAILED TESTS:\n";
    echo "----------------\n";
    foreach ($errors as $error) {
        echo "• {$error['message']}\n";
        if ($error['details']) {
            echo "  Details: {$error['details']}\n";
        }
    }
    echo "\n";
}

if (count($warnings) > 0) {
    echo "⚠️  WARNINGS:\n";
    echo "------------\n";
    foreach ($warnings as $warning) {
        echo "• $warning\n";
    }
    echo "\n";
}

// Recommendations
echo "💡 RECOMMENDATIONS:\n";
echo "-------------------\n";

if ($passRate < 80) {
    echo "• Fix all failed tests before deployment\n";
} else {
    echo "• All core functionality appears to be working\n";
}

if (count($warnings) > 5) {
    echo "• Review and address warnings to improve code quality\n";
}

echo "• Run browser tests to verify UI interactions\n";
echo "• Test with real user data to ensure performance\n";
echo "• Verify responsive design on mobile devices\n";

// Test Database Cleanup
echo "\n🧹 Cleaning up test data...\n";
try {
    DB::statement('SET FOREIGN_KEY_CHECKS=0');
    Call::where('retell_call_id', 'LIKE', 'test_%')->delete();
    Customer::where('email', 'test@example.com')->delete();
    DB::statement('SET FOREIGN_KEY_CHECKS=1');
} catch (\Exception $e) {
    echo "Warning: Could not clean up test data: " . $e->getMessage() . "\n";
}

echo "\n✨ Test completed!\n\n";

// Exit with appropriate code
exit(count($errors) > 0 ? 1 : 0);