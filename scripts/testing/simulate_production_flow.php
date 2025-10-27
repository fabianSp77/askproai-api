#!/usr/bin/env php
<?php

require __DIR__ . '/../../vendor/autoload.php';
$app = require_once __DIR__ . '/../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Services\Testing\CallFlowSimulator;
use App\Services\Testing\MockFunctionExecutor;
use App\Services\Testing\FlowValidationEngine;

echo "\n";
echo "═══════════════════════════════════════════════════════════\n";
echo "SIMULATION: Production Flow (What We Deployed)\n";
echo "═══════════════════════════════════════════════════════════\n\n";

$flowPath = __DIR__ . "/../../public/friseur1_flow_v_PRODUCTION_FIXED.json";

if (!file_exists($flowPath)) {
    die("ERROR: Flow file not found: $flowPath\n");
}

$flowData = json_decode(file_get_contents($flowPath), true);

echo "✅ Flow loaded: " . basename($flowPath) . "\n";
echo "   Tools defined: " . count($flowData['tools'] ?? []) . "\n";
echo "   Nodes defined: " . count($flowData['nodes'] ?? []) . "\n\n";

// Check tools
echo "🔧 Tools in flow:\n";
foreach ($flowData['tools'] ?? [] as $tool) {
    echo "  ✅ " . ($tool['tool_id'] ?? 'unknown') . " → " . ($tool['name'] ?? 'unknown') . "\n";
}
echo "\n";

// Check function nodes
echo "⚙️  Function nodes:\n";
$funcNodeCount = 0;
foreach ($flowData['nodes'] ?? [] as $node) {
    if (($node['type'] ?? '') === 'function') {
        $funcNodeCount++;
        echo "  ✅ " . ($node['id'] ?? 'unknown');
        echo " (tool: " . ($node['tool_id'] ?? 'N/A') . ")";
        echo " wait=" . (($node['wait_for_result'] ?? false) ? 'true' : 'false');
        echo "\n";
    }
}
echo "   Total function nodes: $funcNodeCount\n\n";

if ($funcNodeCount === 0) {
    echo "❌ ERROR: No function nodes found in flow!\n";
    exit(1);
}

// Now simulate
echo "═══════════════════════════════════════════════════════════\n";
echo "RUNNING SIMULATION\n";
echo "═══════════════════════════════════════════════════════════\n\n";

$mockExecutor = new MockFunctionExecutor();
$validator = new FlowValidationEngine();
$simulator = new CallFlowSimulator($mockExecutor, $validator);

$scenario = [
    'name' => 'Herrenhaarschnitt Booking Test',
    'flow_file' => $flowPath,
    'variables' => [
        'phone_number' => '+49123456789',
        'customer_name' => 'Max Mustermann',
        'service' => 'Herrenhaarschnitt',
        'date' => '25.10.2025',
        'time' => '14:00',
    ],
];

echo "Scenario: {$scenario['name']}\n";
echo "Customer: Max Mustermann\n";
echo "Service: Herrenhaarschnitt\n";
echo "DateTime: 25.10.2025 14:00\n\n";

try {
    $result = $simulator->simulateCall($scenario);

    if ($result->success) {
        echo "✅ SIMULATION COMPLETED SUCCESSFULLY\n\n";

        echo "📞 Functions called during simulation:\n";
        if (empty($result->functionsCalled)) {
            echo "  ❌ NONE - THIS IS A PROBLEM!\n\n";
            echo "⛔ Flow did not call any functions!\n";
            exit(1);
        } else {
            foreach ($result->functionsCalled as $func) {
                echo "  ✅ " . $func['name'] . " at " . $func['timestamp'] . "\n";
            }
        }
        echo "\n";

        // Critical verification
        $checkAvailCalled = false;
        $bookApptCalled = false;

        foreach ($result->functionsCalled as $func) {
            $name = $func['name'];
            if (str_contains($name, 'check_availability')) {
                $checkAvailCalled = true;
            }
            if (str_contains($name, 'book_appointment')) {
                $bookApptCalled = true;
            }
        }

        echo "🎯 CRITICAL VERIFICATION:\n";
        echo "  check_availability called? " . ($checkAvailCalled ? "✅ YES" : "❌ NO") . "\n";
        echo "  book_appointment called? " . ($bookApptCalled ? "✅ YES" : "❌ NO") . "\n\n";

        if ($checkAvailCalled && $bookApptCalled) {
            echo "═══════════════════════════════════════════════════════════\n";
            echo "🎉 SIMULATION PASSED - FLOW IS CORRECT!\n";
            echo "═══════════════════════════════════════════════════════════\n\n";
            echo "✅ The deployed flow WILL call:\n";
            echo "   1. check_availability when user provides date/time\n";
            echo "   2. book_appointment when user confirms\n\n";
            echo "🚀 RECOMMENDATION: PROCEED WITH TEST CALL\n\n";
            exit(0);
        } else {
            echo "═══════════════════════════════════════════════════════════\n";
            echo "❌ SIMULATION FAILED - CRITICAL FUNCTIONS NOT CALLED\n";
            echo "═══════════════════════════════════════════════════════════\n\n";
            echo "⛔ DO NOT MAKE TEST CALL - Flow has issues!\n\n";
            exit(1);
        }

    } else {
        echo "❌ SIMULATION FAILED\n";
        echo "Error: {$result->error}\n\n";

        if (!empty($result->validationErrors)) {
            echo "Validation errors:\n";
            foreach ($result->validationErrors as $err) {
                echo "  - $err\n";
            }
            echo "\n";
        }

        echo "⛔ DO NOT MAKE TEST CALL - Flow validation failed!\n\n";
        exit(1);
    }

} catch (Exception $e) {
    echo "❌ EXCEPTION OCCURRED\n";
    echo "Message: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n\n";
    echo "⛔ DO NOT MAKE TEST CALL!\n\n";
    exit(1);
}
