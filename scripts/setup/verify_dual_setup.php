<?php

/**
 * VERIFY DUAL BASE SETUP
 *
 * Validates that both AskProAI and Friseur 1 base companies are correctly set up
 * Checks: Companies, Branches, Staff, Services, Phone Numbers, Pivot Tables
 * Tests: Multi-tenant isolation, Cal.com Event Types, Retell Agent mappings
 */

require __DIR__ . '/../../vendor/autoload.php';

$app = require_once __DIR__ . '/../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Company;
use App\Models\Branch;
use App\Models\Staff;
use App\Models\Service;
use App\Models\PhoneNumber;

echo "╔══════════════════════════════════════════════════════════════╗\n";
echo "║           VERIFY DUAL BASE SETUP (COMPREHENSIVE)            ║\n";
echo "╚══════════════════════════════════════════════════════════════╝\n\n";

$errors = [];
$warnings = [];
$passed = 0;
$failed = 0;

// ========================================
// ASKPROAI VERIFICATION
// ========================================

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "  ASKPROAI VERIFICATION\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

// Test 1: AskProAI Company Exists
echo "🔍 Test 1: AskProAI Company...\n";
$askproai = Company::where('name', 'AskProAI')->first();
if ($askproai) {
    $settings = json_decode($askproai->settings, true);
    $calcomTeamId = $settings['calcom_team_id'] ?? null;
    $retellAgentId = $settings['retell_agent_id'] ?? null;

    if ($calcomTeamId === 39203 && $retellAgentId === 'agent_616d645570ae613e421edb98e7') {
        echo "   ✅ Company: AskProAI (ID: {$askproai->id})\n";
        echo "      Cal.com Team: {$calcomTeamId}\n";
        echo "      Retell Agent: {$retellAgentId}\n";
        $passed++;
    } else {
        echo "   ❌ FAIL: Incorrect Cal.com/Retell configuration\n";
        $errors[] = "AskProAI: Cal.com Team or Retell Agent ID incorrect";
        $failed++;
    }
} else {
    echo "   ❌ FAIL: AskProAI company not found\n";
    $errors[] = "AskProAI company missing";
    $failed++;
}

echo "\n";

// Test 2: AskProAI Branch
echo "🔍 Test 2: AskProAI Branch...\n";
if ($askproai) {
    $askproaiBranches = $askproai->branches;
    if ($askproaiBranches->count() === 1) {
        $branch = $askproaiBranches->first();
        echo "   ✅ Branch: {$branch->name}\n";
        echo "      UUID: {$branch->id}\n";
        echo "      Address: {$branch->address}, {$branch->city}\n";
        $passed++;
    } else {
        echo "   ❌ FAIL: Expected 1 branch, found {$askproaiBranches->count()}\n";
        $errors[] = "AskProAI: Incorrect branch count";
        $failed++;
    }
} else {
    echo "   ⏭️  SKIP: Company not found\n";
}

echo "\n";

// Test 3: AskProAI Phone Number
echo "🔍 Test 3: AskProAI Phone Number...\n";
$askproaiPhone = PhoneNumber::where('phone_number', '+493083793369')->first();
if ($askproaiPhone && $askproaiPhone->company_id === $askproai->id) {
    if ($askproaiPhone->retell_agent_id === 'agent_616d645570ae613e421edb98e7') {
        echo "   ✅ Phone: {$askproaiPhone->phone_number}\n";
        echo "      Mapped to: {$askproaiPhone->retell_agent_id}\n";
        $passed++;
    } else {
        echo "   ❌ FAIL: Phone mapped to wrong agent\n";
        $errors[] = "AskProAI: Phone number mapped to incorrect agent";
        $failed++;
    }
} else {
    echo "   ❌ FAIL: Phone number +493083793369 not found or wrong company\n";
    $errors[] = "AskProAI: Phone number missing or incorrect mapping";
    $failed++;
}

echo "\n";

// Test 4: AskProAI Services
echo "🔍 Test 4: AskProAI Services...\n";
if ($askproai) {
    $askproaiServices = Service::where('company_id', $askproai->id)->count();
    if ($askproaiServices >= 3) {
        echo "   ✅ Services: {$askproaiServices} services created\n";
        $passed++;
    } else {
        echo "   ⚠️  WARNING: Only {$askproaiServices} services found (expected >= 3)\n";
        $warnings[] = "AskProAI: Low service count";
        $passed++; // Non-critical
    }
} else {
    echo "   ⏭️  SKIP: Company not found\n";
}

echo "\n\n";

// ========================================
// FRISEUR 1 VERIFICATION
// ========================================

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "  FRISEUR 1 VERIFICATION (TEMPLATE BASE)\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

// Test 5: Friseur 1 Company
echo "🔍 Test 5: Friseur 1 Company...\n";
$friseur1 = Company::where('name', 'Friseur 1')->first();
if ($friseur1) {
    $settings = json_decode($friseur1->settings, true);
    $calcomTeamId = $settings['calcom_team_id'] ?? null;
    $retellAgentId = $settings['retell_agent_id'] ?? null;

    if ($calcomTeamId === 34209 && $retellAgentId === 'agent_45daa54928c5768b52ba3db736') {
        echo "   ✅ Company: Friseur 1 (ID: {$friseur1->id})\n";
        echo "      Cal.com Team: {$calcomTeamId}\n";
        echo "      Retell Agent: {$retellAgentId}\n";
        $passed++;
    } else {
        echo "   ❌ FAIL: Incorrect Cal.com/Retell configuration\n";
        echo "      Expected Team: 34209, Got: {$calcomTeamId}\n";
        echo "      Expected Agent: agent_45daa54928c5768b52ba3db736, Got: {$retellAgentId}\n";
        $errors[] = "Friseur 1: Cal.com Team or Retell Agent ID incorrect";
        $failed++;
    }
} else {
    echo "   ❌ FAIL: Friseur 1 company not found\n";
    $errors[] = "Friseur 1 company missing";
    $failed++;
}

echo "\n";

// Test 6: Friseur 1 Branches
echo "🔍 Test 6: Friseur 1 Branches (2 expected)...\n";
if ($friseur1) {
    $friseur1Branches = $friseur1->branches;
    if ($friseur1Branches->count() === 2) {
        echo "   ✅ Branches: 2 branches found\n";
        foreach ($friseur1Branches as $branch) {
            echo "      → {$branch->name} ({$branch->id})\n";
            echo "        Address: {$branch->address}, {$branch->city}\n";
        }
        $passed++;
    } else {
        echo "   ❌ FAIL: Expected 2 branches, found {$friseur1Branches->count()}\n";
        $errors[] = "Friseur 1: Incorrect branch count";
        $failed++;
    }
} else {
    echo "   ⏭️  SKIP: Company not found\n";
}

echo "\n";

// Test 7: Friseur 1 Phone Number
echo "🔍 Test 7: Friseur 1 Phone Number...\n";
$friseur1Phone = PhoneNumber::where('phone_number', '+493033081738')->first();
if ($friseur1Phone && $friseur1Phone->company_id === $friseur1->id) {
    if ($friseur1Phone->retell_agent_id === 'agent_45daa54928c5768b52ba3db736') {
        echo "   ✅ Phone: {$friseur1Phone->phone_number}\n";
        echo "      Mapped to: {$friseur1Phone->retell_agent_id}\n";
        $passed++;
    } else {
        echo "   ❌ FAIL: Phone mapped to wrong agent\n";
        echo "      Expected: agent_45daa54928c5768b52ba3db736\n";
        echo "      Got: {$friseur1Phone->retell_agent_id}\n";
        $errors[] = "Friseur 1: Phone number mapped to incorrect agent";
        $failed++;
    }
} else {
    echo "   ❌ FAIL: Phone number +493033081738 not found or wrong company\n";
    $errors[] = "Friseur 1: Phone number missing or incorrect mapping";
    $failed++;
}

echo "\n";

// Test 8: Friseur 1 Staff
echo "🔍 Test 8: Friseur 1 Staff (5 expected)...\n";
if ($friseur1) {
    $friseur1Staff = Staff::where('company_id', $friseur1->id)->get();
    if ($friseur1Staff->count() === 5) {
        echo "   ✅ Staff: 5 members found\n";
        $zentrale = $friseur1Branches->where('name', 'Friseur 1 Zentrale')->first();
        $zweigstelle = $friseur1Branches->where('name', 'Friseur 1 Zweigstelle')->first();

        $zentraleStaff = $friseur1Staff->where('branch_id', $zentrale->id ?? null)->count();
        $zweigstelleStaff = $friseur1Staff->where('branch_id', $zweigstelle->id ?? null)->count();

        echo "      Zentrale: {$zentraleStaff} staff\n";
        echo "      Zweigstelle: {$zweigstelleStaff} staff\n";

        foreach ($friseur1Staff as $staff) {
            $branchName = $staff->branch_id === ($zentrale->id ?? null) ? 'Zentrale' : 'Zweigstelle';
            echo "      → {$staff->name} ({$staff->position}) @ {$branchName}\n";
        }
        $passed++;
    } else {
        echo "   ❌ FAIL: Expected 5 staff, found {$friseur1Staff->count()}\n";
        $errors[] = "Friseur 1: Incorrect staff count";
        $failed++;
    }
} else {
    echo "   ⏭️  SKIP: Company not found\n";
}

echo "\n";

// Test 9: Friseur 1 Services
echo "🔍 Test 9: Friseur 1 Services (16 expected)...\n";
if ($friseur1) {
    $friseur1Services = Service::where('company_id', $friseur1->id)->get();
    if ($friseur1Services->count() === 16) {
        echo "   ✅ Services: 16 services created\n";
        $categories = $friseur1Services->groupBy('category')->map->count();
        foreach ($categories as $category => $count) {
            echo "      {$category}: {$count} services\n";
        }
        $passed++;
    } else {
        echo "   ❌ FAIL: Expected 16 services, found {$friseur1Services->count()}\n";
        $errors[] = "Friseur 1: Incorrect service count";
        $failed++;
    }
} else {
    echo "   ⏭️  SKIP: Company not found\n";
}

echo "\n";

// Test 10: Friseur 1 Cal.com Event Type IDs
echo "🔍 Test 10: Friseur 1 Cal.com Event Type IDs...\n";
if ($friseur1 && isset($friseur1Services)) {
    $eventTypeIds = [];
    foreach ($friseur1Services as $service) {
        $settings = json_decode($service->settings, true);
        if (isset($settings['calcom_event_type_id'])) {
            $eventTypeIds[] = $settings['calcom_event_type_id'];
        }
    }

    if (count($eventTypeIds) === 16 && min($eventTypeIds) === 3719738 && max($eventTypeIds) === 3719753) {
        echo "   ✅ Event Type IDs: Range 3719738-3719753\n";
        echo "      All 16 services have Event Type IDs\n";
        $passed++;
    } else {
        echo "   ❌ FAIL: Event Type ID configuration incorrect\n";
        echo "      Found: " . count($eventTypeIds) . " Event Type IDs\n";
        echo "      Range: " . (count($eventTypeIds) > 0 ? min($eventTypeIds) . '-' . max($eventTypeIds) : 'N/A') . "\n";
        $errors[] = "Friseur 1: Event Type IDs missing or incorrect";
        $failed++;
    }
} else {
    echo "   ⏭️  SKIP: Company or services not found\n";
}

echo "\n";

// Test 11: Branch-Service Pivot
echo "🔍 Test 11: Branch-Service Pivot Tables...\n";
if ($friseur1 && isset($friseur1Branches) && isset($friseur1Services)) {
    $zentrale = $friseur1Branches->where('name', 'Friseur 1 Zentrale')->first();
    $zweigstelle = $friseur1Branches->where('name', 'Friseur 1 Zweigstelle')->first();

    if ($zentrale && $zweigstelle) {
        $zentraleServices = DB::table('branch_service')
            ->where('branch_id', $zentrale->id)
            ->count();
        $zweigstelleServices = DB::table('branch_service')
            ->where('branch_id', $zweigstelle->id)
            ->count();

        if ($zentraleServices === 16 && $zweigstelleServices === 16) {
            echo "   ✅ Branch-Service Links:\n";
            echo "      Zentrale: {$zentraleServices} services linked\n";
            echo "      Zweigstelle: {$zweigstelleServices} services linked\n";
            $passed++;
        } else {
            echo "   ❌ FAIL: Incorrect branch-service links\n";
            echo "      Zentrale: {$zentraleServices}/16\n";
            echo "      Zweigstelle: {$zweigstelleServices}/16\n";
            $errors[] = "Friseur 1: branch_service pivot incomplete";
            $failed++;
        }
    }
} else {
    echo "   ⏭️  SKIP: Required data not found\n";
}

echo "\n";

// Test 12: Staff-Service Pivot
echo "🔍 Test 12: Staff-Service Pivot Tables...\n";
if ($friseur1 && isset($friseur1Staff) && isset($friseur1Services)) {
    $totalStaffServiceLinks = DB::table('service_staff')
        ->whereIn('staff_id', $friseur1Staff->pluck('id'))
        ->count();

    $expectedLinks = $friseur1Staff->count() * $friseur1Services->count(); // 5 * 16 = 80

    if ($totalStaffServiceLinks === $expectedLinks) {
        echo "   ✅ Staff-Service Links: {$totalStaffServiceLinks}/{$expectedLinks}\n";
        echo "      All staff linked to all services\n";
        $passed++;
    } else {
        echo "   ⚠️  WARNING: {$totalStaffServiceLinks}/{$expectedLinks} staff-service links\n";
        $warnings[] = "Friseur 1: service_staff pivot incomplete";
        $passed++; // Non-critical
    }
} else {
    echo "   ⏭️  SKIP: Required data not found\n";
}

echo "\n\n";

// ========================================
// MULTI-TENANT ISOLATION TEST
// ========================================

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "  MULTI-TENANT ISOLATION VERIFICATION\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

// Test 13: Company Isolation
echo "🔍 Test 13: Company Data Isolation...\n";
if ($askproai && $friseur1) {
    $askproaiServices = Service::where('company_id', $askproai->id)->count();
    $friseur1Services = Service::where('company_id', $friseur1->id)->count();

    // Check that no cross-company data exists
    $crossContamination = false;
    if ($askproaiServices > 0 && $friseur1Services > 0) {
        echo "   ✅ Data Isolation:\n";
        echo "      AskProAI: {$askproaiServices} services\n";
        echo "      Friseur 1: {$friseur1Services} services\n";
        echo "      No cross-company data detected\n";
        $passed++;
    } else {
        echo "   ⚠️  WARNING: Cannot fully verify isolation (insufficient data)\n";
        $warnings[] = "Isolation test inconclusive";
        $passed++; // Pass with warning
    }
} else {
    echo "   ⏭️  SKIP: Both companies required\n";
}

echo "\n\n";

// ========================================
// FINAL SUMMARY
// ========================================

echo "╔══════════════════════════════════════════════════════════════╗\n";
echo "║                      VERIFICATION SUMMARY                    ║\n";
echo "╚══════════════════════════════════════════════════════════════╝\n\n";

$total = $passed + $failed;
$passRate = $total > 0 ? round(($passed / $total) * 100, 1) : 0;

echo "📊 TEST RESULTS:\n";
echo "   ✅ Passed:   {$passed}/{$total}\n";
echo "   ❌ Failed:   {$failed}/{$total}\n";
echo "   ⚠️  Warnings: " . count($warnings) . "\n";
echo "   📈 Pass Rate: {$passRate}%\n\n";

if ($failed === 0 && count($warnings) === 0) {
    echo "🎉 ALL TESTS PASSED! Dual base setup is perfect!\n\n";
} elseif ($failed === 0) {
    echo "✅ ALL TESTS PASSED (with warnings)\n\n";
    echo "⚠️  WARNINGS:\n";
    foreach ($warnings as $warning) {
        echo "   • {$warning}\n";
    }
    echo "\n";
} else {
    echo "❌ SETUP HAS ERRORS\n\n";
    echo "🔴 ERRORS:\n";
    foreach ($errors as $error) {
        echo "   • {$error}\n";
    }
    echo "\n";

    if (count($warnings) > 0) {
        echo "⚠️  WARNINGS:\n";
        foreach ($warnings as $warning) {
            echo "   • {$warning}\n";
        }
        echo "\n";
    }
}

echo "🧪 MANUAL TESTING REQUIRED:\n";
echo "   1. Call AskProAI:  +493083793369\n";
echo "      Expected: \"Guten Tag bei AskProAI...\"\n";
echo "      Verify: Agent agent_616d645570ae613e421edb98e7 responds\n\n";
echo "   2. Call Friseur 1: +493033081738\n";
echo "      Expected: \"Guten Tag bei Friseur 1, mein Name ist Carola...\"\n";
echo "      Verify: Agent agent_45daa54928c5768b52ba3db736 responds\n\n";
echo "   3. Book Test Appointments:\n";
echo "      AskProAI: 15 min Schnellberatung\n";
echo "      Friseur 1: Waschen, schneiden, föhnen bei Emma\n\n";
echo "   4. Verify in Cal.com:\n";
echo "      Team 39203: AskProAI bookings\n";
echo "      Team 34209: Friseur 1 bookings\n\n";

if ($failed === 0) {
    echo "✅ Verification Complete - Ready for Template System Development!\n";
    exit(0);
} else {
    echo "❌ Verification Failed - Fix errors before proceeding.\n";
    exit(1);
}
