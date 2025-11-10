<?php

/**
 * Conversation Flow V14 - Konsistenzanalyse
 *
 * Prüft auf:
 * 1. Doppelte/redundante Abfragen
 * 2. Inkonsistente Parameter Mappings
 * 3. Tote Nodes (nicht erreichbar)
 * 4. Missing Edges (Sackgassen)
 * 5. Tool-Konfiguration Konsistenz
 * 6. Variable Usage Konsistenz
 */

$flowData = json_decode(file_get_contents('/tmp/flow_v14_full.json'), true);

echo "🔍 CONVERSATION FLOW V{$flowData['version']} - KONSISTENZANALYSE\n";
echo str_repeat("=", 80) . "\n\n";

// ============================================================================
// 1. FLOW STRUCTURE ANALYSIS
// ============================================================================
echo "📊 1. FLOW STRUKTUR\n";
echo str_repeat("-", 80) . "\n";
echo "Nodes: " . count($flowData['nodes']) . "\n";
echo "Tools: " . count($flowData['tools']) . "\n";
echo "Start Node: {$flowData['start_node_id']}\n\n";

// Node Type Distribution
$nodeTypes = [];
foreach ($flowData['nodes'] as $node) {
    $type = $node['type'];
    $nodeTypes[$type] = ($nodeTypes[$type] ?? 0) + 1;
}
echo "Node Types:\n";
foreach ($nodeTypes as $type => $count) {
    echo "  - {$type}: {$count}\n";
}
echo "\n";

// ============================================================================
// 2. REACHABILITY ANALYSIS (Tote Nodes)
// ============================================================================
echo "📊 2. ERREICHBARKEITSANALYSE\n";
echo str_repeat("-", 80) . "\n";

$reachable = [$flowData['start_node_id'] => true];
$queue = [$flowData['start_node_id']];

while (!empty($queue)) {
    $currentId = array_shift($queue);

    // Find node
    $node = null;
    foreach ($flowData['nodes'] as $n) {
        if ($n['id'] === $currentId) {
            $node = $n;
            break;
        }
    }

    if (!$node || empty($node['edges'])) continue;

    foreach ($node['edges'] as $edge) {
        $destId = $edge['destination_node_id'];
        if (!isset($reachable[$destId])) {
            $reachable[$destId] = true;
            $queue[] = $destId;
        }
    }
}

$allNodeIds = array_column($flowData['nodes'], 'id');
$unreachable = array_diff($allNodeIds, array_keys($reachable));

if (empty($unreachable)) {
    echo "✅ Alle Nodes sind erreichbar\n\n";
} else {
    echo "❌ TOTE NODES GEFUNDEN:\n";
    foreach ($unreachable as $nodeId) {
        $node = null;
        foreach ($flowData['nodes'] as $n) {
            if ($n['id'] === $nodeId) {
                $node = $n;
                break;
            }
        }
        echo "  - {$node['name']} (ID: {$nodeId})\n";
    }
    echo "\n";
}

// ============================================================================
// 3. DEAD ENDS ANALYSIS (Nodes ohne Edges außer End)
// ============================================================================
echo "📊 3. SACKGASSEN-ANALYSE\n";
echo str_repeat("-", 80) . "\n";

$deadEnds = [];
foreach ($flowData['nodes'] as $node) {
    if ($node['type'] === 'end') continue;

    if (empty($node['edges'])) {
        $deadEnds[] = [
            'id' => $node['id'],
            'name' => $node['name'],
            'type' => $node['type']
        ];
    }
}

if (empty($deadEnds)) {
    echo "✅ Keine Sackgassen gefunden (außer End-Nodes)\n\n";
} else {
    echo "⚠️  SACKGASSEN GEFUNDEN:\n";
    foreach ($deadEnds as $de) {
        echo "  - {$de['name']} ({$de['type']}) - ID: {$de['id']}\n";
    }
    echo "\n";
}

// ============================================================================
// 4. TOOL PARAMETER MAPPING CONSISTENCY
// ============================================================================
echo "📊 4. TOOL PARAMETER MAPPING KONSISTENZ\n";
echo str_repeat("-", 80) . "\n";

$functionNodes = array_filter($flowData['nodes'], fn($n) => $n['type'] === 'function');

foreach ($functionNodes as $node) {
    $toolId = $node['tool_id'];
    $mapping = $node['parameter_mapping'] ?? [];

    // Find tool definition
    $tool = null;
    foreach ($flowData['tools'] as $t) {
        if ($t['tool_id'] === $toolId) {
            $tool = $t;
            break;
        }
    }

    if (!$tool) {
        echo "❌ Node '{$node['name']}' referenziert unbekanntes Tool: {$toolId}\n";
        continue;
    }

    echo "\n🔧 {$node['name']} → {$tool['name']}\n";

    // Check required parameters
    $requiredParams = $tool['parameters']['required'] ?? [];
    $missingParams = [];

    foreach ($requiredParams as $param) {
        if (!isset($mapping[$param])) {
            $missingParams[] = $param;
        }
    }

    if (!empty($missingParams)) {
        echo "  ❌ FEHLENDE REQUIRED PARAMETER:\n";
        foreach ($missingParams as $p) {
            echo "     - {$p}\n";
        }
    } else {
        echo "  ✅ Alle required Parameter gemapped\n";
    }

    // Check parameter mappings
    echo "  📋 Parameter Mappings:\n";
    foreach ($mapping as $param => $value) {
        echo "     {$param}: {$value}\n";
    }
}
echo "\n";

// ============================================================================
// 5. DYNAMIC VARIABLES ANALYSIS
// ============================================================================
echo "📊 5. DYNAMIC VARIABLES ANALYSE\n";
echo str_repeat("-", 80) . "\n";

// Extract all {{variable}} references from instructions
$usedVariables = [];
foreach ($flowData['nodes'] as $node) {
    if (!isset($node['instruction'])) continue;

    $text = '';
    if (is_array($node['instruction'])) {
        $text = $node['instruction']['text'] ?? $node['instruction']['prompt'] ?? '';
    } else {
        $text = $node['instruction'];
    }

    preg_match_all('/\{\{([^}]+)\}\}/', $text, $matches);

    if (!empty($matches[1])) {
        foreach ($matches[1] as $var) {
            $usedVariables[$var] = ($usedVariables[$var] ?? 0) + 1;
        }
    }
}

// Also check parameter mappings
foreach ($functionNodes as $node) {
    $mapping = $node['parameter_mapping'] ?? [];
    foreach ($mapping as $param => $value) {
        preg_match_all('/\{\{([^}]+)\}\}/', $value, $matches);
        if (!empty($matches[1])) {
            foreach ($matches[1] as $var) {
                $usedVariables[$var] = ($usedVariables[$var] ?? 0) + 1;
            }
        }
    }
}

echo "Verwendete Dynamic Variables:\n";
arsort($usedVariables);
foreach ($usedVariables as $var => $count) {
    echo "  - {$var}: {$count}x verwendet\n";
}
echo "\n";

// Check global_prompt for variable declarations
echo "Deklarierte Variables (global_prompt):\n";
preg_match_all('/\{\{([^}]+)\}\}/', $flowData['global_prompt'], $matches);
$declaredVars = array_unique($matches[1] ?? []);
foreach ($declaredVars as $var) {
    echo "  - {$var}\n";
}
echo "\n";

// ============================================================================
// 6. REDUNDANTE DATENABFRAGEN PRÜFEN
// ============================================================================
echo "📊 6. REDUNDANTE DATENABFRAGEN\n";
echo str_repeat("-", 80) . "\n";

// Analyze data collection nodes
$dataCollectionNodes = [
    'node_collect_booking_info',
    'node_collect_cancel_info',
    'node_collect_reschedule_info'
];

echo "Datensammlungs-Nodes:\n";
foreach ($flowData['nodes'] as $node) {
    if (!in_array($node['id'], $dataCollectionNodes)) continue;

    echo "\n📦 {$node['name']} (ID: {$node['id']})\n";

    $text = '';
    if (is_array($node['instruction'])) {
        $text = $node['instruction']['text'] ?? $node['instruction']['prompt'] ?? '';
    }

    // Check if instruction mentions checking existing variables
    if (stripos($text, 'bereits') !== false || stripos($text, 'Prüfe') !== false) {
        echo "  ✅ Prüft bereits vorhandene Daten\n";
    } else {
        echo "  ⚠️  Keine explizite Prüfung vorhandener Daten erwähnt\n";
    }

    // Extract which data is collected
    preg_match_all('/\{\{([^}]+)\}\}/', $text, $matches);
    if (!empty($matches[1])) {
        echo "  📋 Sammelt:\n";
        foreach (array_unique($matches[1]) as $var) {
            echo "     - {$var}\n";
        }
    }
}
echo "\n";

// ============================================================================
// 7. TOOL URL CONSISTENCY
// ============================================================================
echo "📊 7. TOOL URL KONSISTENZ\n";
echo str_repeat("-", 80) . "\n";

$urls = [];
foreach ($flowData['tools'] as $tool) {
    $url = $tool['url'];
    if (!isset($urls[$url])) {
        $urls[$url] = [];
    }
    $urls[$url][] = $tool['name'];
}

if (count($urls) === 1) {
    echo "✅ Alle Tools nutzen dieselbe URL:\n";
    foreach ($urls as $url => $tools) {
        echo "  URL: {$url}\n";
        echo "  Tools: " . count($tools) . "\n";
    }
} else {
    echo "⚠️  UNTERSCHIEDLICHE URLs GEFUNDEN:\n";
    foreach ($urls as $url => $tools) {
        echo "\n  URL: {$url}\n";
        echo "  Tools:\n";
        foreach ($tools as $tool) {
            echo "    - {$tool}\n";
        }
    }
}
echo "\n";

// ============================================================================
// 8. EDGE TRANSITION CONDITIONS ANALYSIS
// ============================================================================
echo "📊 8. EDGE TRANSITION CONDITIONS\n";
echo str_repeat("-", 80) . "\n";

$ambiguousEdges = [];
foreach ($flowData['nodes'] as $node) {
    if (empty($node['edges']) || count($node['edges']) <= 1) continue;

    $edges = $node['edges'];

    // Check for overlapping conditions
    echo "\n📦 {$node['name']} → " . count($edges) . " Edges\n";
    foreach ($edges as $idx => $edge) {
        $condition = $edge['transition_condition']['prompt'] ?? 'NONE';
        $dest = null;
        foreach ($flowData['nodes'] as $n) {
            if ($n['id'] === $edge['destination_node_id']) {
                $dest = $n['name'];
                break;
            }
        }
        echo "  Edge " . ($idx + 1) . " → {$dest}\n";
        echo "    Condition: {$condition}\n";
    }
}
echo "\n";

// ============================================================================
// SUMMARY
// ============================================================================
echo str_repeat("=", 80) . "\n";
echo "📋 ZUSAMMENFASSUNG\n";
echo str_repeat("=", 80) . "\n\n";

$issues = [];
$warnings = [];
$success = [];

if (empty($unreachable)) {
    $success[] = "Alle Nodes erreichbar";
} else {
    $issues[] = count($unreachable) . " tote Node(s)";
}

if (empty($deadEnds)) {
    $success[] = "Keine Sackgassen";
} else {
    $warnings[] = count($deadEnds) . " Node(s) ohne Edges";
}

if (count($urls) === 1) {
    $success[] = "Tool URLs konsistent";
} else {
    $issues[] = "Inkonsistente Tool URLs";
}

echo "✅ ERFOLGE:\n";
foreach ($success as $s) {
    echo "  - {$s}\n";
}
echo "\n";

if (!empty($warnings)) {
    echo "⚠️  WARNUNGEN:\n";
    foreach ($warnings as $w) {
        echo "  - {$w}\n";
    }
    echo "\n";
}

if (!empty($issues)) {
    echo "❌ PROBLEME:\n";
    foreach ($issues as $i) {
        echo "  - {$i}\n";
    }
    echo "\n";
}

echo "🎯 EMPFEHLUNGEN:\n";
echo "1. Prüfen Sie die Transition Conditions auf Überschneidungen\n";
echo "2. Testen Sie alle Conversation Pfade durch\n";
echo "3. Verifizieren Sie Dynamic Variables werden korrekt gesetzt\n";
echo "4. Validieren Sie call_id wird in allen Function Nodes korrekt übertragen\n";
