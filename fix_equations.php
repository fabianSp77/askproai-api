<?php

$json = json_decode(file_get_contents('public/askproai_conversation_flow_import.json'), true);

echo "=== FIXING EQUATION TRANSITION CONDITIONS ===\n\n";

$fixed = 0;

function fixEquationCondition(&$condition) {
    if (!isset($condition['type']) || $condition['type'] !== 'equation') {
        return false;
    }

    if (!isset($condition['equations']) || !is_array($condition['equations'])) {
        return false;
    }

    // Check if equations are strings (old format)
    if (count($condition['equations']) > 0 && is_string($condition['equations'][0])) {
        $newEquations = [];

        foreach ($condition['equations'] as $eq) {
            // Parse equations like "{{customer_status}} == \"found\""
            // or "{{availability}} == true"

            // Remove {{ }} brackets
            $eq = str_replace(['{{', '}}'], '', $eq);

            // Parse the equation
            if (preg_match('/^([a-zA-Z_][a-zA-Z0-9_]*)\s*(==|!=|>|>=|<|<=|contains|not_contains)\s*(.+)$/', trim($eq), $matches)) {
                $left = trim($matches[1]);
                $operator = trim($matches[2]);
                $right = trim($matches[3], ' "\'');

                // Handle boolean values
                if ($right === 'true' || $right === 'false') {
                    $right = $right;
                } elseif (is_numeric($right)) {
                    $right = $right;
                } else {
                    // Keep as string
                    $right = $right;
                }

                $newEquations[] = [
                    'left' => $left,
                    'operator' => $operator,
                    'right' => $right
                ];
            }
        }

        if (count($newEquations) > 0) {
            $condition['equations'] = $newEquations;
            $condition['operator'] = '&&'; // Default to AND
            return true;
        }
    }

    return false;
}

foreach ($json['nodes'] as &$node) {
    // Fix edges
    if (isset($node['edges'])) {
        foreach ($node['edges'] as &$edge) {
            if (isset($edge['transition_condition'])) {
                if (fixEquationCondition($edge['transition_condition'])) {
                    echo "Fixed equation in node: {$node['id']} edge: {$edge['id']}\n";
                    $fixed++;
                }
            }
        }
    }

    // Fix skip_response_edge
    if (isset($node['skip_response_edge']['transition_condition'])) {
        if (fixEquationCondition($node['skip_response_edge']['transition_condition'])) {
            echo "Fixed equation in node: {$node['id']} skip_response_edge\n";
            $fixed++;
        }
    }
}

file_put_contents(
    'public/askproai_conversation_flow_import.json',
    json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
);

echo "\n✅ Fixed $fixed equation conditions\n";
echo "File updated: public/askproai_conversation_flow_import.json\n";
