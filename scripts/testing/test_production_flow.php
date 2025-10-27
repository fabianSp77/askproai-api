#!/usr/bin/env php
<?php

/**
 * Test Production-Ready Flow
 *
 * Validates and tests the new production flow that guarantees
 * check_availability execution.
 */

require __DIR__ . '/../../vendor/autoload.php';
$app = require_once __DIR__ . '/../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Services\Testing\CallFlowSimulator;
use App\Services\Testing\MockFunctionExecutor;
use App\Services\Testing\FlowValidationEngine;

echo "╔══════════════════════════════════════════════════════════════════╗\n";
echo "║  Production Flow Validation & Test                              ║\n";
echo "╚══════════════════════════════════════════════════════════════════╝\n\n";

$flowPath = '/var/www/api-gateway/public/friseur1_flow_v_PRODUCTION_FIXED.json';

// ========================================================================
// STEP 1: Validate Flow Structure
// ========================================================================

echo "📋 STEP 1: Validating Flow Structure\n";
echo str_repeat('=', 80) . "\n\n";

$validator = new FlowValidationEngine();
$flowConfig = json_decode(file_get_contents($flowPath), true);

// Update validator to accept Retell's native format
echo "Flow file: " . basename($flowPath) . "\n";
echo "Nodes: " . count($flowConfig['nodes'] ?? []) . "\n";
echo "Tools: " . count($flowConfig['tools'] ?? []) . "\n\n";

// Custom validation for Retell format
$validationErrors = [];
$validationWarnings = [];

// Check for critical function nodes
$hasFuncCheckAvailability = false;
$hasFuncBookAppointment = false;

foreach ($flowConfig['nodes'] ?? [] as $node) {
    $nodeId = $node['id'] ?? 'unknown';
    $nodeType = $node['type'] ?? 'unknown';

    // Check for check_availability function node
    if ($nodeId === 'func_check_availability' && $nodeType === 'function') {
        $hasFuncCheckAvailability = true;

        // Verify it has wait_for_result
        if (!isset($node['wait_for_result']) || !$node['wait_for_result']) {
            $validationWarnings[] = "func_check_availability: wait_for_result should be true";
        }

        // Verify it has tool_id
        if (!isset($node['tool_id'])) {
            $validationErrors[] = "func_check_availability: missing tool_id";
        }

        echo "✅ Found func_check_availability node\n";
        echo "   - tool_id: " . ($node['tool_id'] ?? 'MISSING') . "\n";
        echo "   - wait_for_result: " . ($node['wait_for_result'] ? 'true' : 'false') . "\n";
        echo "   - speak_during_execution: " . ($node['speak_during_execution'] ?? false ? 'true' : 'false') . "\n\n";
    }

    // Check for book_appointment function node
    if ($nodeId === 'func_book_appointment' && $nodeType === 'function') {
        $hasFuncBookAppointment = true;

        // Verify it has wait_for_result
        if (!isset($node['wait_for_result']) || !$node['wait_for_result']) {
            $validationWarnings[] = "func_book_appointment: wait_for_result should be true";
        }

        // Verify it has tool_id
        if (!isset($node['tool_id'])) {
            $validationErrors[] = "func_book_appointment: missing tool_id";
        }

        echo "✅ Found func_book_appointment node\n";
        echo "   - tool_id: " . ($node['tool_id'] ?? 'MISSING') . "\n";
        echo "   - wait_for_result: " . ($node['wait_for_result'] ? 'true' : 'false') . "\n";
        echo "   - speak_during_execution: " . ($node['speak_during_execution'] ?? false ? 'true' : 'false') . "\n\n";
    }
}

if (!$hasFuncCheckAvailability) {
    $validationErrors[] = "CRITICAL: No func_check_availability node found";
}

if (!$hasFuncBookAppointment) {
    $validationErrors[] = "CRITICAL: No func_book_appointment node found";
}

// Validate transition paths
echo "🔍 Checking Transition Paths:\n\n";

foreach ($flowConfig['nodes'] ?? [] as $node) {
    $nodeId = $node['id'] ?? 'unknown';

    // Check if node_collect_appointment_info transitions to func_check_availability
    if ($nodeId === 'node_collect_appointment_info') {
        $hasCheckAvailTransition = false;
        foreach ($node['edges'] ?? [] as $edge) {
            if ($edge['destination_node_id'] === 'func_check_availability') {
                $hasCheckAvailTransition = true;
                echo "✅ node_collect_appointment_info → func_check_availability\n";
                echo "   Condition: " . ($edge['transition_condition']['prompt'] ?? 'N/A') . "\n\n";
            }
        }
        if (!$hasCheckAvailTransition) {
            $validationErrors[] = "node_collect_appointment_info doesn't transition to func_check_availability";
        }
    }

    // Check if node_present_result transitions to func_book_appointment
    if ($nodeId === 'node_present_result') {
        $hasBookTransition = false;
        foreach ($node['edges'] ?? [] as $edge) {
            if ($edge['destination_node_id'] === 'func_book_appointment') {
                $hasBookTransition = true;
                echo "✅ node_present_result → func_book_appointment\n";
                echo "   Condition: " . ($edge['transition_condition']['prompt'] ?? 'N/A') . "\n\n";
            }
        }
        if (!$hasBookTransition) {
            $validationErrors[] = "node_present_result doesn't transition to func_book_appointment";
        }
    }
}

// Report validation results
echo str_repeat('=', 80) . "\n\n";

if (empty($validationErrors)) {
    echo "✅ VALIDATION PASSED\n\n";
} else {
    echo "❌ VALIDATION FAILED\n\n";
    echo "Errors:\n";
    foreach ($validationErrors as $error) {
        echo "  - {$error}\n";
    }
    echo "\n";
}

if (!empty($validationWarnings)) {
    echo "⚠️  Warnings:\n";
    foreach ($validationWarnings as $warning) {
        echo "  - {$warning}\n";
    }
    echo "\n";
}

// ========================================================================
// STEP 2: Simulate Realistic Call
// ========================================================================

echo "🧪 STEP 2: Simulating Realistic Call\n";
echo str_repeat('=', 80) . "\n\n";

$mockExecutor = new MockFunctionExecutor();
$simulator = new CallFlowSimulator($mockExecutor, $validator);

$scenario = [
    'name' => 'Herrenhaarschnitt Booking - Production Flow',
    'flow_file' => $flowPath,
    'variables' => [
        'phone_number' => '+49123456789',
        'service' => 'Herrenhaarschnitt',
        'date' => '25.10.2025',
        'time' => '14:00',
        'customer_name' => 'Max Mustermann',
    ],
    'user_inputs' => [
        'Guten Tag',
        'Ich möchte einen Termin buchen',
        'Herrenhaarschnitt',
        'Morgen um 14 Uhr',
        'Max Mustermann',
        'Ja, buchen Sie bitte',
    ],
];

echo "Scenario: {$scenario['name']}\n";
echo "Flow: " . basename($flowPath) . "\n\n";

$result = $simulator->simulateCall($scenario);

if ($result->success) {
    echo "✅ Simulation completed successfully\n\n";

    echo "Functions Called:\n";
    if (empty($result->functionsCalled)) {
        echo "  ❌ NO FUNCTIONS CALLED (PROBLEM!)\n\n";
    } else {
        foreach ($result->functionsCalled as $func) {
            echo "  ✅ {$func['name']} at {$func['timestamp']}\n";
        }
        echo "\n";
    }

    // CRITICAL CHECK
    $checkAvailabilityCalled = $result->wasFunctionCalled('check_availability_v17') ||
                                $result->wasFunctionCalled('check_availability');
    $bookAppointmentCalled = $result->wasFunctionCalled('book_appointment_v17') ||
                             $result->wasFunctionCalled('book_appointment');

    echo "🎯 CRITICAL VERIFICATION:\n";
    echo "  check_availability called? " . ($checkAvailabilityCalled ? "✅ YES" : "❌ NO") . "\n";
    echo "  book_appointment called? " . ($bookAppointmentCalled ? "✅ YES" : "❌ NO") . "\n\n";

    if ($checkAvailabilityCalled && $bookAppointmentCalled) {
        echo "🎉 SUCCESS! Both critical functions were called.\n";
        echo "This flow is READY for production deployment.\n\n";
    } else {
        echo "❌ FAILURE! Critical functions were NOT called.\n";
        echo "This flow needs further fixes before deployment.\n\n";
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
}

// ========================================================================
// STEP 3: Summary
// ========================================================================

echo str_repeat('=', 80) . "\n\n";
echo "╔══════════════════════════════════════════════════════════════════╗\n";
echo "║  PRODUCTION FLOW TEST SUMMARY                                   ║\n";
echo "╚══════════════════════════════════════════════════════════════════╝\n\n";

$readyForDeployment = empty($validationErrors) &&
                      isset($checkAvailabilityCalled) && $checkAvailabilityCalled &&
                      isset($bookAppointmentCalled) && $bookAppointmentCalled;

if ($readyForDeployment) {
    echo "✅ PRODUCTION READY\n\n";
    echo "Next Steps:\n";
    echo "1. Deploy flow to Retell via API or Dashboard\n";
    echo "2. Update agent to use new flow\n";
    echo "3. Verify with ONE test call\n";
    echo "4. Monitor first 10-20 production calls\n\n";
} else {
    echo "❌ NOT READY FOR PRODUCTION\n\n";
    echo "Issues to fix:\n";
    if (!empty($validationErrors)) {
        foreach ($validationErrors as $error) {
            echo "  - {$error}\n";
        }
    }
    if (!$checkAvailabilityCalled) {
        echo "  - check_availability not called in simulation\n";
    }
    if (!$bookAppointmentCalled) {
        echo "  - book_appointment not called in simulation\n";
    }
    echo "\n";
}

echo "Flow file: {$flowPath}\n";
echo "Ready for deployment: " . ($readyForDeployment ? "YES ✅" : "NO ❌") . "\n\n";
