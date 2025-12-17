<?php
/**
 * Add reschedule nodes to flow (correct tool_id)
 * @date 2025-11-25
 */

$retellApiKey = 'key_6ff998ba48e842092e04a5455d19';
$flowId = 'conversation_flow_097b2c1c2bca';

echo "=== Adding Reschedule Nodes (FINAL) ===\n\n";

$flowJson = file_get_contents('/tmp/current_flow.json');
$flow = json_decode($flowJson, true);

if (!$flow || !isset($flow['nodes'])) {
    die("❌ Could not load flow\n");
}

echo "✅ Loaded " . count($flow['nodes']) . " nodes\n";

// Reschedule nodes with CORRECT tool_id
$rescheduleNodes = [
    [
        'name' => 'Umbuchungsdaten sammeln',
        'id' => 'node_collect_reschedule_info',
        'type' => 'conversation',
        'display_position' => ['x' => 1300, 'y' => 1300],
        'instruction' => [
            'type' => 'prompt',
            'text' => "UMBUCHUNG SAMMELN:\n\n\"Kein Problem! Auf wann möchten Sie den Termin verschieben?\"\n\nSammle neues Datum und Zeit.\nSobald {{new_datum}} UND {{new_uhrzeit}} vorhanden → func_reschedule_appointment"
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
        'tool_id' => 'reschedule_appointment',  // ✅ CORRECT tool_id
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

$flow['nodes'] = array_merge($flow['nodes'], $rescheduleNodes);

// Add intent_router edge
$rescheduleEdge = [
    'id' => 'edge_intent_to_reschedule',
    'destination_node_id' => 'node_collect_reschedule_info',
    'transition_condition' => [
        'type' => 'prompt',
        'prompt' => 'Anrufer möchte Termin verschieben: keywords (umbuchen, verschieben, anderen Tag)'
    ]
];

foreach ($flow['nodes'] as &$node) {
    if ($node['id'] === 'intent_router') {
        $node['edges'][] = $rescheduleEdge;
        echo "✅ Added reschedule edge to intent_router\n";
        break;
    }
}

$updatePayload = ['nodes' => $flow['nodes']];

echo "\n🚀 Updating flow...\n";

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
    echo "✅ SUCCESS! HTTP {$httpCode}\n\n";
    $resp = json_decode($response, true);
    echo "   Nodes: " . count($resp['nodes'] ?? []) . "\n";
    
    // Verify
    foreach ($resp['nodes'] ?? [] as $n) {
        if ($n['id'] === 'intent_router') {
            $dests = array_column($n['edges'], 'destination_node_id');
            echo "   Intent router edges: " . implode(', ', $dests) . "\n";
            if (in_array('node_collect_reschedule_info', $dests)) {
                echo "\n   🎉 RESCHEDULE ROUTING ACTIVE!\n";
            }
            break;
        }
    }
} else {
    echo "❌ FAILED HTTP {$httpCode}\n";
    echo "Response: {$response}\n";
}
