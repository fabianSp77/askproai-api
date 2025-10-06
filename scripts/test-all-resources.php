<?php

use App\Models\Customer;
use App\Models\Appointment;
use App\Models\Call;
use App\Models\Company;
use App\Models\Branch;
use App\Models\Service;
use App\Models\Staff;
use App\Models\WorkingHour;

echo "\n=========================================\n";
echo " Testing All Filament Resources\n";
echo " " . date('Y-m-d H:i:s') . "\n";
echo "=========================================\n";

$results = [];
$errors = [];

// Test CRM Resources
echo "\n=== Testing CRM Resources ===\n";

// Test Customer Resource
try {
    $count = Customer::count();
    $test = Customer::with(['appointments', 'calls', 'notes'])->limit(1)->get();
    echo "✅ CustomerResource: OK ($count records)\n";
    $results['CustomerResource'] = 'PASS';
} catch (Exception $e) {
    echo "❌ CustomerResource: " . $e->getMessage() . "\n";
    $errors['CustomerResource'] = $e->getMessage();
    $results['CustomerResource'] = 'FAIL';
}

// Test Appointment Resource
try {
    $count = Appointment::count();
    $test = Appointment::with(['customer', 'staff', 'service'])->limit(1)->get();
    echo "✅ AppointmentResource: OK ($count records)\n";
    $results['AppointmentResource'] = 'PASS';
} catch (Exception $e) {
    echo "❌ AppointmentResource: " . $e->getMessage() . "\n";
    $errors['AppointmentResource'] = $e->getMessage();
    $results['AppointmentResource'] = 'FAIL';
}

// Test Call Resource
try {
    $count = Call::count();
    $test = Call::with(['customer', 'company'])->limit(1)->get();
    echo "✅ CallResource: OK ($count records)\n";
    $results['CallResource'] = 'PASS';
} catch (Exception $e) {
    echo "❌ CallResource: " . $e->getMessage() . "\n";
    $errors['CallResource'] = $e->getMessage();
    $results['CallResource'] = 'FAIL';
}

// Test Stammdaten Resources
echo "\n=== Testing Stammdaten Resources ===\n";

// Test Company Resource
try {
    $count = Company::count();
    $test = Company::with(['branches', 'staff', 'services'])->limit(1)->get();
    echo "✅ CompanyResource: OK ($count records)\n";
    $results['CompanyResource'] = 'PASS';
} catch (Exception $e) {
    echo "❌ CompanyResource: " . $e->getMessage() . "\n";
    $errors['CompanyResource'] = $e->getMessage();
    $results['CompanyResource'] = 'FAIL';
}

// Test Branch Resource
try {
    $count = Branch::count();
    $test = Branch::with(['company', 'staff', 'services'])->limit(1)->get();
    echo "✅ BranchResource: OK ($count records)\n";
    $results['BranchResource'] = 'PASS';
} catch (Exception $e) {
    echo "❌ BranchResource: " . $e->getMessage() . "\n";
    $errors['BranchResource'] = $e->getMessage();
    $results['BranchResource'] = 'FAIL';
}

// Test Service Resource
try {
    $count = Service::count();
    $active = Service::where('is_active', true)->count();
    $test = Service::with(['company', 'branch', 'appointments', 'staff'])->limit(1)->get();
    echo "✅ ServiceResource: OK ($count records, $active active)\n";
    $results['ServiceResource'] = 'PASS';
} catch (Exception $e) {
    echo "❌ ServiceResource: " . $e->getMessage() . "\n";
    $errors['ServiceResource'] = $e->getMessage();
    $results['ServiceResource'] = 'FAIL';
}

// Test Staff Resource
try {
    $count = Staff::count();
    $test = Staff::with(['company', 'branch', 'workingHours', 'services'])->limit(1)->get();
    echo "✅ StaffResource: OK ($count records)\n";
    $results['StaffResource'] = 'PASS';
} catch (Exception $e) {
    echo "❌ StaffResource: " . $e->getMessage() . "\n";
    $errors['StaffResource'] = $e->getMessage();
    $results['StaffResource'] = 'FAIL';
}

// Test WorkingHour Resource
try {
    $count = WorkingHour::count();
    $active = WorkingHour::where('is_active', true)->count();
    $test = WorkingHour::with(['staff', 'company', 'branch'])->limit(1)->get();
    echo "✅ WorkingHourResource: OK ($count records, $active active)\n";
    $results['WorkingHourResource'] = 'PASS';
} catch (Exception $e) {
    echo "❌ WorkingHourResource: " . $e->getMessage() . "\n";
    $errors['WorkingHourResource'] = $e->getMessage();
    $results['WorkingHourResource'] = 'FAIL';
}

// Test specific problematic queries
echo "\n=== Testing Specific Queries ===\n";

// Test navigation badge queries
try {
    $serviceCount = Service::where('is_active', true)->count();
    echo "✅ Service navigation badge: $serviceCount active services\n";
    $results['Service_Navigation'] = 'PASS';
} catch (Exception $e) {
    echo "❌ Service navigation badge: " . $e->getMessage() . "\n";
    $errors['Service_Navigation'] = $e->getMessage();
    $results['Service_Navigation'] = 'FAIL';
}

try {
    $workingHourCount = WorkingHour::where('is_active', true)->count();
    echo "✅ WorkingHour navigation badge: $workingHourCount active hours\n";
    $results['WorkingHour_Navigation'] = 'PASS';
} catch (Exception $e) {
    echo "❌ WorkingHour navigation badge: " . $e->getMessage() . "\n";
    $errors['WorkingHour_Navigation'] = $e->getMessage();
    $results['WorkingHour_Navigation'] = 'FAIL';
}

// Summary
echo "\n=========================================\n";
echo " Test Summary\n";
echo "=========================================\n";

$passed = array_filter($results, fn($r) => $r === 'PASS');
$failed = array_filter($results, fn($r) => $r === 'FAIL');

echo "Total Tests: " . count($results) . "\n";
echo "✅ Passed: " . count($passed) . "\n";
echo "❌ Failed: " . count($failed) . "\n";

if (count($failed) > 0) {
    echo "\nFailed Tests:\n";
    foreach ($failed as $resource => $status) {
        echo "  - $resource: " . ($errors[$resource] ?? 'Unknown error') . "\n";
    }
    exit(1);
} else {
    echo "\n🎉 All tests passed successfully!\n";
    exit(0);
}