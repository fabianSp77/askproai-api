<?php
/**
 * Add reschedule nodes to deployed conversation flow
 * 
 * Problem: reschedule_appointment tool exists but no nodes use it
 * Solution: Add collect_reschedule_info + func_reschedule + success nodes
 * 
 * @date 2025-11-25
 */

$retellApiKey = getenv('RETELL_TOKEN') ?: die("ERROR: RETELL_TOKEN not set. Export it first: export RETELL_TOKEN=your_key\n");
$flowId = 'conversation_flow_097b2c1c2bca';

echo "=== Adding Reschedule Nodes to Flow ===\n\n";

// 1. Load current flow
$flowJson = file_get_contents('/tmp/current_flow.json');
$flow = json_decode($flowJson, true);

if (!$flow) {
    die("❌ Could not load flow JSON\n");
}

echo "✅ Loaded flow: {$flow['conversation_flow_name']}\n";
echo "   Current nodes: " . count($flow['nodes']) . "\n";
echo "   Current edges in intent_router: ";

// Find intent_router and count edges
foreach ($flow['nodes'] as $node) {
    if ($node['id'] === 'intent_router') {
        echo count($node['edges']) . "\n";
        echo "   Edge destinations: " . implode(', ', array_column($node['edges'], 'destination_node_id')) . "\n";
        break;
    }
}

// 2. Define new nodes
$rescheduleNodes = [
    // Node 1: Collect reschedule info (conversation node)
    [
        'name' => 'Umbuchungsdaten sammeln',
        'id' => 'node_collect_reschedule_info',
        'type' => 'conversation',
        'display_position' => ['x' => 1300, 'y' => 1300],
        'instruction' => [
            'type' => 'prompt',
            'text' => "UMBUCHUNG SAMMELN:\n\n\"Kein Problem! Auf wann möchten Sie den Termin verschieben?\"\n\nWarte auf neues Datum und Zeit.\n\nSobald {{new_datum}} AND {{new_uhrzeit}} vorhanden → zu func_reschedule_appointment"
        ],
        'edges' => [
            [
                'id' => 'edge_collect_reschedule_to_func',
                'destination_node_id' => 'func_reschedule_appointment',
                'transition_condition' => [
                    'type' => 'equation',
                    'equations' => [
                        ['left' => 'new_datum', 'operator' => 'exists'],
                        ['left' => 'new_uhrzeit', 'operator' => 'exists']
                    ],
                    'operator' => '&&'
                ]
            ]
        ]
    ],
    // Node 2: Execute reschedule (function node)
    [
        'name' => 'Termin verschieben',
        'id' => 'func_reschedule_appointment',
        'type' => 'function',
        'tool_type' => 'local',
        'tool_id' => 'tool-reschedule-appointment',
        'display_position' => ['x' => 1600, 'y' => 1300],
        'instruction' => [
            'type' => 'static_text',
            'text' => 'Einen Moment, ich verschiebe Ihren Termin...'
        ],
        'parameter_mapping' => [
            'call_id' => '{{call_id}}',
            'new_datum' => '{{new_datum}}',
            'new_uhrzeit' => '{{new_uhrzeit}}'
        ],
        'wait_for_result' => true,
        'speak_during_execution' => false,
        'edges' => [
            [
                'id' => 'edge_reschedule_to_success',
                'destination_node_id' => 'node_reschedule_success',
                'transition_condition' => [
                    'type' => 'prompt',
                    'prompt' => 'Rescheduling successful'
                ]
            ],
            [
                'id' => 'edge_reschedule_to_error',
                'destination_node_id' => 'node_ask_anything_else',
                'transition_condition' => [
                    'type' => 'prompt',
                    'prompt' => 'Rescheduling failed or not possible'
                ]
            ]
        ]
    ],
    // Node 3: Success confirmation
    [
        'name' => 'Verschiebung bestätigt',
        'id' => 'node_reschedule_success',
        'type' => 'conversation',
        'display_position' => ['x' => 1900, 'y' => 1300],
        'instruction' => [
            'type' => 'static_text',
            'text' => 'Perfekt! Ihr Termin wurde erfolgreich verschoben auf {{new_datum}} um {{new_uhrzeit}} Uhr.'
        ],
        'edges' => [
            [
                'id' => 'edge_reschedule_success_to_end',
                'destination_node_id' => 'node_ask_anything_else',
                'transition_condition' => [
                    'type' => 'prompt',
                    'prompt' => 'Always'
                ]
            ]
        ]
    ]
];

// 3. Add nodes to flow
$flow['nodes'] = array_merge($flow['nodes'], $rescheduleNodes);
echo "\n✅ Added 3 reschedule nodes\n";

// 4. Add edge from intent_router to reschedule
$rescheduleEdge = [
    'id' => 'edge_intent_to_reschedule',
    'destination_node_id' => 'node_collect_reschedule_info',
    'transition_condition' => [
        'type' => 'prompt',
        'prompt' => 'Anrufer möchte Termin verschieben: keywords (umbuchen, verschieben, anderen Tag, anderen Termin)'
    ]
];

foreach ($flow['nodes'] as &$node) {
    if ($node['id'] === 'intent_router') {
        $node['edges'][] = $rescheduleEdge;
        echo "✅ Added reschedule edge to intent_router\n";
        break;
    }
}

// 5. Save updated flow
file_put_contents('/tmp/flow_updated.json', json_encode($flow, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
echo "\n✅ Saved updated flow to /tmp/flow_updated.json\n";

// 6. Update via API
echo "\n🚀 Updating flow via Retell API...\n";

$ch = curl_init("https://api.retellai.com/update-conversation-flow/{$flowId}");
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PATCH');
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($flow));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $retellApiKey,
    'Content-Type: application/json'
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode >= 200 && $httpCode < 300) {
    echo "✅ Flow updated successfully! HTTP {$httpCode}\n";
    
    $responseData = json_decode($response, true);
    echo "   New node count: " . count($responseData['nodes'] ?? []) . "\n";
    
    // Verify reschedule edge was added
    foreach ($responseData['nodes'] ?? [] as $node) {
        if ($node['id'] === 'intent_router') {
            $edgeIds = array_column($node['edges'], 'destination_node_id');
            if (in_array('node_collect_reschedule_info', $edgeIds)) {
                echo "   ✅ Reschedule edge verified in intent_router\n";
            }
            break;
        }
    }
} else {
    echo "❌ Failed to update flow. HTTP {$httpCode}\n";
    echo "Response: " . substr($response, 0, 500) . "\n";
}

echo "\n=== Done ===\n";
