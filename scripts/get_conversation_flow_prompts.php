<?php

/**
 * Get Conversation Flow Prompts for Analysis
 */

$apiKey = 'key_6ff998ba48e842092e04a5455d19';
$flowId = 'conversation_flow_a58405e3f67a';

echo "📥 FETCHING CONVERSATION FLOW\n";
echo str_repeat('=', 80) . "\n\n";

// Get flow
$ch = curl_init("https://api.retellai.com/get-conversation-flow/{$flowId}");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer {$apiKey}",
    "Content-Type: application/json"
]);
$flowResponse = curl_exec($ch);
curl_close($ch);

$flow = json_decode($flowResponse, true);

echo "Flow Version: V{$flow['version']}\n";
echo "Flow Name: {$flow['name']}\n\n";

echo "🎯 CRITICAL NODES TO EXAMINE:\n";
echo str_repeat('=', 80) . "\n\n";

$criticalNodes = [
    'intent_router' => 'Intent Erkennung',
    'node_collect_booking_info' => 'Buchungsdaten sammeln',
    'node_present_result' => 'Ergebnis zeigen'
];

foreach ($flow['nodes'] as $node) {
    if (in_array($node['id'], array_keys($criticalNodes)) ||
        in_array($node['name'], array_values($criticalNodes))) {

        echo "📍 NODE: {$node['name']} (ID: {$node['id']})\n";
        echo str_repeat('-', 80) . "\n";
        echo "Type: {$node['type']}\n\n";

        if (isset($node['prompt'])) {
            echo "PROMPT:\n";
            echo wordwrap($node['prompt'], 78) . "\n\n";
        }

        if (isset($node['messages']) && is_array($node['messages'])) {
            echo "MESSAGES:\n";
            foreach ($node['messages'] as $msg) {
                echo "  - {$msg}\n";
            }
            echo "\n";
        }

        if (isset($node['system_prompt'])) {
            echo "SYSTEM PROMPT:\n";
            echo wordwrap($node['system_prompt'], 78) . "\n\n";
        }

        if (isset($node['destination'])) {
            echo "Destination: {$node['destination']}\n";
        }

        if (isset($node['destinations']) && is_array($node['destinations'])) {
            echo "Destinations:\n";
            foreach ($node['destinations'] as $dest) {
                echo "  - {$dest['name']} → {$dest['node_id']}\n";
            }
        }

        echo "\n" . str_repeat('=', 80) . "\n\n";
    }
}

// Save full flow to file for detailed analysis
file_put_contents('conversation_flow_v22.json', json_encode($flow, JSON_PRETTY_PRINT));
echo "✅ Full flow saved to: conversation_flow_v22.json\n";
