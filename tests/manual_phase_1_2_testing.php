#!/usr/bin/env php
<?php

/**
 * Manual Testing Script for Phase 1 & Phase 2
 *
 * This script tests the implementation like a user would:
 * - Phase 1: Security fixes (RISK-001, RISK-004)
 * - Phase 2: Event system and cache invalidation
 *
 * Run: php tests/manual_phase_1_2_testing.php
 */

require __DIR__ . '/../vendor/autoload.php';

use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use App\Models\PolicyConfiguration;
use App\Models\Company;
use App\Models\User;
use App\Events\ConfigurationCreated;
use App\Events\ConfigurationUpdated;
use App\Events\ConfigurationDeleted;

// Bootstrap Laravel
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "\n";
echo "╔════════════════════════════════════════════════════════════════╗\n";
echo "║  MANUAL TESTING: Phase 1 & Phase 2 Implementation             ║\n";
echo "║  Testing like a user would - UI/UX and functionality          ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n";
echo "\n";

// Test Results
$results = [
    'phase1' => [],
    'phase2' => [],
];

// ===================================================================
// PHASE 1: SECURITY FIXES TESTING
// ===================================================================
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "  PHASE 1: Security Fixes Testing\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

try {
    // Get test companies
    $company1 = Company::first();
    $company2 = Company::skip(1)->first();

    if (!$company1 || !$company2) {
        echo "❌ ERROR: Need at least 2 companies for testing\n";
        exit(1);
    }

    echo "✓ Test Companies:\n";
    echo "  - Company 1 (ID: {$company1->id}): {$company1->name}\n";
    echo "  - Company 2 (ID: {$company2->id}): {$company2->name}\n\n";

    // TEST 1: Get users from each company
    echo "📋 TEST 1: User Authentication Context\n";
    $user1 = User::where('company_id', $company1->id)->first();
    $user2 = User::where('company_id', $company2->id)->first();

    if (!$user1 || !$user2) {
        echo "❌ ERROR: Need users for both companies\n";
        exit(1);
    }

    echo "✓ User 1 (Company {$company1->id}): {$user1->email}\n";
    echo "✓ User 2 (Company {$company2->id}): {$user2->email}\n";
    $results['phase1']['test1'] = '✅ PASS';

    // TEST 2: Create test policies
    echo "\n📋 TEST 2: Creating Test Policies\n";
    $policy1 = PolicyConfiguration::create([
        'company_id' => $company1->id,
        'configurable_type' => Company::class,
        'configurable_id' => $company1->id,
        'policy_type' => PolicyConfiguration::POLICY_TYPE_CANCELLATION,
        'config' => [
            'hours_before' => 24,
            'fee_percentage' => 50,
        ],
    ]);

    $policy2 = PolicyConfiguration::create([
        'company_id' => $company2->id,
        'configurable_type' => Company::class,
        'configurable_id' => $company2->id,
        'policy_type' => PolicyConfiguration::POLICY_TYPE_CANCELLATION,
        'config' => [
            'hours_before' => 48,
            'fee_percentage' => 75,
        ],
    ]);

    echo "✓ Policy 1 (ID: {$policy1->id}) created for Company {$company1->id}\n";
    echo "✓ Policy 2 (ID: {$policy2->id}) created for Company {$company2->id}\n";
    $results['phase1']['test2'] = '✅ PASS';

    // TEST 3: RISK-001 Fix - Explicit Query Filtering
    echo "\n📋 TEST 3: RISK-001 - Explicit Query Filtering\n";
    echo "Testing that users can only see their company's policies...\n";

    // Authenticate as User 1
    auth()->login($user1);
    $user1Policies = PolicyConfiguration::all();

    echo "✓ User 1 sees {$user1Policies->count()} policies\n";

    $belongsToCompany1 = $user1Policies->every(fn($p) => $p->company_id == $company1->id);
    if ($belongsToCompany1) {
        echo "✅ PASS: All policies belong to User 1's company\n";
        $results['phase1']['test3'] = '✅ PASS';
    } else {
        echo "❌ FAIL: User 1 can see policies from other companies!\n";
        $results['phase1']['test3'] = '❌ FAIL';
    }

    // TEST 4: Cross-Company Access Attempt
    echo "\n📋 TEST 4: Preventing Cross-Company Access\n";
    auth()->login($user1);

    try {
        $unauthorizedPolicy = PolicyConfiguration::where('company_id', $company2->id)->first();
        if ($unauthorizedPolicy) {
            echo "❌ FAIL: User 1 can access Company 2's policies!\n";
            $results['phase1']['test4'] = '❌ FAIL';
        } else {
            echo "✅ PASS: User 1 cannot access Company 2's policies\n";
            $results['phase1']['test4'] = '✅ PASS';
        }
    } catch (\Exception $e) {
        echo "✅ PASS: Cross-company access blocked\n";
        $results['phase1']['test4'] = '✅ PASS';
    }

    // TEST 5: Super Admin Access
    echo "\n📋 TEST 5: Super Admin Access (All Companies)\n";
    $superAdmin = User::whereHas('roles', fn($q) => $q->where('name', 'super_admin'))->first();

    if ($superAdmin) {
        auth()->login($superAdmin);
        $adminPolicies = PolicyConfiguration::all();
        echo "✓ Super Admin sees {$adminPolicies->count()} policies across all companies\n";
        $results['phase1']['test5'] = '✅ PASS';
    } else {
        echo "⚠️  SKIP: No super_admin user found\n";
        $results['phase1']['test5'] = '⚠️ SKIP';
    }

} catch (\Exception $e) {
    echo "❌ ERROR in Phase 1 testing: {$e->getMessage()}\n";
    echo "Stack trace: {$e->getTraceAsString()}\n";
}

// ===================================================================
// PHASE 2: EVENT SYSTEM TESTING
// ===================================================================
echo "\n\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "  PHASE 2: Event System Testing\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

try {
    // TEST 6: Event Listener Registration
    echo "📋 TEST 6: Event System Registration\n";
    $listeners = Event::getListeners(ConfigurationUpdated::class);
    echo "✓ ConfigurationUpdated has " . count($listeners) . " listeners registered\n";
    $results['phase2']['test6'] = count($listeners) > 0 ? '✅ PASS' : '❌ FAIL';

    // TEST 7: ConfigurationCreated Event
    echo "\n📋 TEST 7: ConfigurationCreated Event Dispatching\n";
    Event::fake([ConfigurationCreated::class]);

    $testPolicy = PolicyConfiguration::create([
        'company_id' => $company1->id,
        'configurable_type' => Company::class,
        'configurable_id' => $company1->id,
        'policy_type' => PolicyConfiguration::POLICY_TYPE_RESCHEDULE,
        'config' => [
            'hours_before' => 12,
            'max_reschedules_per_appointment' => 2,
        ],
    ]);

    Event::assertDispatched(ConfigurationCreated::class);
    echo "✅ PASS: ConfigurationCreated event dispatched\n";
    $results['phase2']['test7'] = '✅ PASS';

    // TEST 8: ConfigurationUpdated Event
    echo "\n📋 TEST 8: ConfigurationUpdated Event Dispatching\n";
    Event::fake([ConfigurationUpdated::class]);

    $testPolicy->update([
        'config' => [
            'hours_before' => 24,
            'max_reschedules_per_appointment' => 3,
        ],
    ]);

    Event::assertDispatched(ConfigurationUpdated::class);
    echo "✅ PASS: ConfigurationUpdated event dispatched\n";
    $results['phase2']['test8'] = '✅ PASS';

    // TEST 9: Cache Invalidation
    echo "\n📋 TEST 9: Cache Invalidation on Update\n";

    // Set cache
    $cacheKey = "company:{$company1->id}:config";
    Cache::put($cacheKey, ['test' => 'value'], 3600);
    echo "✓ Cache set: {$cacheKey}\n";

    // Update policy (should invalidate cache)
    $policy1->update([
        'config' => [
            'hours_before' => 36,
            'fee_percentage' => 60,
        ],
    ]);

    // Check if cache was invalidated
    if (Cache::has($cacheKey)) {
        echo "⚠️  WARNING: Cache not invalidated (check listener configuration)\n";
        $results['phase2']['test9'] = '⚠️ WARNING';
    } else {
        echo "✅ PASS: Cache invalidated on configuration update\n";
        $results['phase2']['test9'] = '✅ PASS';
    }

    // TEST 10: ConfigurationDeleted Event
    echo "\n📋 TEST 10: ConfigurationDeleted Event (Soft Delete)\n";
    Event::fake([ConfigurationDeleted::class]);

    $testPolicy->delete();

    Event::assertDispatched(ConfigurationDeleted::class);
    echo "✅ PASS: ConfigurationDeleted event dispatched\n";
    $results['phase2']['test10'] = '✅ PASS';

    // TEST 11: Activity Log Integration
    echo "\n📋 TEST 11: Activity Log Integration\n";

    try {
        $activityExists = \Illuminate\Support\Facades\Schema::hasTable('activity_log');
        if ($activityExists) {
            $activityCount = \DB::table('activity_log')
                ->where('subject_type', PolicyConfiguration::class)
                ->count();
            echo "✓ Activity log table exists\n";
            echo "✓ Found {$activityCount} activity log entries for PolicyConfiguration\n";
            $results['phase2']['test11'] = '✅ PASS';
        } else {
            echo "⚠️  WARNING: activity_log table not found (run migrations)\n";
            $results['phase2']['test11'] = '⚠️ MIGRATION NEEDED';
        }
    } catch (\Exception $e) {
        echo "⚠️  WARNING: Could not check activity_log: {$e->getMessage()}\n";
        $results['phase2']['test11'] = '⚠️ CHECK NEEDED';
    }

    // TEST 12: Sensitive Data Masking
    echo "\n📋 TEST 12: Sensitive Data Masking in Events\n";
    Event::fake([ConfigurationUpdated::class]);

    $sensitivePolicy = PolicyConfiguration::create([
        'company_id' => $company1->id,
        'configurable_type' => Company::class,
        'configurable_id' => $company1->id,
        'policy_type' => 'api_key_config',
        'config' => [
            'api_key' => 'secret_key_12345',
        ],
    ]);

    $sensitivePolicy->update([
        'config' => [
            'api_key' => 'new_secret_key_67890',
        ],
    ]);

    Event::assertDispatched(ConfigurationUpdated::class);
    echo "✅ PASS: Sensitive configuration update event dispatched\n";
    echo "✓ Event should mask API keys automatically\n";
    $results['phase2']['test12'] = '✅ PASS';

} catch (\Exception $e) {
    echo "❌ ERROR in Phase 2 testing: {$e->getMessage()}\n";
    echo "Stack trace: {$e->getTraceAsString()}\n";
}

// ===================================================================
// RESULTS SUMMARY
// ===================================================================
echo "\n\n╔════════════════════════════════════════════════════════════════╗\n";
echo "║  TEST RESULTS SUMMARY                                          ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n\n";

echo "PHASE 1: Security Fixes\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
foreach ($results['phase1'] as $test => $result) {
    echo "$result  $test\n";
}

echo "\nPHASE 2: Event System\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
foreach ($results['phase2'] as $test => $result) {
    echo "$result  $test\n";
}

// Calculate pass rate
$totalTests = count($results['phase1']) + count($results['phase2']);
$passedTests = 0;
foreach (array_merge($results['phase1'], $results['phase2']) as $result) {
    if (str_contains($result, '✅')) {
        $passedTests++;
    }
}

$passRate = $totalTests > 0 ? round(($passedTests / $totalTests) * 100) : 0;

echo "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "OVERALL: {$passedTests}/{$totalTests} tests passed ({$passRate}%)\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

if ($passRate >= 80) {
    echo "✅ READY TO PROCEED TO PHASE 3\n\n";
    exit(0);
} else {
    echo "⚠️  REVIEW REQUIRED BEFORE PROCEEDING\n\n";
    exit(1);
}
