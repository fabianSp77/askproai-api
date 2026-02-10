<?php
/**
 * Add reschedule nodes to deployed conversation flow (nodes only)
 * @date 2025-11-25
 */

$retellApiKey = getenv('RETELL_TOKEN') ?: die("ERROR: RETELL_TOKEN not set. Export it first: export RETELL_TOKEN=your_key\n");
$flowId = 'conversation_flow_097b2c1c2bca';

echo "=== Adding Reschedule Nodes (nodes only) ===\n\n";

// 1. Load current flow
$flowJson = file_get_contents('/tmp/current_flow.json');
$flow = json_decode($flowJson, true);

if (!$flow || !isset($flow['nodes'])) {
    die("❌ Could not load flow JSON or no nodes found\n");
}

echo "✅ Loaded flow with " . count($flow['nodes']) . " nodes\n";

// 2. Define new nodes
$rescheduleNodes = [
    [
        'name' => 'Umbuchungsdaten sammeln',
        'id' => 'node_collect_reschedule_info',
        'type' => 'conversation',
        'display_position' => ['x' => 1300, 'y' => 1300],
        'instruction' => [
            'type' => 'prompt',
            'text' => "UMBUCHUNG SAMMELN:\n\n\"Kein Problem! Auf wann möchten Sie den Termin verschieben?\"\n\nWarte auf neues Datum und Zeit vom Kunden.\n\nSobald {{new_datum}} AND {{new_uhrzeit}} vorhanden → zu func_reschedule_appointment"
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

// 3. Add nodes
$flow['nodes'] = array_merge($flow['nodes'], $rescheduleNodes);

// 4. Add edge from intent_router
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
        echo "✅ Added edge to intent_router\n";
        break;
    }
}

// 5. Create update payload with only nodes
$updatePayload = [
    'nodes' => $flow['nodes']
];

file_put_contents('/tmp/flow_nodes_update.json', json_encode($updatePayload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
echo "✅ Saved update payload to /tmp/flow_nodes_update.json\n";

// 6. Update via API (PATCH with only nodes)
echo "\n🚀 Updating flow via API...\n";

$ch = curl_init("https://api.retellai.com/update-conversation-flow/{$flowId}");
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PATCH');
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($updatePayload));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $retellApiKey,
    'Content-Type: application/json'
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode >= 200 && $httpCode < 300) {
    echo "✅ Flow updated! HTTP {$httpCode}\n";
    $responseData = json_decode($response, true);
    echo "   Node count: " . count($responseData['nodes'] ?? []) . "\n";
    
    foreach ($responseData['nodes'] ?? [] as $node) {
        if ($node['id'] === 'intent_router') {
            $dests = array_column($node['edges'], 'destination_node_id');
            if (in_array('node_collect_reschedule_info', $dests)) {
                echo "   ✅ VERIFIED: Reschedule edge in intent_router!\n";
            }
            break;
        }
    }
} else {
    echo "❌ Failed. HTTP {$httpCode}\n";
    echo "Response: " . $response . "\n";
}
