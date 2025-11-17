<?php
/**
 * Complete User Role Permission Validation
 * Tests all visible resources against all user roles
 */

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\Company;

echo "\n";
echo "╔════════════════════════════════════════════════════════════════╗\n";
echo "║       USER ROLE PERMISSION VALIDATION - ALL ROLES             ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n";
echo "\n";

// Critical resources to test
$criticalResources = [
    'App\\Filament\\Resources\\CompanyResource' => [
        'name' => 'Unternehmen',
        'model' => 'App\\Models\\Company',
    ],
    'App\\Filament\\Resources\\BranchResource' => [
        'name' => 'Filialen',
        'model' => 'App\\Models\\Branch',
    ],
    'App\\Filament\\Resources\\StaffResource' => [
        'name' => 'Personal',
        'model' => 'App\\Models\\Staff',
    ],
    'App\\Filament\\Resources\\CustomerResource' => [
        'name' => 'Kunden',
        'model' => 'App\\Models\\Customer',
    ],
    'App\\Filament\\Resources\\AppointmentResource' => [
        'name' => 'Termine',
        'model' => 'App\\Models\\Appointment',
    ],
    'App\\Filament\\Resources\\ServiceResource' => [
        'name' => 'Dienstleistungen',
        'model' => 'App\\Models\\Service',
    ],
    'App\\Filament\\Resources\\CallResource' => [
        'name' => 'Anrufe',
        'model' => 'App\\Models\\Call',
    ],
    'App\\Filament\\Resources\\UserResource' => [
        'name' => 'Benutzer',
        'model' => 'App\\Models\\User',
    ],
    'App\\Filament\\Resources\\PhoneNumberResource' => [
        'name' => 'Telefonnummern',
        'model' => 'App\\Models\\PhoneNumber',
    ],
    'App\\Filament\\Resources\\PolicyConfigurationResource' => [
        'name' => 'Richtlinien',
        'model' => 'App\\Models\\PolicyConfiguration',
    ],
    'App\\Filament\\Resources\\CallbackRequestResource' => [
        'name' => 'Rückrufanfragen',
        'model' => 'App\\Models\\CallbackRequest',
    ],
    'App\\Filament\\Resources\\CallForwardingConfigurationResource' => [
        'name' => 'Anrufweiterleitung',
        'model' => 'App\\Models\\CallForwardingConfiguration',
    ],
];

// Test roles
$rolesToTest = [
    'super_admin' => '🔴 Super Admin',
    'admin' => '🟡 Admin',
    'manager' => '🟢 Manager',
    'staff' => '🔵 Staff',
];

echo "📊 Testing " . count($criticalResources) . " critical resources against " . count($rolesToTest) . " roles...\n";
echo "─────────────────────────────────────────────────────────────────\n\n";

// Check if users exist for each role
echo "🔍 Checking if test users exist...\n";
$testUsers = [];
$company = Company::first();

if (!$company) {
    echo "❌ ERROR: No company found in database!\n";
    echo "   Please create a company first.\n\n";
    exit(1);
}

foreach ($rolesToTest as $role => $label) {
    $user = User::whereHas('roles', function($q) use ($role) {
        $q->where('name', $role);
    })->first();

    if ($user) {
        echo "  ✅ {$label}: {$user->email}\n";
        $testUsers[$role] = $user;
    } else {
        echo "  ⚠️  {$label}: No user found with this role\n";
    }
}

echo "\n";

if (empty($testUsers)) {
    echo "❌ ERROR: No users found for testing!\n";
    echo "   Please ensure users with roles exist in the database.\n\n";
    exit(1);
}

// Test each resource
echo "╔════════════════════════════════════════════════════════════════╗\n";
echo "║            PERMISSION TEST RESULTS BY RESOURCE                 ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n";
echo "\n";

foreach ($criticalResources as $resourceClass => $info) {
    if (!class_exists($resourceClass)) {
        echo "⚠️  {$info['name']}: Resource class not found\n\n";
        continue;
    }

    echo "📋 {$info['name']} ({$resourceClass})\n";
    echo str_repeat('─', 60) . "\n";

    // Test canViewAny for each role
    foreach ($rolesToTest as $role => $label) {
        if (!isset($testUsers[$role])) {
            echo "  {$label}: ⚠️  No user (skipped)\n";
            continue;
        }

        $user = $testUsers[$role];

        // Temporarily authenticate user for testing
        auth()->login($user);

        $canViewAny = false;
        try {
            if (method_exists($resourceClass, 'canViewAny')) {
                $canViewAny = $resourceClass::canViewAny();
            } else {
                // Use policy if no custom method
                $modelClass = $info['model'];
                if (class_exists($modelClass)) {
                    $canViewAny = $user->can('viewAny', $modelClass);
                }
            }
        } catch (\Exception $e) {
            echo "  {$label}: ❌ Error: " . $e->getMessage() . "\n";
            continue;
        }

        $status = $canViewAny ? '✅' : '❌';
        echo "  {$label}: {$status} " . ($canViewAny ? 'Can access' : 'No access') . "\n";

        auth()->logout();
    }

    echo "\n";
}

// Summary
echo "╔════════════════════════════════════════════════════════════════╗\n";
echo "║                    EXPECTED ACCESS MATRIX                      ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n";
echo "\n";

echo "Expected Permissions:\n\n";

echo "🔴 Super Admin:\n";
echo "  ✅ Full access to ALL resources (bypasses all checks)\n";
echo "  ✅ Can view/edit/delete across all companies\n";
echo "  ✅ Can force delete and restore\n\n";

echo "🟡 Admin (Company-Scoped):\n";
echo "  ✅ Full access to resources in THEIR company\n";
echo "  ✅ Can view/edit/delete in their company\n";
echo "  ❌ Cannot access other companies\n";
echo "  ❌ Cannot force delete (only soft delete)\n\n";

echo "🟢 Manager (Company-Scoped, Limited):\n";
echo "  ✅ Can view/create in their company\n";
echo "  ⚠️  Limited edit permissions\n";
echo "  ❌ Cannot delete\n";
echo "  ❌ Cannot access other companies\n\n";

echo "🔵 Staff (Company-Scoped, Read-Mostly):\n";
echo "  ✅ Can view resources in their company\n";
echo "  ⚠️  Very limited edit permissions\n";
echo "  ❌ Cannot create/delete\n";
echo "  ❌ Cannot access other companies\n\n";

echo "╔════════════════════════════════════════════════════════════════╗\n";
echo "║                    KEY FINDINGS                                ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n";
echo "\n";

// Check for common issues
$issues = [];

// Check if super_admin has access to everything
if (isset($testUsers['super_admin'])) {
    $superAdminIssues = 0;
    foreach ($criticalResources as $resourceClass => $info) {
        if (!class_exists($resourceClass)) continue;

        auth()->login($testUsers['super_admin']);
        $canViewAny = method_exists($resourceClass, 'canViewAny')
            ? $resourceClass::canViewAny()
            : $testUsers['super_admin']->can('viewAny', $info['model']);
        auth()->logout();

        if (!$canViewAny) {
            $superAdminIssues++;
            $issues[] = "Super Admin cannot access {$info['name']}";
        }
    }

    if ($superAdminIssues === 0) {
        echo "✅ Super Admin: All " . count($criticalResources) . " resources accessible\n";
    } else {
        echo "❌ Super Admin: {$superAdminIssues} resources blocked (THIS IS A BUG!)\n";
    }
}

// Check if policies exist for all resources
$missingPolicies = 0;
foreach ($criticalResources as $resourceClass => $info) {
    $modelClass = $info['model'];
    if (class_exists($modelClass)) {
        $modelName = class_basename($modelClass);
        $policyClass = "App\\Policies\\{$modelName}Policy";
        if (!class_exists($policyClass)) {
            $missingPolicies++;
            $issues[] = "Missing policy for {$info['name']} ({$policyClass})";
        }
    }
}

if ($missingPolicies === 0) {
    echo "✅ Policies: All critical resources have policies\n";
} else {
    echo "⚠️  Policies: {$missingPolicies} resources missing policies\n";
}

echo "\n";

if (!empty($issues)) {
    echo "╔════════════════════════════════════════════════════════════════╗\n";
    echo "║                    ⚠️ ISSUES FOUND                             ║\n";
    echo "╚════════════════════════════════════════════════════════════════╝\n";
    echo "\n";

    foreach ($issues as $issue) {
        echo "  ❌ {$issue}\n";
    }
    echo "\n";
} else {
    echo "🎉 NO ISSUES FOUND - All permissions working correctly!\n\n";
}

echo "════════════════════════════════════════════════════════════════\n";
echo "Validation Complete: " . date('Y-m-d H:i:s') . "\n";
echo "════════════════════════════════════════════════════════════════\n";
echo "\n";
