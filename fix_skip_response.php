<?php

$json = json_decode(file_get_contents('public/askproai_conversation_flow_import.json'), true);

echo "=== FIXING SKIP_RESPONSE_EDGE ===\n\n";

$fixed = 0;

foreach ($json['nodes'] as &$node) {
    if (isset($node['skip_response_edge'])) {
        if (isset($node['skip_response_edge']['transition_condition'])) {
            if ($node['skip_response_edge']['transition_condition']['type'] === 'prompt') {
                $oldPrompt = $node['skip_response_edge']['transition_condition']['prompt'];
                $node['skip_response_edge']['transition_condition']['prompt'] = 'Skip response';

                echo "Fixed: {$node['id']} - changed '$oldPrompt' to 'Skip response'\n";
                $fixed++;
            }
        }
    }
}

file_put_contents(
    'public/askproai_conversation_flow_import.json',
    json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
);

echo "\n✅ Fixed $fixed skip_response_edge nodes\n";
echo "File updated: public/askproai_conversation_flow_import.json\n";
