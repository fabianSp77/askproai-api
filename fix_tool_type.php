<?php

$json = json_decode(file_get_contents('public/askproai_conversation_flow_import.json'), true);

echo "=== ADDING tool_type TO FUNCTION NODES ===\n\n";

$fixed = 0;

foreach ($json['nodes'] as &$node) {
    if ($node['type'] === 'function') {
        if (!isset($node['tool_type'])) {
            echo "Fixing: {$node['id']}\n";
            $node['tool_type'] = 'local';
            $fixed++;
        }
    }
}

file_put_contents(
    'public/askproai_conversation_flow_import.json',
    json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
);

echo "\n✅ Fixed $fixed function nodes\n";
echo "File updated: public/askproai_conversation_flow_import.json\n";
