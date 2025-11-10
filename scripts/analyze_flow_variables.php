<?php

/**
 * Deep Variable Analysis for Flow V14
 *
 * Prüft:
 * 1. Variable Declaration vs Usage Gaps
 * 2. Redundante Datenabfragen
 * 3. State Management Konsistenz
 */

$flowData = json_decode(file_get_contents('/tmp/flow_v14_full.json'), true);

echo "🔍 DEEP VARIABLE ANALYSIS - Flow V{$flowData['version']}\n";
echo str_repeat("=", 80) . "\n\n";

// ============================================================================
// 1. EXTRACT ALL USED VARIABLES
// ============================================================================

$usedVariables = [];
$declaredVariables = [];

// Parse global_prompt for declarations
preg_match_all('/-\s+\{\{([^}]+)\}\}\s+-\s+(.+)$/m', $flowData['global_prompt'], $matches, PREG_SET_ORDER);
foreach ($matches as $match) {
    $varName = $match[1];
    $description = trim($match[2]);
    $declaredVariables[$varName] = $description;
}

// Parse all nodes for variable usage
foreach ($flowData['nodes'] as $node) {
    $nodeId = $node['id'];
    $nodeName = $node['name'];

    // Check instruction text
    if (isset($node['instruction'])) {
        $text = '';
        if (is_array($node['instruction'])) {
            $text = $node['instruction']['text'] ?? $node['instruction']['prompt'] ?? '';
        } else {
            $text = $node['instruction'];
        }

        preg_match_all('/\{\{([^}]+)\}\}/', $text, $matches);
        foreach ($matches[1] as $var) {
            if (!isset($usedVariables[$var])) {
                $usedVariables[$var] = [];
            }
            $usedVariables[$var][] = [
                'node' => $nodeName,
                'type' => 'instruction',
                'context' => substr($text, max(0, strpos($text, "{{{$var}}}") - 30), 100)
            ];
        }
    }

    // Check parameter mappings
    if (isset($node['parameter_mapping'])) {
        foreach ($node['parameter_mapping'] as $param => $value) {
            preg_match_all('/\{\{([^}]+)\}\}/', $value, $matches);
            foreach ($matches[1] as $var) {
                if (!isset($usedVariables[$var])) {
                    $usedVariables[$var] = [];
                }
                $usedVariables[$var][] = [
                    'node' => $nodeName,
                    'type' => 'parameter_mapping',
                    'param' => $param,
                    'value' => $value
                ];
            }
        }
    }

    // Check edge conditions
    if (isset($node['edges'])) {
        foreach ($node['edges'] as $edge) {
            $condition = $edge['transition_condition']['prompt'] ?? '';
            preg_match_all('/\{\{([^}]+)\}\}/', $condition, $matches);
            foreach ($matches[1] as $var) {
                if (!isset($usedVariables[$var])) {
                    $usedVariables[$var] = [];
                }
                $usedVariables[$var][] = [
                    'node' => $nodeName,
                    'type' => 'edge_condition',
                    'condition' => substr($condition, 0, 80)
                ];
            }
        }
    }
}

// ============================================================================
// 2. DECLARATION vs USAGE GAP ANALYSIS
// ============================================================================

echo "📊 VARIABLE DECLARATION vs USAGE\n";
echo str_repeat("-", 80) . "\n\n";

echo "✅ DEKLARIERTE VARIABLES (global_prompt):\n";
foreach ($declaredVariables as $var => $desc) {
    $usageCount = count($usedVariables[$var] ?? []);
    echo "  - {{$var}}: {$desc}\n";
    echo "    Usage: {$usageCount}x\n";
}
echo "\n";

echo "⚠️  VERWENDETE ABER NICHT DEKLARIERTE VARIABLES:\n";
$undeclared = [];
foreach ($usedVariables as $var => $usages) {
    if (!isset($declaredVariables[$var])) {
        $undeclared[$var] = $usages;
    }
}

if (empty($undeclared)) {
    echo "  ✅ Keine\n";
} else {
    foreach ($undeclared as $var => $usages) {
        echo "  ❌ {{$var}}: " . count($usages) . "x verwendet\n";
        echo "     Verwendet in:\n";
        foreach (array_slice($usages, 0, 3) as $usage) {
            echo "       - {$usage['node']} ({$usage['type']})\n";
        }
    }
}
echo "\n";

echo "⚠️  DEKLARIERTE ABER NICHT VERWENDETE VARIABLES:\n";
$unused = [];
foreach ($declaredVariables as $var => $desc) {
    if (!isset($usedVariables[$var])) {
        $unused[$var] = $desc;
    }
}

if (empty($unused)) {
    echo "  ✅ Keine\n";
} else {
    foreach ($unused as $var => $desc) {
        echo "  ⚠️  {{$var}}: {$desc}\n";
    }
}
echo "\n";

// ============================================================================
// 3. STATE PERSISTENCE ANALYSIS
// ============================================================================

echo "📊 STATE PERSISTENCE PRÜFUNG\n";
echo str_repeat("-", 80) . "\n\n";

// Check if data collection nodes actually check existing state
$dataCollectionNodes = [
    'node_collect_booking_info' => ['customer_name', 'service_name', 'appointment_date', 'appointment_time'],
    'node_collect_cancel_info' => ['cancel_datum', 'cancel_uhrzeit'],
    'node_collect_reschedule_info' => ['old_datum', 'old_uhrzeit', 'new_datum', 'new_uhrzeit']
];

foreach ($flowData['nodes'] as $node) {
    if (!isset($dataCollectionNodes[$node['id']])) continue;

    $expectedVars = $dataCollectionNodes[$node['id']];
    $nodeName = $node['name'];

    echo "📦 {$nodeName}\n";
    echo "   Sollte sammeln: " . implode(', ', $expectedVars) . "\n";

    $text = '';
    if (is_array($node['instruction'])) {
        $text = $node['instruction']['text'] ?? $node['instruction']['prompt'] ?? '';
    }

    // Check if instruction mentions each variable
    echo "   Variable Check:\n";
    foreach ($expectedVars as $var) {
        $mentioned = stripos($text, "{{{$var}}}") !== false;
        $status = $mentioned ? "✅" : "❌";
        echo "     {$status} {$var}: " . ($mentioned ? "Erwähnt in Instruction" : "NICHT erwähnt") . "\n";
    }

    // Check if it warns about checking existing data
    $checksState = stripos($text, 'bereits') !== false || stripos($text, 'Prüfe') !== false;
    $status = $checksState ? "✅" : "⚠️ ";
    echo "   {$status} State Check: " . ($checksState ? "Prüft vorhandene Daten" : "Keine explizite State-Prüfung") . "\n";
    echo "\n";
}

// ============================================================================
// 4. REDUNDANTE DATENABFRAGEN IDENTIFIZIEREN
// ============================================================================

echo "📊 REDUNDANTE DATENABFRAGEN\n";
echo str_repeat("-", 80) . "\n\n";

// Check if booking flow might ask for data twice
$bookingNode = null;
foreach ($flowData['nodes'] as $node) {
    if ($node['id'] === 'node_collect_booking_info') {
        $bookingNode = $node;
        break;
    }
}

if ($bookingNode) {
    $text = is_array($bookingNode['instruction']) ? ($bookingNode['instruction']['text'] ?? '') : $bookingNode['instruction'];

    echo "📦 Buchungsdaten sammeln - Redundanzprüfung\n\n";

    // Check for each variable if instruction warns about double-asking
    $vars = ['customer_name', 'service_name', 'appointment_date', 'appointment_time'];

    foreach ($vars as $var) {
        echo "  Variable: {{$var}}\n";

        // Check if instruction has logic to skip if already filled
        $hasSkipLogic = preg_match("/Wenn\s+\{\{{$var}\}\}\s+(leer|nicht|bereits)/i", $text) > 0;
        $hasCheckLogic = preg_match("/Prüfe.*\{\{{$var}\}\}/i", $text) > 0;

        if ($hasSkipLogic || $hasCheckLogic) {
            echo "    ✅ Hat Skip-Logik (fragt nicht doppelt)\n";
        } else {
            echo "    ⚠️  Keine explizite Skip-Logik\n";
        }

        // Check transition condition
        $edge = $bookingNode['edges'][0] ?? null;
        if ($edge) {
            $condition = $edge['transition_condition']['prompt'] ?? '';
            $checksVar = stripos($condition, "{{{$var}}}") !== false;
            echo "    " . ($checksVar ? "✅" : "❌") . " Transition prüft Variable\n";
        }
        echo "\n";
    }
}

// ============================================================================
// 5. VARIABLE LIFECYCLE TRACKING
// ============================================================================

echo "📊 VARIABLE LIFECYCLE\n";
echo str_repeat("-", 80) . "\n\n";

// Track where each variable is SET vs READ
$variableLifecycle = [];

foreach ($flowData['nodes'] as $node) {
    $nodeName = $node['name'];

    // Variables are SET in conversation nodes (based on user input)
    // Variables are READ in function nodes (parameter mapping) and conditions

    if ($node['type'] === 'conversation') {
        // This node likely SETS variables (collects data)
        $text = is_array($node['instruction']) ? ($node['instruction']['text'] ?? $node['instruction']['prompt'] ?? '') : ($node['instruction'] ?? '');

        // Look for data collection patterns
        foreach ($declaredVariables as $var => $desc) {
            if (stripos($text, "{{$var}}") !== false) {
                if (!isset($variableLifecycle[$var])) {
                    $variableLifecycle[$var] = ['set' => [], 'read' => []];
                }

                // Check if it's asking for data (SET) or just reading
                if (preg_match('/Frage|sammeln|Name|Datum|Uhrzeit|Service/i', $text)) {
                    $variableLifecycle[$var]['set'][] = $nodeName;
                } else {
                    $variableLifecycle[$var]['read'][] = $nodeName;
                }
            }
        }
    } elseif ($node['type'] === 'function') {
        // Function nodes READ variables
        $mapping = $node['parameter_mapping'] ?? [];
        foreach ($mapping as $param => $value) {
            preg_match_all('/\{\{([^}]+)\}\}/', $value, $matches);
            foreach ($matches[1] as $var) {
                if (!isset($variableLifecycle[$var])) {
                    $variableLifecycle[$var] = ['set' => [], 'read' => []];
                }
                $variableLifecycle[$var]['read'][] = $nodeName;
            }
        }
    }
}

echo "Variable Lifecycle Map:\n\n";
foreach ($variableLifecycle as $var => $lifecycle) {
    echo "📌 {{$var}}\n";

    if (!empty($lifecycle['set'])) {
        echo "   SET by: " . implode(', ', array_unique($lifecycle['set'])) . "\n";
    } else {
        echo "   ⚠️  NEVER SET (system variable?)\n";
    }

    if (!empty($lifecycle['read'])) {
        echo "   READ by: " . implode(', ', array_unique($lifecycle['read'])) . "\n";
    } else {
        echo "   ⚠️  NEVER READ\n";
    }
    echo "\n";
}

// ============================================================================
// SUMMARY
// ============================================================================
echo str_repeat("=", 80) . "\n";
echo "📋 VARIABLE CONSISTENCY SUMMARY\n";
echo str_repeat("=", 80) . "\n\n";

$issues = [];
$warnings = [];

if (!empty($undeclared)) {
    $issues[] = count($undeclared) . " verwendete aber nicht deklarierte Variable(n)";
}

if (!empty($unused)) {
    $warnings[] = count($unused) . " deklarierte aber nicht verwendete Variable(n)";
}

if (!empty($issues)) {
    echo "❌ PROBLEME:\n";
    foreach ($issues as $i) {
        echo "  - {$i}\n";
    }
    echo "\n";
}

if (!empty($warnings)) {
    echo "⚠️  WARNUNGEN:\n";
    foreach ($warnings as $w) {
        echo "  - {$w}\n";
    }
    echo "\n";
}

echo "🔧 EMPFEHLUNGEN:\n";
echo "1. Deklariere fehlende Variables im global_prompt\n";
echo "2. Entferne ungenutzte Variable-Deklarationen\n";
echo "3. Füge State-Check-Logik zu allen Data Collection Nodes hinzu\n";
echo "4. Verifiziere Variable Lifecycle (SET → READ Flow)\n";
