#!/usr/bin/env php
<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$flowData = file_get_contents(__DIR__ . '/current_agent_flow.json');
$flow = json_decode($flowData, true);

echo "\n═══════════════════════════════════════════════════════════\n";
echo "🗺️  FLOW PATH TRACING: START → func_check_availability\n";
echo "═══════════════════════════════════════════════════════════\n\n";

// Build adjacency list
$graph = [];
$nodes = [];

foreach ($flow['nodes'] as $node) {
    $nodeId = $node['id'];
    $nodes[$nodeId] = $node;

    if (isset($node['edges'])) {
        foreach ($node['edges'] as $edge) {
            $to = $edge['destination_node_id'];
            $condition = $edge['transition_condition']['prompt'] ?? $edge['transition_condition']['type'] ?? 'unconditional';

            if (!isset($graph[$nodeId])) {
                $graph[$nodeId] = [];
            }

            $graph[$nodeId][] = [
                'to' => $to,
                'condition' => $condition,
            ];
        }
    }
}

// Find start node
$startNodeId = $flow['start_node_id'] ?? 'func_00_initialize';

echo "Start Node: $startNodeId\n";
echo "Target Node: func_check_availability\n\n";

// BFS to find path
function findPath($graph, $start, $target) {
    $queue = [[$start]];
    $visited = [$start => true];

    while (!empty($queue)) {
        $path = array_shift($queue);
        $current = end($path);

        if ($current === $target) {
            return $path;
        }

        if (isset($graph[$current])) {
            foreach ($graph[$current] as $edge) {
                $next = $edge['to'];

                if (!isset($visited[$next])) {
                    $visited[$next] = true;
                    $newPath = $path;
                    $newPath[] = $next;
                    $queue[] = $newPath;
                }
            }
        }
    }

    return null;
}

$path = findPath($graph, $startNodeId, 'func_check_availability');

if (!$path) {
    echo "❌ NO PATH FOUND from $startNodeId to func_check_availability!\n";
    echo "   This means the flow CANNOT reach the availability check!\n\n";
    exit(1);
}

echo "✅ Path found! " . count($path) . " nodes\n\n";

echo "Complete Path:\n";
echo "───────────────────────────────────────────────────────────\n";

for ($i = 0; $i < count($path); $i++) {
    $nodeId = $path[$i];
    $node = $nodes[$nodeId] ?? ['name' => 'Unknown', 'type' => 'unknown'];

    $step = $i + 1;
    $type = $node['type'];
    $name = $node['name'] ?? $nodeId;

    echo "[$step] $nodeId\n";
    echo "    Type: $type\n";
    echo "    Name: $name\n";

    // Show transition to next
    if ($i < count($path) - 1) {
        $nextId = $path[$i + 1];

        // Find edge
        if (isset($graph[$nodeId])) {
            foreach ($graph[$nodeId] as $edge) {
                if ($edge['to'] === $nextId) {
                    $condition = $edge['condition'];

                    echo "    ↓\n";
                    echo "    Transition: $condition\n";
                    echo "\n";
                    break;
                }
            }
        }
    } else {
        echo "    ← TARGET REACHED!\n";
    }
}

echo "\n";
echo "═══════════════════════════════════════════════════════════\n";
echo "CRITICAL NODES ANALYSIS\n";
echo "═══════════════════════════════════════════════════════════\n\n";

// Analyze prompt-based transitions
$promptTransitions = 0;
$staticTransitions = 0;

foreach ($path as $i => $nodeId) {
    if ($i === count($path) - 1) break; // Skip last node

    $nextId = $path[$i + 1];

    if (isset($graph[$nodeId])) {
        foreach ($graph[$nodeId] as $edge) {
            if ($edge['to'] === $nextId) {
                if ($edge['condition'] !== 'unconditional' && $edge['condition'] !== 'static_text') {
                    $promptTransitions++;

                    echo "⚠️  PROMPT-BASED TRANSITION DETECTED:\n";
                    echo "   From: $nodeId\n";
                    echo "   To: $nextId\n";
                    echo "   Condition: \"{$edge['condition']}\"\n";
                    echo "   → LLM must decide this!\n";
                    echo "   → If LLM doesn't decide correctly, flow gets stuck!\n\n";
                } else {
                    $staticTransitions++;
                }
            }
        }
    }
}

echo "Transition Summary:\n";
echo "  Prompt-based: $promptTransitions ⚠️\n";
echo "  Static/Unconditional: $staticTransitions ✅\n\n";

if ($promptTransitions > 0) {
    echo "🚨 POTENTIAL PROBLEM:\n";
    echo "   Flow relies on LLM decisions ($promptTransitions times)\n";
    echo "   If LLM doesn't trigger transition, flow gets stuck!\n\n";

    echo "RECOMMENDED FIX:\n";
    echo "   1. Use unconditional transitions where possible\n";
    echo "   2. Use expression-based transitions with explicit variable checks\n";
    echo "   3. Reduce reliance on prompt-based decisions\n\n";
}
