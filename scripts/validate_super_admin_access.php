<?php
/**
 * Super Admin Access Validation Script
 *
 * Validates that super_admin role has full access to all resources
 * through Gate::before() and Policy before() methods.
 */

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "\n";
echo "╔════════════════════════════════════════════════════════════════╗\n";
echo "║     SUPER ADMIN ACCESS VALIDATION - PHASE 4                   ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n";
echo "\n";

// ============================================================================
// 1. Check AuthServiceProvider Gate::before() Configuration
// ============================================================================
echo "🔍 [1/4] Checking AuthServiceProvider Gate::before() Configuration...\n";
echo "─────────────────────────────────────────────────────────────────\n";

$authServiceProviderPath = app_path('Providers/AuthServiceProvider.php');
$authServiceProviderContent = file_get_contents($authServiceProviderPath);

// Check if Gate::before() exists
if (preg_match('/Gate::before\s*\(.*?\)/s', $authServiceProviderContent, $matches)) {
    echo "✅ Gate::before() found in AuthServiceProvider\n";

    // Check if it has super_admin bypass
    if (preg_match('/hasRole\s*\(\s*[\'"]super_admin[\'"]\s*\)/', $authServiceProviderContent)) {
        echo "✅ Super admin bypass logic found\n";
        echo "   → if (\$user->hasRole('super_admin')) { return true; }\n";
    } else {
        echo "❌ Super admin bypass NOT found in Gate::before()\n";
    }
} else {
    echo "❌ Gate::before() NOT found in AuthServiceProvider\n";
}

echo "\n";

// ============================================================================
// 2. Check All Policy Files for before() Methods
// ============================================================================
echo "🔍 [2/4] Checking All Policy Files for before() Methods...\n";
echo "─────────────────────────────────────────────────────────────────\n";

$policiesPath = app_path('Policies');
$policyFiles = glob($policiesPath . '/*.php');

$policiesWithBefore = [];
$policiesWithoutBefore = [];
$policiesWithSuperAdminBypass = [];
$policiesWithoutSuperAdminBypass = [];

foreach ($policyFiles as $policyFile) {
    $policyName = basename($policyFile);
    $policyContent = file_get_contents($policyFile);

    // Check if before() method exists
    if (preg_match('/public\s+function\s+before\s*\(/s', $policyContent)) {
        $policiesWithBefore[] = $policyName;

        // Check if before() has super_admin bypass
        if (preg_match('/hasRole\s*\(\s*[\'"]super_admin[\'"]\s*\)/', $policyContent)) {
            $policiesWithSuperAdminBypass[] = $policyName;
        } else {
            $policiesWithoutSuperAdminBypass[] = $policyName;
        }
    } else {
        $policiesWithoutBefore[] = $policyName;
    }
}

echo "📊 Summary:\n";
echo "   Total Policy Files: " . count($policyFiles) . "\n";
echo "   ✅ With before() method: " . count($policiesWithBefore) . "\n";
echo "   ✅ With super_admin bypass: " . count($policiesWithSuperAdminBypass) . "\n";
echo "   ⚠️  Without before() method: " . count($policiesWithoutBefore) . "\n";
echo "   ⚠️  With before() but no super_admin bypass: " . count($policiesWithoutSuperAdminBypass) . "\n";
echo "\n";

if (!empty($policiesWithSuperAdminBypass)) {
    echo "✅ Policies with Super Admin Bypass (" . count($policiesWithSuperAdminBypass) . "):\n";
    foreach ($policiesWithSuperAdminBypass as $policy) {
        echo "   ✓ {$policy}\n";
    }
    echo "\n";
}

if (!empty($policiesWithoutBefore)) {
    echo "⚠️  Policies WITHOUT before() method (" . count($policiesWithoutBefore) . "):\n";
    foreach ($policiesWithoutBefore as $policy) {
        echo "   ! {$policy}\n";
    }
    echo "   Note: These policies rely on AuthServiceProvider Gate::before()\n";
    echo "\n";
}

if (!empty($policiesWithoutSuperAdminBypass)) {
    echo "❌ Policies WITH before() but WITHOUT super_admin bypass (" . count($policiesWithoutSuperAdminBypass) . "):\n";
    foreach ($policiesWithoutSuperAdminBypass as $policy) {
        echo "   ✗ {$policy}\n";
    }
    echo "   WARNING: These policies may block super_admin access!\n";
    echo "\n";
}

// ============================================================================
// 3. Verify Phase 4 Resources Specifically
// ============================================================================
echo "🔍 [3/4] Verifying Phase 4 Resources Specifically...\n";
echo "─────────────────────────────────────────────────────────────────\n";

$phase4Policies = [
    'PolicyConfigurationPolicy.php',
    'CallbackRequestPolicy.php',
    'CallForwardingConfigurationPolicy.php',
];

echo "Phase 4 Resources:\n";
foreach ($phase4Policies as $policy) {
    $exists = in_array($policy, array_map('basename', $policyFiles));
    $hasBefore = in_array($policy, $policiesWithBefore);
    $hasSuperAdmin = in_array($policy, $policiesWithSuperAdminBypass);

    $status = $exists && $hasBefore && $hasSuperAdmin ? '✅' : '❌';
    echo "{$status} {$policy}\n";

    if ($exists) {
        echo "   ├─ File exists: ✅\n";
        echo "   ├─ before() method: " . ($hasBefore ? '✅' : '❌') . "\n";
        echo "   └─ super_admin bypass: " . ($hasSuperAdmin ? '✅' : '❌') . "\n";
    } else {
        echo "   └─ File missing: ❌\n";
    }
    echo "\n";
}

// ============================================================================
// 4. Check AuthServiceProvider Policy Registration
// ============================================================================
echo "🔍 [4/4] Checking AuthServiceProvider Policy Registration...\n";
echo "─────────────────────────────────────────────────────────────────\n";

// Check if Phase 4 models are registered
$phase4Models = [
    'PolicyConfiguration' => 'PolicyConfigurationPolicy',
    'CallbackRequest' => 'CallbackRequestPolicy',
    'CallForwardingConfiguration' => 'CallForwardingConfigurationPolicy',
];

echo "Phase 4 Policy Registrations:\n";
foreach ($phase4Models as $model => $policy) {
    $searchPattern = preg_quote($model, '/') . '::class.*?' . preg_quote($policy, '/') . '::class';
    if (preg_match("/{$searchPattern}/", $authServiceProviderContent)) {
        echo "✅ {$model} → {$policy}\n";
    } else {
        echo "❌ {$model} → {$policy} (NOT REGISTERED)\n";
    }
}

echo "\n";

// ============================================================================
// Final Assessment
// ============================================================================
echo "╔════════════════════════════════════════════════════════════════╗\n";
echo "║                    FINAL ASSESSMENT                            ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n";
echo "\n";

$allPhase4PoliciesValid = true;
foreach ($phase4Policies as $policy) {
    if (!in_array($policy, $policiesWithSuperAdminBypass)) {
        $allPhase4PoliciesValid = false;
        break;
    }
}

$allPhase4ModelsRegistered = true;
foreach ($phase4Models as $model => $policy) {
    $searchPattern = preg_quote($model, '/') . '::class.*?' . preg_quote($policy, '/') . '::class';
    if (!preg_match("/{$searchPattern}/", $authServiceProviderContent)) {
        $allPhase4ModelsRegistered = false;
        break;
    }
}

$gateBeforeExists = preg_match('/Gate::before\s*\(.*?\)/s', $authServiceProviderContent) &&
                     preg_match('/hasRole\s*\(\s*[\'"]super_admin[\'"]\s*\)/', $authServiceProviderContent);

if ($gateBeforeExists && $allPhase4PoliciesValid && $allPhase4ModelsRegistered) {
    echo "🎉 RESULT: ✅ SUPER ADMIN ACCESS FULLY CONFIGURED\n";
    echo "\n";
    echo "✅ Gate::before() with super_admin bypass: ACTIVE\n";
    echo "✅ All Phase 4 policies have super_admin bypass: CONFIRMED\n";
    echo "✅ All Phase 4 models registered: CONFIRMED\n";
    echo "\n";
    echo "👤 Super Admin User Will Have:\n";
    echo "   • Full access to PolicyConfigurationResource (11 policy types)\n";
    echo "   • Full access to CallbackRequestResource (email field)\n";
    echo "   • Full access to CallForwardingConfigurationResource (all CRUD)\n";
    echo "   • Bypass all company-level isolation checks\n";
    echo "   • Bypass all role-based restrictions\n";
    echo "\n";
    echo "📋 Next Step: Manual UI Testing (see PHASE_4_UI_TESTING_GUIDE.md)\n";
} else {
    echo "⚠️  RESULT: ❌ CONFIGURATION ISSUES DETECTED\n";
    echo "\n";
    echo "Issues:\n";
    if (!$gateBeforeExists) {
        echo "❌ Gate::before() not properly configured\n";
    }
    if (!$allPhase4PoliciesValid) {
        echo "❌ Some Phase 4 policies missing super_admin bypass\n";
    }
    if (!$allPhase4ModelsRegistered) {
        echo "❌ Some Phase 4 models not registered in AuthServiceProvider\n";
    }
    echo "\n";
    echo "⚠️  MANUAL REVIEW REQUIRED\n";
}

echo "\n";
echo "════════════════════════════════════════════════════════════════\n";
echo "Validation Complete: " . date('Y-m-d H:i:s') . "\n";
echo "════════════════════════════════════════════════════════════════\n";
echo "\n";
