<?php

$json = json_decode(file_get_contents('public/askproai_agent_import.json'), true);

echo "=== DEEP VALIDATION ===\n\n";

$errors = [];

// Validate agent level
if (!isset($json[0])) {
    $errors[] = "Agent array is empty or missing";
} else {
    $agent = $json[0];

    // Check conversationFlow exists
    if (!isset($agent['conversationFlow'])) {
        $errors[] = "Agent missing 'conversationFlow'";
    } else {
        $flow = $agent['conversationFlow'];

        // Validate tools
        if (!isset($flow['tools'])) {
            $errors[] = "Flow missing 'tools' array";
        } else {
            foreach ($flow['tools'] as $i => $tool) {
                if ($tool === null) {
                    $errors[] = "Tool $i is NULL";
                    continue;
                }
                if (!isset($tool['type'])) {
                    $errors[] = "Tool $i missing 'type'";
                }
                if (isset($tool['parameters']) && $tool['parameters'] !== null) {
                    if (!isset($tool['parameters']['type'])) {
                        $errors[] = "Tool $i parameters missing 'type'";
                    }
                }
            }
        }

        // Validate nodes
        if (!isset($flow['nodes'])) {
            $errors[] = "Flow missing 'nodes' array";
        } else {
            foreach ($flow['nodes'] as $i => $node) {
                if ($node === null) {
                    $errors[] = "Node $i is NULL";
                    continue;
                }

                $nodeId = isset($node['id']) ? $node['id'] : "INDEX_$i";

                // Check type
                if (!isset($node['type'])) {
                    $errors[] = "Node $nodeId missing 'type'";
                }

                // Check instruction
                if (isset($node['instruction'])) {
                    if ($node['instruction'] === null) {
                        $errors[] = "Node $nodeId instruction is NULL";
                    } elseif (!isset($node['instruction']['type'])) {
                        $errors[] = "Node $nodeId instruction missing 'type'";
                    }
                } else {
                    // Only required for non-end nodes
                    if (isset($node['type']) && $node['type'] !== 'end') {
                        $errors[] = "Node $nodeId missing 'instruction'";
                    }
                }

                // Check edges
                if (isset($node['edges'])) {
                    if ($node['edges'] === null) {
                        $errors[] = "Node $nodeId edges is NULL";
                    } elseif (!is_array($node['edges'])) {
                        $errors[] = "Node $nodeId edges is not an array";
                    } else {
                        foreach ($node['edges'] as $ei => $edge) {
                            if ($edge === null) {
                                $errors[] = "Node $nodeId edge $ei is NULL";
                                continue;
                            }

                            if (!isset($edge['transition_condition'])) {
                                $errors[] = "Node $nodeId edge $ei missing 'transition_condition'";
                            } elseif ($edge['transition_condition'] === null) {
                                $errors[] = "Node $nodeId edge $ei transition_condition is NULL";
                            } elseif (!isset($edge['transition_condition']['type'])) {
                                $errors[] = "Node $nodeId edge $ei transition_condition missing 'type'";
                            }

                            if (!isset($edge['destination_node_id'])) {
                                $errors[] = "Node $nodeId edge $ei missing 'destination_node_id'";
                            }
                        }
                    }
                }

                // Check skip_response_edge
                if (isset($node['skip_response_edge'])) {
                    $edge = $node['skip_response_edge'];
                    if ($edge === null) {
                        $errors[] = "Node $nodeId skip_response_edge is NULL";
                    } elseif (!isset($edge['transition_condition'])) {
                        $errors[] = "Node $nodeId skip_response_edge missing 'transition_condition'";
                    } elseif ($edge['transition_condition'] === null) {
                        $errors[] = "Node $nodeId skip_response_edge transition_condition is NULL";
                    } elseif (!isset($edge['transition_condition']['type'])) {
                        $errors[] = "Node $nodeId skip_response_edge transition_condition missing 'type'";
                    }
                }
            }
        }
    }
}

if (empty($errors)) {
    echo "✅ ALL VALIDATIONS PASSED!\n";
    echo "\nAgent Structure:\n";
    echo "- Agent properties: " . count(array_keys($json[0])) . "\n";
    echo "- Tools: " . count($json[0]['conversationFlow']['tools']) . "\n";
    echo "- Nodes: " . count($json[0]['conversationFlow']['nodes']) . "\n";
} else {
    echo "❌ ERRORS FOUND:\n\n";
    foreach ($errors as $error) {
        echo "  • $error\n";
    }
}

echo "\n=== VALIDATION COMPLETE ===\n";
