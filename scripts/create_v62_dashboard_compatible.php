<?php

require __DIR__ . '/../vendor/autoload.php';

echo "═══════════════════════════════════════════════════════\n";
echo "🔧 CREATING V62 DASHBOARD-COMPATIBLE VERSION\n";
echo "═══════════════════════════════════════════════════════\n\n";

// Load V62
$v62 = json_decode(file_get_contents(__DIR__ . '/../retell_agent_v62_fixed.json'), true);
$flow = $v62['conversation_flow'];

echo "Original: " . count($flow['nodes']) . " nodes\n";

// Find and remove logic_split node
$newNodes = [];
$removedLogicSplit = null;
$antiLoopHandlerId = null;

foreach ($flow['nodes'] as $node) {
    if ($node['type'] === 'logic_split') {
        echo "⚠️  Removing logic_split node: {$node['name']}\n";
        $removedLogicSplit = $node;

        // Find the anti-loop handler target
        foreach ($node['edges'] as $edge) {
            if (isset($edge['destination_node_id']) && strpos($edge['destination_node_id'], 'anti_loop_handler') !== false) {
                $antiLoopHandlerId = $edge['destination_node_id'];
            }
        }
        continue;
    }
    $newNodes[] = $node;
}

$flow['nodes'] = $newNodes;

echo "After removal: " . count($flow['nodes']) . " nodes\n\n";

// Now redirect edges that pointed to logic_split
// They should now point directly to node_present_alternatives or use prompt-based logic
foreach ($flow['nodes'] as &$node) {
    if (isset($node['edges'])) {
        foreach ($node['edges'] as &$edge) {
            if (isset($edge['destination_node_id']) && $edge['destination_node_id'] === 'logic_split_anti_loop') {
                echo "🔀 Redirecting edge from {$node['name']} to node_present_alternatives\n";
                // Direct to presenting alternatives, but add prompt-based anti-loop check
                $edge['destination_node_id'] = 'node_present_alternatives';

                // Add prompt-based condition instead of equation
                if (!isset($edge['transition_condition']) || empty($edge['transition_condition'])) {
                    $edge['transition_condition'] = [
                        'type' => 'prompt_completion',
                        'prompt' => 'User selected an alternative time or expressed interest in the suggested alternatives.'
                    ];
                }
            }
        }
    }
}
unset($node, $edge);

// Modify the node_present_alternatives to include anti-loop logic in instruction
foreach ($flow['nodes'] as &$node) {
    if ($node['id'] === 'node_present_alternatives' || $node['name'] === 'Alternativen präsentieren') {
        echo "📝 Adding anti-loop logic to node_present_alternatives instruction\n";

        if (isset($node['instruction']) && isset($node['instruction']['instruction'])) {
            // Add anti-loop instruction
            $node['instruction']['instruction'] .= "\n\n⚠️ ANTI-LOOP CHECK:\nFalls dies bereits die 2. oder 3. Runde mit Alternativen ist und der User immer noch nichts Passendes findet:\n→ Biete stattdessen Callback/Warteliste an: \"Ich habe noch nichts Passendes gefunden. Möchten Sie, dass ich Sie zurückrufe, sobald ein passender Termin frei wird? Oder ich kann Sie auf die Warteliste setzen.\"";
        }
    }
}
unset($node);

echo "\n✅ Dashboard-compatible version created\n";
echo "   - Removed: logic_split node\n";
echo "   - Added: Prompt-based anti-loop in node_present_alternatives\n";
echo "   - Total nodes: " . count($flow['nodes']) . "\n\n";

// Save conversation flow only (for dashboard import)
file_put_contents(
    __DIR__ . '/../public/docs/friseur1/agents/retell_agent_v62_dashboard_compatible.json',
    json_encode($flow, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
);

echo "📁 Saved to: public/docs/friseur1/agents/retell_agent_v62_dashboard_compatible.json\n";
echo "📊 File size: " . round(filesize(__DIR__ . '/../public/docs/friseur1/agents/retell_agent_v62_dashboard_compatible.json') / 1024, 1) . " KB\n\n";

echo "✅ READY FOR DASHBOARD IMPORT!\n";
echo "🔗 URL: https://api.askproai.de/docs/friseur1/agents/retell_agent_v62_dashboard_compatible.json\n";
