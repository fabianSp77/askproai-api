<?php

$json = json_decode(file_get_contents('public/askproai_conversation_flow_import.json'), true);

echo "=== REMOVING SELF-REFERENCING EDGES ===\n\n";

$fixed = 0;

foreach ($json['nodes'] as &$node) {
    if (isset($node['edges'])) {
        $originalCount = count($node['edges']);
        $node['edges'] = array_filter($node['edges'], function($edge) use ($node) {
            if ($edge['destination_node_id'] === $node['id']) {
                echo "Removing self-loop: {$edge['id']} in node {$node['id']}\n";
                return false; // Remove this edge
            }
            return true; // Keep this edge
        });

        // Re-index array
        $node['edges'] = array_values($node['edges']);

        if (count($node['edges']) < $originalCount) {
            $fixed++;
        }
    }
}

file_put_contents(
    'public/askproai_conversation_flow_import.json',
    json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
);

echo "\n✅ Fixed $fixed nodes with self-referencing edges\n";
echo "File updated: public/askproai_conversation_flow_import.json\n";
