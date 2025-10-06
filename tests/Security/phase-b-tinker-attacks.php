<?php

/**
 * PHASE B - Artisan Tinker Attack Scenarios
 *
 * Run with: php artisan tinker < tests/Security/phase-b-tinker-attacks.php
 *
 * This file contains executable PHP code for testing security vulnerabilities
 * directly at the model layer using Laravel's artisan tinker interface.
 */

echo "\n========================================\n";
echo "PHASE B - TINKER ATTACK SCENARIOS\n";
echo "========================================\n\n";

// ============================================================================
// SETUP: Create test data
// ============================================================================

echo "[SETUP] Creating test companies and users...\n";

$companyAlpha = \App\Models\Company::firstOrCreate(
    ['name' => 'Test Company Alpha'],
    ['id' => 9001, 'tenant_id' => 1]
);

$companyBeta = \App\Models\Company::firstOrCreate(
    ['name' => 'Test Company Beta'],
    ['id' => 9002, 'tenant_id' => 1]
);

$adminUser = \App\Models\User::firstOrCreate(
    ['email' => 'admin@test-company-alpha.com'],
    [
        'name' => 'Admin User',
        'password' => bcrypt('password'),
        'company_id' => 9001
    ]
);

$regularUser = \App\Models\User::firstOrCreate(
    ['email' => 'user@test-company-alpha.com'],
    [
        'name' => 'Regular User',
        'password' => bcrypt('password'),
        'company_id' => 9001
    ]
);

$attackerUser = \App\Models\User::firstOrCreate(
    ['email' => 'attacker@test-company-beta.com'],
    [
        'name' => 'Malicious User',
        'password' => bcrypt('password'),
        'company_id' => 9002
    ]
);

// Assign roles
$adminUser->assignRole('admin');
$regularUser->assignRole('staff');
$attackerUser->assignRole('staff');

echo "[SETUP] Test data created successfully\n\n";

// ============================================================================
// ATTACK #1: Cross-Tenant Data Access via Direct Model Queries
// CVSS: 9.8 CRITICAL
// ============================================================================

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "ATTACK #1: Cross-Tenant Data Access via Model Queries\n";
echo "CVSS: 9.8 CRITICAL | Category: Authorization Bypass\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

\Illuminate\Support\Facades\Auth::login($attackerUser);
$currentUser = \Illuminate\Support\Facades\Auth::user();

echo "[INFO] Logged in as: {$currentUser->email} (Company ID: {$currentUser->company_id})\n";
echo "[ATTACK] Attempting to access appointments from Company Alpha (9001)...\n";

// Test 1: Direct where clause
$appointments = \App\Models\Appointment::where('company_id', 9001)->get();

if ($appointments->isEmpty()) {
    echo "[✓ SECURE] CompanyScope prevented cross-tenant access via where()\n";
    echo "[RESULT] Found 0 appointments (CompanyScope working)\n";
} else {
    echo "[✗ VULNERABLE] CompanyScope bypass detected!\n";
    echo "[RESULT] Found {$appointments->count()} appointments from Company Alpha\n";
    echo "[CRITICAL] Cross-tenant data leakage confirmed\n";
}

// Test 2: Find by ID
echo "\n[ATTACK] Attempting to find specific appointment by ID...\n";
$targetAppointment = \App\Models\Appointment::where('company_id', 9001)->first();

if ($targetAppointment && $targetAppointment->id) {
    $foundAppointment = \App\Models\Appointment::find($targetAppointment->id);

    if ($foundAppointment) {
        echo "[✗ VULNERABLE] Found appointment by ID from different company\n";
    } else {
        echo "[✓ SECURE] CompanyScope prevented find() access\n";
    }
}

// Test 3: All records
echo "\n[ATTACK] Attempting to list all appointments...\n";
$allAppointments = \App\Models\Appointment::all();

if ($allAppointments->count() > 0) {
    $hasOtherCompanyData = $allAppointments->where('company_id', '!=', $currentUser->company_id)->count() > 0;

    if ($hasOtherCompanyData) {
        echo "[✗ VULNERABLE] all() returns data from other companies\n";
    } else {
        echo "[✓ SECURE] all() only returns current company data\n";
    }
} else {
    echo "[✓ SECURE] No cross-tenant data accessible\n";
}

echo "\n";

// ============================================================================
// ATTACK #2: Privilege Escalation to Super Admin
// CVSS: 8.8 HIGH
// ============================================================================

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "ATTACK #2: Admin Role Privilege Escalation\n";
echo "CVSS: 8.8 HIGH | Category: Privilege Escalation\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

\Illuminate\Support\Facades\Auth::login($regularUser);
$currentUser = \Illuminate\Support\Facades\Auth::user();

echo "[INFO] Current user: {$currentUser->email}\n";
echo "[INFO] Current roles: " . $currentUser->getRoleNames()->implode(', ') . "\n";
echo "[ATTACK] Attempting to assign super_admin role...\n";

try {
    $currentUser->assignRole('super_admin');
    $currentUser->refresh();

    if ($currentUser->hasRole('super_admin')) {
        echo "[✗ VULNERABLE] Successfully escalated to super_admin\n";
        echo "[CRITICAL] Privilege escalation successful\n";

        // Remove the role to clean up
        $currentUser->removeRole('super_admin');
    } else {
        echo "[✓ SECURE] Role assignment did not grant super_admin privileges\n";
    }
} catch (\Exception $e) {
    echo "[✓ SECURE] Exception thrown during role assignment\n";
    echo "[INFO] Error: {$e->getMessage()}\n";
}

echo "\n[ATTACK] Attempting direct database manipulation...\n";

try {
    DB::table('model_has_roles')->insert([
        'role_id' => DB::table('roles')->where('name', 'super_admin')->value('id'),
        'model_type' => 'App\\Models\\User',
        'model_id' => $currentUser->id
    ]);

    $currentUser->refresh();

    if ($currentUser->hasRole('super_admin')) {
        echo "[✗ VULNERABLE] Direct DB manipulation granted super_admin\n";

        // Clean up
        DB::table('model_has_roles')
            ->where('model_id', $currentUser->id)
            ->where('role_id', DB::table('roles')->where('name', 'super_admin')->value('id'))
            ->delete();
    } else {
        echo "[✓ SECURE] Role cache prevented privilege escalation\n";
    }
} catch (\Exception $e) {
    echo "[✓ SECURE] Database constraint prevented escalation\n";
    echo "[INFO] Error: {$e->getMessage()}\n";
}

echo "\n";

// ============================================================================
// ATTACK #3: Mass Assignment Protection Bypass
// CVSS: 8.1 HIGH
// ============================================================================

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "ATTACK #3: Mass Assignment Protection Bypass\n";
echo "CVSS: 8.1 HIGH | Category: Input Validation\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

\Illuminate\Support\Facades\Auth::login($attackerUser);
$currentUser = \Illuminate\Support\Facades\Auth::user();

echo "[INFO] Logged in as: {$currentUser->email} (Company ID: {$currentUser->company_id})\n";
echo "[ATTACK] Attempting to create appointment with manipulated company_id...\n";

try {
    $maliciousAppointment = \App\Models\Appointment::create([
        'company_id' => 9001,  // Attempting to set different company
        'branch_id' => 1,
        'service_id' => 1,
        'customer_id' => 1,
        'staff_id' => 1,
        'starts_at' => now()->addDay(),
        'ends_at' => now()->addDay()->addHour(),
        'status' => 'pending',
        'price' => 99999.99,  // Attempting to set price directly
    ]);

    if ($maliciousAppointment->company_id == 9001) {
        echo "[✗ VULNERABLE] Mass assignment allowed company_id override\n";
        echo "[CRITICAL] Created appointment in Company Alpha from Company Beta user\n";

        // Clean up
        $maliciousAppointment->delete();
    } else {
        echo "[✓ SECURE] Mass assignment protection prevented company_id override\n";
        echo "[INFO] Appointment created with correct company_id: {$maliciousAppointment->company_id}\n";

        // Clean up
        $maliciousAppointment->delete();
    }

    if ($maliciousAppointment->price == 99999.99) {
        echo "[✗ VULNERABLE] Mass assignment allowed price manipulation\n";
    } else {
        echo "[✓ SECURE] Price field is guarded from mass assignment\n";
    }

} catch (\Exception $e) {
    echo "[✓ SECURE] Mass assignment protection working\n";
    echo "[INFO] Error: {$e->getMessage()}\n";
}

echo "\n";

// ============================================================================
// ATTACK #4: Service Cross-Company Booking
// CVSS: 8.1 HIGH
// ============================================================================

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "ATTACK #4: Cross-Company Service Booking\n";
echo "CVSS: 8.1 HIGH | Category: Authorization Bypass\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

\Illuminate\Support\Facades\Auth::login($attackerUser);
$currentUser = \Illuminate\Support\Facades\Auth::user();

echo "[INFO] Logged in as: {$currentUser->email} (Company ID: {$currentUser->company_id})\n";
echo "[ATTACK] Attempting to access services from Company Alpha...\n";

$servicesAlpha = \App\Models\Service::where('company_id', 9001)->get();

if ($servicesAlpha->isEmpty()) {
    echo "[✓ SECURE] CompanyScope prevented service access\n";
} else {
    echo "[✗ VULNERABLE] Found {$servicesAlpha->count()} services from Company Alpha\n";

    $service = $servicesAlpha->first();
    echo "[ATTACK] Attempting to book service: {$service->name}\n";

    try {
        $booking = \App\Models\Appointment::create([
            'service_id' => $service->id,
            'customer_id' => 1,
            'staff_id' => 1,
            'starts_at' => now()->addDay(),
            'ends_at' => now()->addDay()->addHour(),
            'status' => 'pending'
        ]);

        echo "[✗ VULNERABLE] Cross-company booking succeeded\n";
        echo "[CRITICAL] Appointment ID: {$booking->id}\n";

        // Clean up
        $booking->delete();

    } catch (\Exception $e) {
        echo "[✓ SECURE] Cross-company booking prevented\n";
        echo "[INFO] Error: {$e->getMessage()}\n";
    }
}

echo "\n";

// ============================================================================
// ATTACK #5: Policy Authorization Bypass
// CVSS: 8.8 HIGH
// ============================================================================

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "ATTACK #5: Authorization Policy Bypass\n";
echo "CVSS: 8.8 HIGH | Category: Authorization\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

\Illuminate\Support\Facades\Auth::login($regularUser);
$currentUser = \Illuminate\Support\Facades\Auth::user();

echo "[INFO] Current user: {$currentUser->email}\n";
echo "[INFO] Current roles: " . $currentUser->getRoleNames()->implode(', ') . "\n";

$appointment = \App\Models\Appointment::where('company_id', 9001)->first();

if ($appointment) {
    echo "[ATTACK] Testing authorization policies on appointment {$appointment->id}...\n\n";

    // Test forceDelete (super_admin only)
    $canForceDelete = $currentUser->can('forceDelete', $appointment);
    echo "[TEST] forceDelete: ";
    if ($canForceDelete) {
        echo "[✗ FAIL] Regular user can forceDelete\n";
    } else {
        echo "[✓ PASS] Correctly denied\n";
    }

    // Test view
    $canView = $currentUser->can('view', $appointment);
    echo "[TEST] view: ";
    if ($canView) {
        echo "[✓ PASS] Can view own company's appointments\n";
    } else {
        echo "[✗ FAIL] Cannot view own company's appointments\n";
    }

    // Test update
    $canUpdate = $currentUser->can('update', $appointment);
    echo "[TEST] update: ";
    if ($canUpdate && !$currentUser->hasRole('admin')) {
        echo "[WARNING] Staff can update appointments\n";
    } else {
        echo "[INFO] Update permission: " . ($canUpdate ? 'granted' : 'denied') . "\n";
    }

    // Test delete
    $canDelete = $currentUser->can('delete', $appointment);
    echo "[TEST] delete: ";
    if ($canDelete && !$currentUser->hasAnyRole(['admin', 'manager'])) {
        echo "[✗ FAIL] Staff can delete appointments\n";
    } else {
        echo "[✓ PASS] Delete properly restricted\n";
    }

} else {
    echo "[INFO] No appointments found for policy testing\n";
}

echo "\n";

// ============================================================================
// ATTACK #6: CompanyScope Bypass via Raw Queries
// CVSS: 9.1 CRITICAL
// ============================================================================

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "ATTACK #6: CompanyScope Bypass via Raw Queries\n";
echo "CVSS: 9.1 CRITICAL | Category: Authorization Bypass\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

\Illuminate\Support\Facades\Auth::login($attackerUser);
$currentUser = \Illuminate\Support\Facades\Auth::user();

echo "[INFO] Logged in as Company ID: {$currentUser->company_id}\n";
echo "[ATTACK] Testing various query methods to bypass CompanyScope...\n\n";

// Test 1: DB::table() - bypasses Eloquent scopes
echo "[TEST 1] Using DB::table() to bypass Eloquent scopes...\n";
$tableCount = DB::table('appointments')->where('company_id', 9001)->count();
echo "[RESULT] Found {$tableCount} appointments via DB::table()\n";
if ($tableCount > 0) {
    echo "[WARNING] DB::table() bypasses CompanyScope (expected behavior)\n";
    echo "[NOTE] Application code must manually filter by company_id in raw queries\n";
}

// Test 2: Raw SQL query
echo "\n[TEST 2] Using raw SQL query...\n";
$rawResults = DB::select('SELECT COUNT(*) as count FROM appointments WHERE company_id = 9001');
$rawCount = $rawResults[0]->count ?? 0;
echo "[RESULT] Found {$rawCount} appointments via raw SQL\n";
if ($rawCount > 0) {
    echo "[WARNING] Raw SQL bypasses CompanyScope (expected behavior)\n";
}

// Test 3: withoutGlobalScope
echo "\n[TEST 3] Using withoutGlobalScope()...\n";
try {
    $scopelessCount = \App\Models\Appointment::withoutGlobalScope(\App\Scopes\CompanyScope::class)
        ->where('company_id', 9001)
        ->count();
    echo "[RESULT] Found {$scopelessCount} appointments without scope\n";
    echo "[✗ VULNERABLE] Users can manually remove CompanyScope\n";
} catch (\Exception $e) {
    echo "[✓ SECURE] Prevented scope bypass\n";
    echo "[INFO] Error: {$e->getMessage()}\n";
}

// Test 4: allCompanies() macro
echo "\n[TEST 4] Using allCompanies() macro...\n";
try {
    $allCompaniesCount = \App\Models\Appointment::allCompanies()
        ->where('company_id', 9001)
        ->count();
    echo "[RESULT] Found {$allCompaniesCount} appointments with allCompanies()\n";
    echo "[WARNING] allCompanies() macro allows scope bypass\n";
} catch (\Exception $e) {
    echo "[✓ SECURE] Macro not available or protected\n";
    echo "[INFO] Error: {$e->getMessage()}\n";
}

echo "\n[ANALYSIS] CompanyScope protection summary:\n";
echo "  ✓ Eloquent queries: PROTECTED\n";
echo "  ✗ DB::table(): NOT PROTECTED (manual filtering required)\n";
echo "  ✗ Raw SQL: NOT PROTECTED (manual filtering required)\n";
echo "  ? withoutGlobalScope(): Depends on authorization checks\n";

echo "\n";

// ============================================================================
// ATTACK #7: XSS via Model Attributes
// CVSS: 6.1 MEDIUM
// ============================================================================

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "ATTACK #7: XSS Injection via Model Attributes\n";
echo "CVSS: 6.1 MEDIUM | Category: Cross-Site Scripting\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

\Illuminate\Support\Facades\Auth::login($attackerUser);

echo "[ATTACK] Attempting to inject XSS payload in appointment notes...\n";

$xssPayload = '<script>alert("XSS")</script>';
$imgPayload = '<img src=x onerror=alert(1)>';

try {
    $xssTest = \App\Models\Appointment::create([
        'service_id' => 1,
        'customer_id' => 1,
        'staff_id' => 1,
        'starts_at' => now()->addDay(),
        'ends_at' => now()->addDay()->addHour(),
        'status' => 'pending',
        'notes' => $xssPayload,
        'metadata' => [
            'description' => $imgPayload,
            'malicious' => '<svg onload=alert(1)>'
        ]
    ]);

    echo "[INFO] XSS test appointment created\n";

    // Check if payload is stored without sanitization
    $storedNotes = $xssTest->notes;
    $storedMetadata = $xssTest->metadata;

    if (strpos($storedNotes, '<script>') !== false) {
        echo "[✗ VULNERABLE] Script tag stored without sanitization\n";
        echo "[CRITICAL] Stored XSS vulnerability confirmed\n";
    } else {
        echo "[✓ SECURE] Script tag sanitized or escaped\n";
    }

    if (isset($storedMetadata['description']) && strpos($storedMetadata['description'], '<img') !== false) {
        echo "[✗ VULNERABLE] HTML in metadata stored without sanitization\n";
    } else {
        echo "[✓ SECURE] Metadata HTML sanitized\n";
    }

    // Clean up
    $xssTest->delete();

} catch (\Exception $e) {
    echo "[✓ SECURE] Input validation prevented XSS\n";
    echo "[INFO] Error: {$e->getMessage()}\n";
}

echo "\n";

// ============================================================================
// CLEANUP
// ============================================================================

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "CLEANUP PHASE\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

echo "[CLEANUP] Removing test data...\n";

\App\Models\User::whereIn('email', [
    'admin@test-company-alpha.com',
    'user@test-company-alpha.com',
    'attacker@test-company-beta.com'
])->delete();

\App\Models\Company::whereIn('id', [9001, 9002])->delete();

echo "[CLEANUP] Test data removed successfully\n";

echo "\n========================================\n";
echo "PENETRATION TESTING COMPLETE\n";
echo "========================================\n\n";

echo "Review the output above for vulnerability findings.\n";
echo "Look for [✗ VULNERABLE] and [✗ FAIL] markers.\n\n";
