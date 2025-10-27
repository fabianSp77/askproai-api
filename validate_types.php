<?php

$json = json_decode(file_get_contents('public/retell_import_fixed.json'), true);

echo "=== Validating TYPE properties ===" . PHP_EOL . PHP_EOL;

// Check model_choice.type
if (!isset($json['model_choice']['type'])) {
    echo "❌ MISSING: model_choice.type" . PHP_EOL;
} else {
    echo "✅ model_choice.type = " . $json['model_choice']['type'] . PHP_EOL;
}

echo PHP_EOL . "=== Checking Nodes ===" . PHP_EOL . PHP_EOL;

foreach ($json['nodes'] as $i => $node) {
    $nodeId = $node['id'];
    $issues = [];

    // Check node type
    if (!isset($node['type'])) {
        $issues[] = "node.type is MISSING";
    }

    // Check instruction (only for non-end nodes)
    if ($node['type'] !== 'end') {
        if (!isset($node['instruction'])) {
            $issues[] = "instruction is MISSING";
        } elseif (!isset($node['instruction']['type'])) {
            $issues[] = "instruction.type is MISSING";
        }
    }

    // Check edges
    if (isset($node['edges'])) {
        foreach ($node['edges'] as $ei => $edge) {
            if (isset($edge['transition_condition']) && !isset($edge['transition_condition']['type'])) {
                $issues[] = "edge[$ei].transition_condition.type is MISSING";
            }
        }
    }

    if (!empty($issues)) {
        echo "❌ Node: $nodeId" . PHP_EOL;
        foreach ($issues as $issue) {
            echo "   - $issue" . PHP_EOL;
        }
    } else {
        echo "✅ Node: $nodeId" . PHP_EOL;
    }
}

echo PHP_EOL . "=== Validation Complete ===" . PHP_EOL;
