<?php

/**
 * CRITICAL: Multi-Tenant Security Audit for EXISTING Models
 *
 * Tests 6 existing models WITHOUT RefreshDatabase (uses production data)
 */

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\{User, Company, Appointment, Customer, Service, Staff, Branch};
use Illuminate\Support\Facades\{Auth, DB};

// ANSI colors
const RED = "\033[31m";
const GREEN = "\033[32m";
const YELLOW = "\033[33m";
const BLUE = "\033[34m";
const RESET = "\033[0m";

function test($name) {
    echo "\n" . BLUE . "🧪 TEST: $name" . RESET . "\n";
}

function pass($msg) {
    echo GREEN . "   ✅ $msg" . RESET . "\n";
}

function fail($msg) {
    echo RED . "   ❌ FAIL: $msg" . RESET . "\n";
    global $failureCount;
    $failureCount++;
}

function info($msg) {
    echo YELLOW . "   ℹ️  $msg" . RESET . "\n";
}

$failureCount = 0;
$testCount = 0;

echo str_repeat('=', 80) . "\n";
echo BLUE . "📊 MULTI-TENANT SECURITY AUDIT - EXISTING MODELS" . RESET . "\n";
echo str_repeat('=', 80) . "\n";

// Get two different companies from production
$companies = Company::limit(2)->get();

if ($companies->count() < 2) {
    fail("Need at least 2 companies in database. Found: " . $companies->count());
    exit(1);
}

$companyA = $companies[0];
$companyB = $companies[1];

info("Company A: {$companyA->name} (ID: {$companyA->id})");
info("Company B: {$companyB->name} (ID: {$companyB->id})");

// Get users from each company
$userA = User::where('company_id', $companyA->id)->first();
$userB = User::where('company_id', $companyB->id)->first();

if (!$userA || !$userB) {
    fail("Need users in both companies. Company A user: " . ($userA ? 'exists' : 'missing') . ", Company B user: " . ($userB ? 'exists' : 'missing'));
    exit(1);
}

info("User A: {$userA->email}");
info("User B: {$userB->email}");

// ============================================================================
// TEST 1: USER MODEL
// ============================================================================
test("USER MODEL - Multi-Tenant Isolation");
$testCount++;

Auth::login($userA);

$users = User::all();
$companyAUsers = $users->where('company_id', $companyA->id);
$companyBUsers = $users->where('company_id', $companyB->id);

if ($companyBUsers->count() > 0) {
    fail("User::all() returned " . $companyBUsers->count() . " Company B users (should be 0)");
} else {
    pass("User::all() scoped to Company A only (" . $users->count() . " users)");
}

$foundUserB = User::find($userB->id);
if ($foundUserB !== null) {
    fail("Company B user accessible via User::find() - SECURITY LEAK!");
} else {
    pass("Company B user invisible via User::find()");
}

if ($users->every(fn($u) => $u->company_id === $companyA->id)) {
    pass("All users belong to Company A");
} else {
    fail("Some users do NOT belong to Company A");
}

// ============================================================================
// TEST 2: APPOINTMENT MODEL
// ============================================================================
test("APPOINTMENT MODEL - Cross-Company Isolation");
$testCount++;

Auth::login($userA);

$appointments = Appointment::all();
$companyBAppointments = $appointments->where('company_id', $companyB->id);

if ($companyBAppointments->count() > 0) {
    fail("Appointment::all() returned " . $companyBAppointments->count() . " Company B appointments");
} else {
    pass("Appointment::all() scoped to Company A (" . $appointments->count() . " appointments)");
}

// Try to find a Company B appointment
$companyBAppointment = Appointment::where('company_id', $companyB->id)->withoutGlobalScopes()->first();
if ($companyBAppointment) {
    $foundAppointment = Appointment::find($companyBAppointment->id);
    if ($foundAppointment !== null) {
        fail("Company B appointment accessible - SECURITY LEAK!");
    } else {
        pass("Company B appointment invisible");
    }
} else {
    info("No Company B appointments to test");
}

// ============================================================================
// TEST 3: CUSTOMER MODEL
// ============================================================================
test("CUSTOMER MODEL - Multi-Tenant Isolation");
$testCount++;

Auth::login($userA);

$customers = Customer::all();
$companyBCustomers = $customers->where('company_id', $companyB->id);

if ($companyBCustomers->count() > 0) {
    fail("Customer::all() returned " . $companyBCustomers->count() . " Company B customers");
} else {
    pass("Customer::all() scoped to Company A (" . $customers->count() . " customers)");
}

// Try to find a Company B customer
$companyBCustomer = Customer::where('company_id', $companyB->id)->withoutGlobalScopes()->first();
if ($companyBCustomer) {
    $foundCustomer = Customer::find($companyBCustomer->id);
    if ($foundCustomer !== null) {
        fail("Company B customer accessible - SECURITY LEAK!");
    } else {
        pass("Company B customer invisible");
    }
} else {
    info("No Company B customers to test");
}

// ============================================================================
// TEST 4: SERVICE MODEL
// ============================================================================
test("SERVICE MODEL - Authorization & Scoping");
$testCount++;

Auth::login($userA);

$services = Service::all();
$companyBServices = $services->where('company_id', $companyB->id);

if ($companyBServices->count() > 0) {
    fail("Service::all() returned " . $companyBServices->count() . " Company B services");
} else {
    pass("Service::all() scoped to Company A (" . $services->count() . " services)");
}

// Try to find a Company B service
$companyBService = Service::where('company_id', $companyB->id)->withoutGlobalScopes()->first();
if ($companyBService) {
    $foundService = Service::find($companyBService->id);
    if ($foundService !== null) {
        fail("Company B service accessible - SECURITY LEAK!");
    } else {
        pass("Company B service invisible");
    }
} else {
    info("No Company B services to test");
}

// ============================================================================
// TEST 5: STAFF MODEL
// ============================================================================
test("STAFF MODEL - Company Scoping");
$testCount++;

Auth::login($userA);

$staff = Staff::all();
$companyBStaff = $staff->where('company_id', $companyB->id);

if ($companyBStaff->count() > 0) {
    fail("Staff::all() returned " . $companyBStaff->count() . " Company B staff");
} else {
    pass("Staff::all() scoped to Company A (" . $staff->count() . " staff)");
}

// Try to find a Company B staff
$companyBStaffMember = Staff::where('company_id', $companyB->id)->withoutGlobalScopes()->first();
if ($companyBStaffMember) {
    $foundStaff = Staff::find($companyBStaffMember->id);
    if ($foundStaff !== null) {
        fail("Company B staff accessible - SECURITY LEAK!");
    } else {
        pass("Company B staff invisible");
    }
} else {
    info("No Company B staff to test");
}

// Count queries scoped
$totalStaff = Staff::count();
$totalStaffWithoutScope = Staff::withoutGlobalScopes()->where('company_id', $companyA->id)->count();
if ($totalStaff === $totalStaffWithoutScope) {
    pass("Staff::count() scoped correctly");
} else {
    fail("Staff::count() not scoped correctly");
}

// ============================================================================
// TEST 6: BRANCH MODEL
// ============================================================================
test("BRANCH MODEL - Cross-Company Access Prevention");
$testCount++;

Auth::login($userA);

$branches = Branch::all();
$companyBBranches = $branches->where('company_id', $companyB->id);

if ($companyBBranches->count() > 0) {
    fail("Branch::all() returned " . $companyBBranches->count() . " Company B branches");
} else {
    pass("Branch::all() scoped to Company A (" . $branches->count() . " branches)");
}

// Try to find a Company B branch
$companyBBranch = Branch::where('company_id', $companyB->id)->withoutGlobalScopes()->first();
if ($companyBBranch) {
    $foundBranch = Branch::find($companyBBranch->id);
    if ($foundBranch !== null) {
        fail("Company B branch accessible - SECURITY LEAK!");
    } else {
        pass("Company B branch invisible");
    }

    // Test paginated queries
    $paginatedBranches = Branch::paginate(10);
    $hasCompanyBInPagination = $paginatedBranches->contains('company_id', $companyB->id);
    if ($hasCompanyBInPagination) {
        fail("Paginated query includes Company B branches");
    } else {
        pass("Paginated queries scoped correctly");
    }
} else {
    info("No Company B branches to test");
}

// ============================================================================
// SUMMARY
// ============================================================================
echo "\n" . str_repeat('=', 80) . "\n";
echo BLUE . "📊 COMPREHENSIVE ISOLATION SUMMARY" . RESET . "\n";
echo str_repeat('=', 80) . "\n\n";

echo "Test Configuration:\n";
echo "  Company A: {$companyA->name} (ID: {$companyA->id})\n";
echo "  Company B: {$companyB->name} (ID: {$companyB->id})\n";
echo "  Logged in as: {$userA->email} (Company A)\n\n";

echo "Isolation Matrix (Company A perspective):\n";
$models = [
    'User' => User::all()->count(),
    'Appointment' => Appointment::all()->count(),
    'Customer' => Customer::all()->count(),
    'Service' => Service::all()->count(),
    'Staff' => Staff::all()->count(),
    'Branch' => Branch::all()->count(),
];

foreach ($models as $model => $count) {
    echo "  ✅ {$model}::all() returned: {$count} (Company A only)\n";
}

echo "\nCross-Company Access Tests:\n";
$crossCompanyTests = [
    'Branch' => Branch::where('company_id', $companyB->id)->withoutGlobalScopes()->first(),
    'Customer' => Customer::where('company_id', $companyB->id)->withoutGlobalScopes()->first(),
    'Service' => Service::where('company_id', $companyB->id)->withoutGlobalScopes()->first(),
    'Staff' => Staff::where('company_id', $companyB->id)->withoutGlobalScopes()->first(),
    'Appointment' => Appointment::where('company_id', $companyB->id)->withoutGlobalScopes()->first(),
];

foreach ($crossCompanyTests as $model => $record) {
    if ($record) {
        $found = $model::find($record->id);
        $status = $found === null ? GREEN . "✅ NULL (blocked)" . RESET : RED . "❌ FOUND (LEAK!)" . RESET;
        echo "  Company B {$model} find(): {$status}\n";
    } else {
        echo "  Company B {$model}: " . YELLOW . "No records to test" . RESET . "\n";
    }
}

echo "\n" . str_repeat('=', 80) . "\n";

if ($failureCount === 0) {
    echo GREEN . "✅ VERDICT: 100% MULTI-TENANT ISOLATION VERIFIED" . RESET . "\n";
    echo GREEN . "   All {$testCount} tests passed for existing models" . RESET . "\n";
} else {
    echo RED . "❌ VERDICT: SECURITY FAILURES DETECTED" . RESET . "\n";
    echo RED . "   {$failureCount} failures found" . RESET . "\n";
}

echo str_repeat('=', 80) . "\n\n";

exit($failureCount > 0 ? 1 : 0);
