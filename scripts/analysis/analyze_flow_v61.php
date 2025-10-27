#!/usr/bin/env php
<?php

$flowPath = __DIR__ . '/../../public/friseur1_optimized_v61.json';
$flowData = json_decode(file_get_contents($flowPath), true);

echo "\n";
echo "═══════════════════════════════════════════════════════════\n";
echo "FLOW ANALYSIS V61 - QUALITY CHECK\n";
echo "═══════════════════════════════════════════════════════════\n\n";

// 1. Check for duplicate tools
echo "1️⃣  DUPLICATE TOOLS CHECK\n";
echo "───────────────────────────────────────────────────────────\n";
$toolNames = [];
$toolIds = [];
$duplicates = false;

foreach ($flowData['tools'] as $tool) {
    $name = $tool['name'];
    $id = $tool['tool_id'];

    if (in_array($name, $toolNames)) {
        echo "❌ DUPLICATE NAME: $name\n";
        $duplicates = true;
    }
    if (in_array($id, $toolIds)) {
        echo "❌ DUPLICATE ID: $id\n";
        $duplicates = true;
    }

    $toolNames[] = $name;
    $toolIds[] = $id;
}

if (!$duplicates) {
    echo "✅ No duplicate tools\n";
}
echo "   Total tools: " . count($toolNames) . "\n\n";

// 2. Check for unreachable nodes
echo "2️⃣  UNREACHABLE NODES CHECK\n";
echo "───────────────────────────────────────────────────────────\n";

$allNodeIds = [];
foreach ($flowData['nodes'] as $node) {
    $allNodeIds[] = $node['id'];
}

$reachableFrom = ['start']; // Start with start node
$toProcess = ['start'];
$processed = [];

while (!empty($toProcess)) {
    $current = array_shift($toProcess);
    if (in_array($current, $processed)) continue;
    $processed[] = $current;

    // Find all edges from this node
    foreach ($flowData['edges'] as $edge) {
        if ($edge['from'] === $current && !in_array($edge['to'], $reachableFrom)) {
            $reachableFrom[] = $edge['to'];
            $toProcess[] = $edge['to'];
        }
    }
}

$unreachable = array_diff($allNodeIds, $reachableFrom);

if (!empty($unreachable)) {
    echo "⚠️  UNREACHABLE NODES:\n";
    foreach ($unreachable as $node) {
        echo "   - $node\n";
    }
} else {
    echo "✅ All nodes are reachable from start\n";
}
echo "\n";

// 3. Check for dead-end nodes (except 'end')
echo "3️⃣  DEAD-END NODES CHECK\n";
echo "───────────────────────────────────────────────────────────\n";

$nodesWithOutgoingEdges = [];
foreach ($flowData['edges'] as $edge) {
    if (!in_array($edge['from'], $nodesWithOutgoingEdges)) {
        $nodesWithOutgoingEdges[] = $edge['from'];
    }
}

$deadEnds = array_diff($allNodeIds, $nodesWithOutgoingEdges);
$deadEnds = array_diff($deadEnds, ['end']); // 'end' is supposed to be a dead-end

if (!empty($deadEnds)) {
    echo "⚠️  DEAD-END NODES (no outgoing edges):\n";
    foreach ($deadEnds as $node) {
        echo "   - $node\n";
    }
} else {
    echo "✅ No unexpected dead-end nodes\n";
}
echo "\n";

// 4. Check for function nodes without wait_for_result
echo "4️⃣  FUNCTION NODES - WAIT FOR RESULT CHECK\n";
echo "───────────────────────────────────────────────────────────\n";

$functionNodesWithoutWait = [];
foreach ($flowData['nodes'] as $node) {
    if ($node['type'] === 'function') {
        if (!isset($node['wait_for_result']) || $node['wait_for_result'] !== true) {
            $functionNodesWithoutWait[] = $node['id'];
        }
    }
}

if (!empty($functionNodesWithoutWait)) {
    echo "❌ FUNCTION NODES WITHOUT wait_for_result: true\n";
    foreach ($functionNodesWithoutWait as $node) {
        echo "   - $node\n";
    }
} else {
    echo "✅ All function nodes have wait_for_result: true\n";
}
echo "\n";

// 5. Check for orphan edges
echo "5️⃣  ORPHAN EDGES CHECK\n";
echo "───────────────────────────────────────────────────────────\n";

$orphanEdges = false;
foreach ($flowData['edges'] as $edge) {
    if (!in_array($edge['from'], $allNodeIds)) {
        echo "❌ ORPHAN EDGE FROM: {$edge['from']} → {$edge['to']}\n";
        $orphanEdges = true;
    }
    if (!in_array($edge['to'], $allNodeIds)) {
        echo "❌ ORPHAN EDGE TO: {$edge['from']} → {$edge['to']}\n";
        $orphanEdges = true;
    }
}

if (!$orphanEdges) {
    echo "✅ All edges connect existing nodes\n";
}
echo "\n";

// 6. Check for redundant paths
echo "6️⃣  REDUNDANT PATHS CHECK\n";
echo "───────────────────────────────────────────────────────────\n";

// Check if same intent goes to different nodes
$intentRoutes = [];
foreach ($flowData['edges'] as $edge) {
    if ($edge['from'] === 'intent_router') {
        $condition = $edge['condition'] ?? 'default';
        if (!isset($intentRoutes[$condition])) {
            $intentRoutes[$condition] = [];
        }
        $intentRoutes[$condition][] = $edge['to'];
    }
}

$redundantIntents = false;
foreach ($intentRoutes as $intent => $targets) {
    if (count($targets) > 1) {
        echo "⚠️  INTENT '$intent' routes to multiple targets:\n";
        foreach ($targets as $target) {
            echo "   - $target\n";
        }
        $redundantIntents = true;
    }
}

if (!$redundantIntents) {
    echo "✅ No redundant intent routes\n";
}
echo "\n";

// 7. Flow path summary
echo "7️⃣  FLOW PATHS SUMMARY\n";
echo "───────────────────────────────────────────────────────────\n";

echo "Main flow:\n";
echo "  start → func_initialize → greeting → intent_router\n\n";

echo "Intent routes:\n";
foreach ($intentRoutes as $intent => $targets) {
    echo "  $intent → " . implode(', ', $targets) . "\n";
}
echo "\n";

// 8. Function nodes check
echo "8️⃣  FUNCTION NODES DETAIL\n";
echo "───────────────────────────────────────────────────────────\n";

foreach ($flowData['nodes'] as $node) {
    if ($node['type'] === 'function') {
        $toolId = $node['tool_id'];
        $wait = $node['wait_for_result'] ?? false;
        $speakDuring = $node['speak_during_execution'] ?? false;
        $speakAfter = $node['speak_after_execution'] ?? false;

        $waitIcon = $wait ? '✅' : '❌';
        $speakDuringIcon = $speakDuring ? '🔊' : '🔇';
        $speakAfterIcon = $speakAfter ? '🔊' : '🔇';

        echo "{$node['id']}:\n";
        echo "   Tool: $toolId\n";
        echo "   Wait: $waitIcon " . ($wait ? 'true' : 'false') . "\n";
        echo "   Speak during: $speakDuringIcon " . ($speakDuring ? 'true' : 'false') . "\n";
        echo "   Speak after: $speakAfterIcon " . ($speakAfter ? 'true' : 'false') . "\n";
        echo "\n";
    }
}

// Summary
echo "═══════════════════════════════════════════════════════════\n";
echo "SUMMARY\n";
echo "═══════════════════════════════════════════════════════════\n\n";

$issues = 0;

if ($duplicates) {
    echo "❌ Duplicate tools found\n";
    $issues++;
}

if (!empty($unreachable)) {
    echo "⚠️  Unreachable nodes found\n";
    $issues++;
}

if (!empty($deadEnds)) {
    echo "⚠️  Dead-end nodes found\n";
    $issues++;
}

if (!empty($functionNodesWithoutWait)) {
    echo "❌ Function nodes without wait_for_result\n";
    $issues++;
}

if ($orphanEdges) {
    echo "❌ Orphan edges found\n";
    $issues++;
}

if ($redundantIntents) {
    echo "⚠️  Redundant intent routes\n";
    $issues++;
}

if ($issues === 0) {
    echo "🎉 FLOW IS CLEAN!\n";
    echo "   ✅ No duplicates\n";
    echo "   ✅ All nodes reachable\n";
    echo "   ✅ No orphan edges\n";
    echo "   ✅ All function nodes configured correctly\n";
    echo "   ✅ Clear intent routing\n";
} else {
    echo "Found $issues issue(s) - review above\n";
}

echo "\n";
