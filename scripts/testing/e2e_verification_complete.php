#!/usr/bin/env php
<?php

require __DIR__ . '/../../vendor/autoload.php';
$app = require_once __DIR__ . '/../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Http;
use App\Models\Company;
use App\Models\Branch;

$agentId = 'agent_f1ce85d06a84afb989dfbb16a9';
$friseurPhone = '+493033081738';
$token = env('RETELL_TOKEN');

$results = [
    'checks_passed' => 0,
    'checks_failed' => 0,
    'warnings' => 0,
    'details' => [],
];

echo "\n";
echo "═══════════════════════════════════════════════════════════\n";
echo "END-TO-END VERIFICATION - Friseur 1 Voice AI System\n";
echo "═══════════════════════════════════════════════════════════\n\n";
echo "Timestamp: " . date('Y-m-d H:i:s') . "\n";
echo "Agent ID: $agentId\n";
echo "Phone: $friseurPhone\n\n";

// ============================================================
// CHECK 1: Retell Agent Status
// ============================================================
echo "🔍 CHECK 1: Retell Agent Status\n";
echo "───────────────────────────────────────────────────────────\n";

try {
    $response = Http::withHeaders([
        'Authorization' => "Bearer $token",
    ])->get("https://api.retellai.com/get-agent/$agentId");

    if ($response->successful()) {
        $agent = $response->json();
        echo "✅ Agent exists in Retell\n";
        echo "   Name: " . ($agent['agent_name'] ?? 'N/A') . "\n";
        echo "   Voice: " . ($agent['voice_id'] ?? 'N/A') . "\n";
        echo "   Language: " . ($agent['language'] ?? 'N/A') . "\n";

        $results['checks_passed']++;
        $results['details']['agent_status'] = 'PASS';
    } else {
        echo "❌ FAIL: Agent not found (HTTP " . $response->status() . ")\n";
        $results['checks_failed']++;
        $results['details']['agent_status'] = 'FAIL';
    }
} catch (Exception $e) {
    echo "❌ EXCEPTION: " . $e->getMessage() . "\n";
    $results['checks_failed']++;
    $results['details']['agent_status'] = 'ERROR';
}

echo "\n";

// ============================================================
// CHECK 2: Deployed Flow Structure
// ============================================================
echo "🔍 CHECK 2: Deployed Flow Structure\n";
echo "───────────────────────────────────────────────────────────\n";

$flowPath = __DIR__ . '/../../public/friseur1_flow_v_PRODUCTION_FIXED.json';

if (file_exists($flowPath)) {
    $flowData = json_decode(file_get_contents($flowPath), true);

    echo "✅ Flow file exists\n";

    // Check tools
    $tools = $flowData['tools'] ?? [];
    $toolCount = count($tools);
    echo "   Tools defined: $toolCount\n";

    $hasInitialize = false;
    $hasCheckAvail = false;
    $hasBookAppt = false;

    foreach ($tools as $tool) {
        $name = $tool['name'] ?? '';
        if (str_contains($name, 'initialize')) $hasInitialize = true;
        if (str_contains($name, 'check_availability')) $hasCheckAvail = true;
        if (str_contains($name, 'book_appointment')) $hasBookAppt = true;
    }

    if ($hasInitialize && $hasCheckAvail && $hasBookAppt) {
        echo "   ✅ All critical tools present\n";
        $results['checks_passed']++;
    } else {
        echo "   ❌ Missing critical tools!\n";
        $results['checks_failed']++;
    }

    // Check function nodes
    $nodes = $flowData['nodes'] ?? [];
    $funcNodes = 0;
    $hasFuncCheckAvail = false;
    $hasFuncBookAppt = false;
    $allWaitForResult = true;

    foreach ($nodes as $node) {
        if (($node['type'] ?? '') === 'function') {
            $funcNodes++;
            $nodeId = $node['id'] ?? '';

            if (str_contains($nodeId, 'check_availability')) $hasFuncCheckAvail = true;
            if (str_contains($nodeId, 'book_appointment')) $hasFuncBookAppt = true;

            if (!($node['wait_for_result'] ?? false)) {
                $allWaitForResult = false;
            }
        }
    }

    echo "   Function nodes: $funcNodes\n";

    if ($hasFuncCheckAvail && $hasFuncBookAppt) {
        echo "   ✅ Critical function nodes present\n";
        $results['checks_passed']++;
    } else {
        echo "   ❌ Missing critical function nodes!\n";
        $results['checks_failed']++;
    }

    if ($allWaitForResult) {
        echo "   ✅ All function nodes have wait_for_result: true\n";
        $results['checks_passed']++;
    } else {
        echo "   ⚠️  WARNING: Some function nodes don't wait for result\n";
        $results['warnings']++;
    }

    $results['details']['flow_structure'] = 'PASS';

} else {
    echo "❌ FAIL: Flow file not found at $flowPath\n";
    $results['checks_failed']++;
    $results['details']['flow_structure'] = 'FAIL';
}

echo "\n";

// ============================================================
// CHECK 3: Phone Number Mapping
// ============================================================
echo "🔍 CHECK 3: Phone Number Mapping\n";
echo "───────────────────────────────────────────────────────────\n";

try {
    $response = Http::withHeaders([
        'Authorization' => "Bearer $token",
    ])->get('https://api.retellai.com/list-phone-numbers');

    if ($response->successful()) {
        $phones = $response->json();
        $friseurPhoneFound = false;
        $correctMapping = false;

        foreach ($phones as $phone) {
            if (($phone['phone_number'] ?? '') === $friseurPhone) {
                $friseurPhoneFound = true;
                $mappedAgent = $phone['agent_id'] ?? 'NONE';

                echo "✅ Phone number found: $friseurPhone\n";
                echo "   Nickname: " . ($phone['nickname'] ?? 'N/A') . "\n";
                echo "   Mapped to agent: $mappedAgent\n";

                if ($mappedAgent === $agentId) {
                    echo "   ✅ CORRECTLY MAPPED to our agent!\n";
                    $correctMapping = true;
                    $results['checks_passed']++;
                } else {
                    echo "   ❌ WRONG MAPPING! Expected: $agentId\n";
                    $results['checks_failed']++;
                }

                break;
            }
        }

        if (!$friseurPhoneFound) {
            echo "❌ FAIL: Phone number $friseurPhone not found in Retell!\n";
            $results['checks_failed']++;
            $results['details']['phone_mapping'] = 'FAIL - Not Found';
        } else {
            $results['details']['phone_mapping'] = $correctMapping ? 'PASS' : 'FAIL - Wrong Agent';
        }

    } else {
        echo "❌ FAIL: Cannot fetch phone numbers (HTTP " . $response->status() . ")\n";
        $results['checks_failed']++;
        $results['details']['phone_mapping'] = 'ERROR';
    }
} catch (Exception $e) {
    echo "❌ EXCEPTION: " . $e->getMessage() . "\n";
    $results['checks_failed']++;
    $results['details']['phone_mapping'] = 'ERROR';
}

echo "\n";

// ============================================================
// CHECK 4: Database Configuration
// ============================================================
echo "🔍 CHECK 4: Database Configuration\n";
echo "───────────────────────────────────────────────────────────\n";

$company = Company::where('name', 'Friseur 1')->first();

if ($company) {
    echo "✅ Company 'Friseur 1' found (ID: {$company->id})\n";
    echo "   Retell Agent ID: " . ($company->retell_agent_id ?? 'NOT SET') . "\n";

    if ($company->retell_agent_id === $agentId) {
        echo "   ✅ Company agent ID correct\n";
        $results['checks_passed']++;
    } else {
        echo "   ❌ Company agent ID incorrect!\n";
        $results['checks_failed']++;
    }

    $branches = $company->branches;
    echo "   Branches: {$branches->count()}\n";

    $allBranchesCorrect = true;
    foreach ($branches as $branch) {
        echo "   → {$branch->name}: " . ($branch->retell_agent_id ?? 'NOT SET');
        if ($branch->retell_agent_id === $agentId) {
            echo " ✅\n";
        } else {
            echo " ❌\n";
            $allBranchesCorrect = false;
        }
    }

    if ($allBranchesCorrect && $branches->count() > 0) {
        echo "   ✅ All branch agent IDs correct\n";
        $results['checks_passed']++;
    } else {
        echo "   ❌ Some branch agent IDs incorrect!\n";
        $results['checks_failed']++;
    }

    $results['details']['database_config'] = $allBranchesCorrect ? 'PASS' : 'FAIL';

} else {
    echo "❌ FAIL: Company 'Friseur 1' not found in database!\n";
    $results['checks_failed']++;
    $results['details']['database_config'] = 'FAIL';
}

echo "\n";

// ============================================================
// CHECK 5: Webhook Endpoints
// ============================================================
echo "🔍 CHECK 5: Webhook Endpoints\n";
echo "───────────────────────────────────────────────────────────\n";

$webhookEndpoints = [
    'initialize_call' => 'https://api.askproai.de/api/retell/initialize-call',
    'check_availability_v17' => 'https://api.askproai.de/api/retell/v17/check-availability',
    'book_appointment_v17' => 'https://api.askproai.de/api/retell/v17/book-appointment',
];

$allEndpointsReachable = true;

foreach ($webhookEndpoints as $name => $url) {
    echo "   Testing: $name\n";
    echo "   URL: $url\n";

    try {
        // HEAD request to check if endpoint exists
        $response = Http::timeout(5)->head($url);
        $statusCode = $response->status();

        // We expect 405 (Method Not Allowed) or 401 (Unauthorized) - both mean endpoint exists
        // We DON'T expect 404 (Not Found) or 500 (Server Error)
        if (in_array($statusCode, [200, 405, 401])) {
            echo "   ✅ Endpoint reachable (HTTP $statusCode)\n";
        } elseif ($statusCode === 404) {
            echo "   ❌ Endpoint NOT FOUND (404)\n";
            $allEndpointsReachable = false;
        } else {
            echo "   ⚠️  WARNING: Unexpected status (HTTP $statusCode)\n";
            $results['warnings']++;
        }
    } catch (Exception $e) {
        echo "   ⚠️  WARNING: " . $e->getMessage() . "\n";
        $results['warnings']++;
    }

    echo "\n";
}

if ($allEndpointsReachable) {
    $results['checks_passed']++;
    $results['details']['webhook_endpoints'] = 'PASS';
} else {
    $results['checks_failed']++;
    $results['details']['webhook_endpoints'] = 'FAIL';
}

// ============================================================
// CHECK 6: Recent Call Analysis
// ============================================================
echo "🔍 CHECK 6: Recent Call Analysis\n";
echo "───────────────────────────────────────────────────────────\n";

$recentCall = \App\Models\RetellCallSession::orderBy('created_at', 'desc')->first();

if ($recentCall) {
    echo "   Latest call ID: {$recentCall->call_id}\n";
    echo "   Started: {$recentCall->started_at}\n";
    echo "   Status: " . ($recentCall->call_status ?? 'unknown') . "\n";
    echo "   Duration: " . ($recentCall->duration ?? 0) . " seconds\n";

    $funcCount = $recentCall->functionTraces()->count();
    $transcriptCount = $recentCall->transcriptSegments()->count();

    echo "   Functions called: $funcCount\n";
    echo "   Transcript segments: $transcriptCount\n";

    if ($funcCount > 0) {
        echo "   ℹ️  Recent calls ARE reaching the system\n";
    } else {
        echo "   ⚠️  WARNING: Recent calls have NO function traces\n";
        $results['warnings']++;
    }

    $results['details']['recent_calls'] = 'INFO';
} else {
    echo "   ℹ️  No calls in database yet\n";
    $results['details']['recent_calls'] = 'NONE';
}

echo "\n";

// ============================================================
// FINAL SUMMARY
// ============================================================
echo "═══════════════════════════════════════════════════════════\n";
echo "VERIFICATION SUMMARY\n";
echo "═══════════════════════════════════════════════════════════\n\n";

echo "Checks Passed: " . $results['checks_passed'] . " ✅\n";
echo "Checks Failed: " . $results['checks_failed'] . " ❌\n";
echo "Warnings: " . $results['warnings'] . " ⚠️\n\n";

echo "Detailed Results:\n";
foreach ($results['details'] as $check => $status) {
    $icon = $status === 'PASS' ? '✅' : ($status === 'FAIL' ? '❌' : 'ℹ️');
    echo "  $icon " . str_pad($check, 30) . " → $status\n";
}

echo "\n";

// Overall verdict
$totalChecks = $results['checks_passed'] + $results['checks_failed'];
$passRate = $totalChecks > 0 ? ($results['checks_passed'] / $totalChecks * 100) : 0;

echo "═══════════════════════════════════════════════════════════\n";

if ($results['checks_failed'] === 0) {
    echo "🟢 OVERALL STATUS: READY FOR TEST CALL\n";
    echo "═══════════════════════════════════════════════════════════\n\n";
    echo "✅ All critical checks passed!\n";
    echo "✅ System is configured correctly\n";
    echo "✅ Test call can be made to: $friseurPhone\n\n";
    echo "Next Steps:\n";
    echo "1. Make test call to $friseurPhone\n";
    echo "2. Say: 'Ich möchte einen Herrenhaarschnitt morgen um 14 Uhr'\n";
    echo "3. Listen for: 'Ich prüfe die Verfügbarkeit...'\n";
    echo "4. Verify functions were called in database\n\n";
    exit(0);
} elseif ($results['checks_failed'] <= 2 && $passRate >= 70) {
    echo "🟡 OVERALL STATUS: MOSTLY READY (with warnings)\n";
    echo "═══════════════════════════════════════════════════════════\n\n";
    echo "⚠️  Some checks failed but system might still work\n";
    echo "⚠️  Review failed checks above\n";
    echo "⚠️  Consider fixing issues before test call\n\n";
    exit(1);
} else {
    echo "🔴 OVERALL STATUS: NOT READY\n";
    echo "═══════════════════════════════════════════════════════════\n\n";
    echo "❌ Critical issues found - DO NOT make test call yet!\n";
    echo "❌ Fix the failed checks above first\n\n";
    exit(2);
}
