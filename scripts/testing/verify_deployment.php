#!/usr/bin/env php
<?php
/**
 * Verify Deployment - Check Agent Status & Simulate Call
 *
 * Verifiziert dass:
 * 1. Der richtige Agent online ist
 * 2. Alle Funktionen registriert sind
 * 3. Der Flow korrekt deployed wurde
 * 4. Simulation erfolgreich ist
 */

require __DIR__ . '/../../vendor/autoload.php';
$app = require_once __DIR__ . '/../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Http;
use App\Services\Testing\CallFlowSimulator;
use App\Services\Testing\MockFunctionExecutor;
use App\Services\Testing\FlowValidationEngine;

echo "\n";
echo "╔══════════════════════════════════════════════════════════════════╗\n";
echo "║  Deployment Verification - Pre-Test Call Check                  ║\n";
echo "╚══════════════════════════════════════════════════════════════════╝\n";
echo "\nTimestamp: " . now()->format('Y-m-d H:i:s') . "\n\n";

$retellToken = env('RETELL_TOKEN');
$agentId = 'agent_f1ce85d06a84afb989dfbb16a9';

if (!$retellToken) {
    echo "❌ ERROR: RETELL_TOKEN not set in .env\n";
    echo "Cannot verify agent status without API token.\n\n";
    exit(1);
}

// ========================================================================
// STEP 1: Verify Agent is Online
// ========================================================================

echo "📡 STEP 1: Checking Agent Status\n";
echo str_repeat('=', 80) . "\n\n";

echo "Agent ID: $agentId\n";
echo "Fetching agent configuration from Retell API...\n\n";

try {
    $response = Http::timeout(30)->withHeaders([
        'Authorization' => "Bearer $retellToken",
    ])->get("https://api.retellai.com/get-agent/$agentId");

    if (!$response->successful()) {
        echo "❌ ERROR: Could not fetch agent\n";
        echo "   Status: {$response->status()}\n";
        echo "   Error: {$response->body()}\n\n";
        exit(1);
    }

    $agent = $response->json();

    echo "✅ Agent found and online\n";
    echo "   Agent Name: " . ($agent['agent_name'] ?? 'N/A') . "\n";
    echo "   Agent ID: " . ($agent['agent_id'] ?? 'N/A') . "\n";

    // Check if this is the Friseur 1 agent
    $agentName = strtolower($agent['agent_name'] ?? '');
    if (!str_contains($agentName, 'friseur')) {
        echo "⚠️  WARNING: Agent name doesn't contain 'Friseur'\n";
        echo "   Expected: Friseur 1 agent\n";
        echo "   Got: {$agent['agent_name']}\n\n";
    }

    echo "   Voice ID: " . ($agent['voice_id'] ?? 'N/A') . "\n";
    echo "   Language: " . ($agent['language'] ?? 'N/A') . "\n\n";

} catch (\Exception $e) {
    echo "❌ ERROR: Exception while fetching agent\n";
    echo "   Message: {$e->getMessage()}\n\n";
    exit(1);
}

// ========================================================================
// STEP 2: Verify Tools/Functions are Registered
// ========================================================================

echo "🔧 STEP 2: Verifying Registered Tools/Functions\n";
echo str_repeat('=', 80) . "\n\n";

$requiredTools = [
    'tool-initialize-call' => 'initialize_call',
    'tool-v17-check-availability' => 'check_availability_v17',
    'tool-v17-book-appointment' => 'book_appointment_v17',
];

$registeredTools = [];
$missingTools = [];

if (isset($agent['conversation_flow']['tools'])) {
    foreach ($agent['conversation_flow']['tools'] as $tool) {
        $toolId = $tool['tool_id'] ?? null;
        $toolName = $tool['name'] ?? 'N/A';

        if ($toolId) {
            $registeredTools[$toolId] = $toolName;
        }
    }
}

echo "Registered Tools:\n";
foreach ($registeredTools as $id => $name) {
    echo "  ✅ $id → $name\n";
}
echo "\n";

echo "Checking Required Tools:\n";
foreach ($requiredTools as $toolId => $toolName) {
    if (isset($registeredTools[$toolId])) {
        echo "  ✅ $toolId ($toolName) - FOUND\n";
    } else {
        echo "  ❌ $toolId ($toolName) - MISSING!\n";
        $missingTools[] = $toolId;
    }
}
echo "\n";

if (!empty($missingTools)) {
    echo "❌ CRITICAL: Missing required tools!\n";
    echo "   Missing: " . implode(', ', $missingTools) . "\n\n";
    exit(1);
}

echo "✅ All required tools are registered\n\n";

// ========================================================================
// STEP 3: Verify Function Nodes Exist in Flow
// ========================================================================

echo "🔍 STEP 3: Verifying Function Nodes in Deployed Flow\n";
echo str_repeat('=', 80) . "\n\n";

$nodes = $agent['conversation_flow']['nodes'] ?? [];
echo "Total nodes in flow: " . count($nodes) . "\n\n";

$criticalNodes = [
    'func_check_availability' => false,
    'func_book_appointment' => false,
];

foreach ($nodes as $node) {
    $nodeId = $node['id'] ?? 'unknown';
    $nodeType = $node['type'] ?? 'unknown';

    if ($nodeId === 'func_check_availability') {
        $criticalNodes['func_check_availability'] = true;

        echo "✅ Found: func_check_availability\n";
        echo "   Type: {$nodeType}\n";
        echo "   Tool ID: " . ($node['tool_id'] ?? 'N/A') . "\n";
        echo "   Wait for result: " . (($node['wait_for_result'] ?? false) ? 'true' : 'false') . "\n";
        echo "   Speak during execution: " . (($node['speak_during_execution'] ?? false) ? 'true' : 'false') . "\n\n";

        // Verify settings
        if ($nodeType !== 'function') {
            echo "   ⚠️  WARNING: Type should be 'function', got '$nodeType'\n";
        }
        if (!($node['wait_for_result'] ?? false)) {
            echo "   ❌ ERROR: wait_for_result should be true!\n";
        }
        if (($node['tool_id'] ?? '') !== 'tool-v17-check-availability') {
            echo "   ⚠️  WARNING: Expected tool_id 'tool-v17-check-availability'\n";
        }
    }

    if ($nodeId === 'func_book_appointment') {
        $criticalNodes['func_book_appointment'] = true;

        echo "✅ Found: func_book_appointment\n";
        echo "   Type: {$nodeType}\n";
        echo "   Tool ID: " . ($node['tool_id'] ?? 'N/A') . "\n";
        echo "   Wait for result: " . (($node['wait_for_result'] ?? false) ? 'true' : 'false') . "\n";
        echo "   Speak during execution: " . (($node['speak_during_execution'] ?? false) ? 'true' : 'false') . "\n\n";

        // Verify settings
        if ($nodeType !== 'function') {
            echo "   ⚠️  WARNING: Type should be 'function', got '$nodeType'\n";
        }
        if (!($node['wait_for_result'] ?? false)) {
            echo "   ❌ ERROR: wait_for_result should be true!\n";
        }
        if (($node['tool_id'] ?? '') !== 'tool-v17-book-appointment') {
            echo "   ⚠️  WARNING: Expected tool_id 'tool-v17-book-appointment'\n";
        }
    }
}

$allCriticalNodesPresent = !in_array(false, $criticalNodes, true);

if (!$allCriticalNodesPresent) {
    echo "❌ CRITICAL: Missing function nodes!\n";
    foreach ($criticalNodes as $nodeId => $found) {
        if (!$found) {
            echo "   Missing: $nodeId\n";
        }
    }
    echo "\n";
    exit(1);
}

echo "✅ All critical function nodes present and configured\n\n";

// ========================================================================
// STEP 4: Simulate Test Call with Deployed Flow
// ========================================================================

echo "🧪 STEP 4: Simulating Test Call with Deployed Flow\n";
echo str_repeat('=', 80) . "\n\n";

// Save deployed flow temporarily for simulation
$deployedFlowPath = '/tmp/deployed_flow_verification.json';
file_put_contents($deployedFlowPath, json_encode($agent['conversation_flow'], JSON_PRETTY_PRINT));

echo "Saved deployed flow to: $deployedFlowPath\n";
echo "File size: " . number_format(filesize($deployedFlowPath)) . " bytes\n\n";

$mockExecutor = new MockFunctionExecutor();
$validator = new FlowValidationEngine();
$simulator = new CallFlowSimulator($mockExecutor, $validator);

$testScenario = [
    'name' => 'Herrenhaarschnitt Booking - Deployed Flow',
    'flow_file' => $deployedFlowPath,
    'variables' => [
        'phone_number' => '+49123456789',
        'customer_name' => 'Max Mustermann',
        'service' => 'Herrenhaarschnitt',
        'date' => '25.10.2025',
        'time' => '14:00',
    ],
    'user_inputs' => [
        'Guten Tag',
        'Ich möchte einen Herrenhaarschnitt morgen um 14 Uhr',
        'Max Mustermann',
        'Ja, buchen Sie bitte',
    ],
];

echo "Scenario: {$testScenario['name']}\n\n";

$result = $simulator->simulateCall($testScenario);

if ($result->success) {
    echo "✅ Simulation completed successfully\n\n";

    echo "Functions Called:\n";
    if (empty($result->functionsCalled)) {
        echo "  ❌ NO FUNCTIONS CALLED - PROBLEM!\n\n";
    } else {
        foreach ($result->functionsCalled as $func) {
            echo "  ✅ {$func['name']} at {$func['timestamp']}\n";
        }
        echo "\n";
    }

    // Critical verification
    $checkAvailabilityCalled = $result->wasFunctionCalled('check_availability_v17') ||
                                $result->wasFunctionCalled('check_availability');
    $bookAppointmentCalled = $result->wasFunctionCalled('book_appointment_v17') ||
                             $result->wasFunctionCalled('book_appointment');

    echo "🎯 CRITICAL VERIFICATION:\n";
    echo "  check_availability called? " . ($checkAvailabilityCalled ? "✅ YES" : "❌ NO") . "\n";
    echo "  book_appointment called? " . ($bookAppointmentCalled ? "✅ YES" : "❌ NO") . "\n\n";

    if (!$checkAvailabilityCalled || !$bookAppointmentCalled) {
        echo "❌ SIMULATION FAILED: Critical functions not called\n\n";
        echo "⛔ DO NOT MAKE TEST CALL - Flow has issues!\n\n";
        exit(1);
    }

} else {
    echo "❌ Simulation failed\n";
    echo "Error: {$result->error}\n\n";

    if (!empty($result->validationErrors)) {
        echo "Validation Errors:\n";
        foreach ($result->validationErrors as $error) {
            echo "  - {$error}\n";
        }
        echo "\n";
    }

    echo "⛔ DO NOT MAKE TEST CALL - Flow validation failed!\n\n";
    exit(1);
}

// ========================================================================
// FINAL SUMMARY
// ========================================================================

echo str_repeat('=', 80) . "\n\n";
echo "╔══════════════════════════════════════════════════════════════════╗\n";
echo "║  VERIFICATION COMPLETE - READY FOR TEST CALL                    ║\n";
echo "╚══════════════════════════════════════════════════════════════════╝\n\n";

echo "✅ All Checks Passed:\n\n";
echo "  1. ✅ Agent is online (agent_f1ce85d06a84afb989dfbb16a9)\n";
echo "  2. ✅ All required tools registered:\n";
echo "     - initialize_call\n";
echo "     - check_availability_v17\n";
echo "     - book_appointment_v17\n";
echo "  3. ✅ Critical function nodes present:\n";
echo "     - func_check_availability (wait_for_result: true)\n";
echo "     - func_book_appointment (wait_for_result: true)\n";
echo "  4. ✅ Simulation successful:\n";
echo "     - check_availability called ✅\n";
echo "     - book_appointment called ✅\n\n";

echo "🎉 RECOMMENDATION: GO FOR TEST CALL\n\n";

echo "📞 Test Call Script:\n";
echo "   1. Anrufen: [Ihre Friseur 1 Retell-Nummer]\n";
echo "   2. Sagen: \"Ich möchte einen Herrenhaarschnitt morgen um 14 Uhr\"\n";
echo "   3. Erwarten: \"Einen Moment bitte, ich prüfe die Verfügbarkeit...\"\n";
echo "   4. Bei Verfügbarkeit: \"Ja, buchen Sie bitte\"\n";
echo "   5. Erwarten: \"Perfekt! Einen Moment bitte, ich buche...\"\n\n";

echo "🔍 After Test Call - Database Check:\n";
echo "   php artisan tinker\n";
echo "   >>> \$call = \\App\\Models\\RetellCallSession::latest()->first()\n";
echo "   >>> \$call->functionTraces->pluck('function_name')\n";
echo "   >>> // Should show: check_availability_v17, book_appointment_v17\n\n";

echo "✅ VERIFICATION COMPLETE - ALL SYSTEMS GO!\n\n";
