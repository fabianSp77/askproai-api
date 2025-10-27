<?php

$json = json_decode(file_get_contents('public/askproai_agent_import.json'), true);

echo "=== VALIDATING AGENT IMPORT JSON ===\n\n";

$agent = $json[0];
$flow = $agent['conversationFlow'];

echo "✓ Agent has conversationFlow\n";
echo "  - Nodes: " . count($flow['nodes']) . "\n";
echo "  - Tools: " . count($flow['tools']) . "\n\n";

// Validate tools
echo "=== TOOLS ===\n";
foreach ($flow['tools'] as $i => $tool) {
    $issues = [];

    if (!isset($tool['type'])) {
        $issues[] = "Missing 'type'";
    }
    if (!isset($tool['tool_id'])) {
        $issues[] = "Missing 'tool_id'";
    }
    if (!isset($tool['name'])) {
        $issues[] = "Missing 'name'";
    }

    if (!empty($issues)) {
        $toolName = isset($tool['name']) ? $tool['name'] : 'UNNAMED';
        echo "❌ Tool $i ($toolName): " . implode(', ', $issues) . "\n";
    } else {
        echo "✓ Tool: {$tool['name']}\n";
    }
}

echo "\n=== NODES ===\n";
foreach ($flow['nodes'] as $i => $node) {
    $nodeId = isset($node['id']) ? $node['id'] : "UNNAMED_$i";
    $issues = [];

    // Check type
    if (!isset($node['type'])) {
        $issues[] = "Missing 'type'";
    }

    // Check instruction (except for end nodes)
    if (isset($node['type']) && $node['type'] !== 'end') {
        if (!isset($node['instruction'])) {
            $issues[] = "Missing 'instruction'";
        } elseif (!isset($node['instruction']['type'])) {
            $issues[] = "instruction missing 'type'";
        }
    }

    // Check function nodes
    if (isset($node['type']) && $node['type'] === 'function') {
        if (!isset($node['tool_id'])) {
            $issues[] = "Function node missing 'tool_id'";
        }
        if (!isset($node['wait_for_result'])) {
            $issues[] = "Function node missing 'wait_for_result'";
        }
    }

    // Check edges
    if (isset($node['edges'])) {
        foreach ($node['edges'] as $ei => $edge) {
            if (!isset($edge['transition_condition'])) {
                $issues[] = "Edge $ei missing 'transition_condition'";
            } elseif (!isset($edge['transition_condition']['type'])) {
                $issues[] = "Edge $ei transition_condition missing 'type'";
            }
            if (!isset($edge['destination_node_id'])) {
                $issues[] = "Edge $ei missing 'destination_node_id'";
            }
        }
    }

    // Check skip_response_edge
    if (isset($node['skip_response_edge'])) {
        $edge = $node['skip_response_edge'];
        if (!isset($edge['transition_condition'])) {
            $issues[] = "skip_response_edge missing 'transition_condition'";
        } elseif (!isset($edge['transition_condition']['type'])) {
            $issues[] = "skip_response_edge transition_condition missing 'type'";
        }
    }

    $nodeType = isset($node['type']) ? $node['type'] : 'MISSING';
    if (!empty($issues)) {
        echo "❌ Node: $nodeId (type: $nodeType)\n";
        foreach ($issues as $issue) {
            echo "   - $issue\n";
        }
    } else {
        echo "✓ Node: $nodeId (type: $nodeType)\n";
    }
}

echo "\n=== VALIDATION COMPLETE ===\n";
