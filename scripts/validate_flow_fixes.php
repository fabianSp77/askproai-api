<?php

/**
 * Validate Flow V14 Fixes
 *
 * Checks:
 * 1. Global prompt has all 10 variables
 * 2. Stornierung node has correct instruction
 * 3. Verschiebung node has correct instruction
 * 4. All parameter mappings use {{call.call_id}}
 */

$flowData = json_decode(file_get_contents('/tmp/flow_final.json'), true);

echo "✅ FLOW V{$flowData['version']} VALIDATION REPORT\n";
echo str_repeat("=", 80) . "\n\n";

$allPassed = true;

// ============================================================================
// TEST 1: Global Prompt Variables
// ============================================================================

echo "TEST 1: Global Prompt Variable Declarations\n";
echo str_repeat("-", 80) . "\n";

$expectedVariables = [
    'customer_name' => 'Name des Kunden',
    'service_name' => 'Gewünschter Service',
    'appointment_date' => 'Gewünschtes Datum',
    'appointment_time' => 'Gewünschte Uhrzeit',
    'cancel_datum' => 'Datum für Stornierung',
    'cancel_uhrzeit' => 'Uhrzeit für Stornierung',
    'old_datum' => 'Alter Termin Datum',
    'old_uhrzeit' => 'Alter Termin Uhrzeit',
    'new_datum' => 'Neuer Termin Datum',
    'new_uhrzeit' => 'Neuer Termin Uhrzeit'
];

$globalPrompt = $flowData['global_prompt'];
$test1Passed = true;

foreach ($expectedVariables as $var => $desc) {
    $pattern = "/\{\{{$var}\}\}/";
    if (preg_match($pattern, $globalPrompt)) {
        echo "✅ {$var}: Deklariert\n";
    } else {
        echo "❌ {$var}: FEHLT!\n";
        $test1Passed = false;
        $allPassed = false;
    }
}

// Check booking_confirmed was removed
if (stripos($globalPrompt, 'booking_confirmed') !== false) {
    echo "⚠️  booking_confirmed: Sollte entfernt sein, ist aber noch da\n";
    $test1Passed = false;
} else {
    echo "✅ booking_confirmed: Korrekt entfernt\n";
}

echo "\nTest 1: " . ($test1Passed ? "✅ PASSED" : "❌ FAILED") . "\n\n";

// ============================================================================
// TEST 2: Stornierung Node Instruction
// ============================================================================

echo "TEST 2: Stornierung Node State Management\n";
echo str_repeat("-", 80) . "\n";

$stornierungNode = null;
foreach ($flowData['nodes'] as $node) {
    if ($node['id'] === 'node_collect_cancel_info') {
        $stornierungNode = $node;
        break;
    }
}

$test2Passed = true;

if (!$stornierungNode) {
    echo "❌ Stornierung Node nicht gefunden!\n";
    $test2Passed = false;
    $allPassed = false;
} else {
    $instruction = is_array($stornierungNode['instruction'])
        ? $stornierungNode['instruction']['text']
        : $stornierungNode['instruction'];

    // Check for state management keywords
    $checks = [
        'cancel_datum' => stripos($instruction, '{{cancel_datum}}') !== false,
        'cancel_uhrzeit' => stripos($instruction, '{{cancel_uhrzeit}}') !== false,
        'Prüfe' => stripos($instruction, 'Prüfe') !== false || stripos($instruction, 'Bereits gesammelte') !== false,
        'ÜBERSPRINGE' => stripos($instruction, 'ÜBERSPRINGE') !== false || stripos($instruction, 'bereits gefüllt') !== false,
    ];

    foreach ($checks as $check => $result) {
        if ($result) {
            echo "✅ {$check}: Vorhanden\n";
        } else {
            echo "❌ {$check}: FEHLT!\n";
            $test2Passed = false;
            $allPassed = false;
        }
    }

    // Check transition condition
    if (isset($stornierungNode['edges'][0])) {
        $condition = $stornierungNode['edges'][0]['transition_condition']['prompt'] ?? '';
        if (stripos($condition, 'cancel_datum') !== false && stripos($condition, 'cancel_uhrzeit') !== false) {
            echo "✅ Transition Condition: Korrekt (prüft beide Variables)\n";
        } else {
            echo "❌ Transition Condition: INKORREKT\n";
            echo "   Current: {$condition}\n";
            $test2Passed = false;
            $allPassed = false;
        }
    } else {
        echo "❌ Transition Edge: FEHLT!\n";
        $test2Passed = false;
        $allPassed = false;
    }
}

echo "\nTest 2: " . ($test2Passed ? "✅ PASSED" : "❌ FAILED") . "\n\n";

// ============================================================================
// TEST 3: Verschiebung Node Instruction
// ============================================================================

echo "TEST 3: Verschiebung Node State Management\n";
echo str_repeat("-", 80) . "\n";

$verschiebungNode = null;
foreach ($flowData['nodes'] as $node) {
    if ($node['id'] === 'node_collect_reschedule_info') {
        $verschiebungNode = $node;
        break;
    }
}

$test3Passed = true;

if (!$verschiebungNode) {
    echo "❌ Verschiebung Node nicht gefunden!\n";
    $test3Passed = false;
    $allPassed = false;
} else {
    $instruction = is_array($verschiebungNode['instruction'])
        ? $verschiebungNode['instruction']['text']
        : $verschiebungNode['instruction'];

    // Check for state management keywords
    $checks = [
        'old_datum' => stripos($instruction, '{{old_datum}}') !== false,
        'old_uhrzeit' => stripos($instruction, '{{old_uhrzeit}}') !== false,
        'new_datum' => stripos($instruction, '{{new_datum}}') !== false,
        'new_uhrzeit' => stripos($instruction, '{{new_uhrzeit}}') !== false,
        'Prüfe' => stripos($instruction, 'Prüfe') !== false || stripos($instruction, 'Bereits gesammelte') !== false,
        'ÜBERSPRINGE' => stripos($instruction, 'ÜBERSPRINGE') !== false || stripos($instruction, 'bereits gefüllt') !== false,
    ];

    foreach ($checks as $check => $result) {
        if ($result) {
            echo "✅ {$check}: Vorhanden\n";
        } else {
            echo "❌ {$check}: FEHLT!\n";
            $test3Passed = false;
            $allPassed = false;
        }
    }

    // Check transition condition
    if (isset($verschiebungNode['edges'][0])) {
        $condition = $verschiebungNode['edges'][0]['transition_condition']['prompt'] ?? '';
        $requiredVars = ['old_datum', 'old_uhrzeit', 'new_datum', 'new_uhrzeit'];
        $allVarsPresent = true;
        foreach ($requiredVars as $var) {
            if (stripos($condition, $var) === false) {
                $allVarsPresent = false;
                break;
            }
        }

        if ($allVarsPresent) {
            echo "✅ Transition Condition: Korrekt (prüft alle 4 Variables)\n";
        } else {
            echo "❌ Transition Condition: INKORREKT\n";
            echo "   Current: {$condition}\n";
            $test3Passed = false;
            $allPassed = false;
        }
    } else {
        echo "❌ Transition Edge: FEHLT!\n";
        $test3Passed = false;
        $allPassed = false;
    }
}

echo "\nTest 3: " . ($test3Passed ? "✅ PASSED" : "❌ FAILED") . "\n\n";

// ============================================================================
// TEST 4: Parameter Mappings (call_id)
// ============================================================================

echo "TEST 4: Tool Parameter Mappings (call.call_id)\n";
echo str_repeat("-", 80) . "\n";

$functionNodes = array_filter($flowData['nodes'], fn($n) => $n['type'] === 'function');
$test4Passed = true;

foreach ($functionNodes as $node) {
    $mapping = $node['parameter_mapping'] ?? [];
    $callId = $mapping['call_id'] ?? null;

    if ($callId === '{{call.call_id}}') {
        echo "✅ {$node['name']}: {{call.call_id}}\n";
    } else {
        echo "❌ {$node['name']}: {$callId}\n";
        $test4Passed = false;
        $allPassed = false;
    }
}

echo "\nTest 4: " . ($test4Passed ? "✅ PASSED" : "❌ FAILED") . "\n\n";

// ============================================================================
// SUMMARY
// ============================================================================

echo str_repeat("=", 80) . "\n";
echo "VALIDATION SUMMARY\n";
echo str_repeat("=", 80) . "\n\n";

if ($allPassed) {
    echo "🎉 🎉 🎉 ALL TESTS PASSED!\n\n";
    echo "✅ Flow V{$flowData['version']} ist vollständig korrekt konfiguriert:\n";
    echo "   - Global Prompt: 10 Variables deklariert\n";
    echo "   - Stornierung Node: State Management implementiert\n";
    echo "   - Verschiebung Node: State Management implementiert\n";
    echo "   - Parameter Mappings: Alle nutzen {{call.call_id}}\n\n";

    echo "🎯 NÄCHSTE SCHRITTE:\n";
    echo "1. Agent Konfiguration prüfen (nutzt V{$flowData['version']}?)\n";
    echo "2. Agent publishen im Dashboard\n";
    echo "3. Test-Calls durchführen:\n";
    echo "   - Buchung: \"Herrenhaarschnitt morgen 16 Uhr, Hans Schuster\"\n";
    echo "   - Stornierung: \"Ich möchte meinen Termin morgen 14 Uhr stornieren\"\n";
    echo "   - Verschiebung: \"Morgen 14 Uhr auf Donnerstag 16 Uhr verschieben\"\n\n";

    exit(0);
} else {
    echo "❌ VALIDATION FAILED!\n\n";
    echo "Einige Tests sind fehlgeschlagen. Bitte prüfen Sie die Fehler oben.\n\n";
    exit(1);
}
