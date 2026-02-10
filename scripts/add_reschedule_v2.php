<?php
/**
 * Add reschedule nodes - v2 with correct node references
 */

$retellApiKey = getenv('RETELL_TOKEN') ?: die("ERROR: RETELL_TOKEN not set. Export it first: export RETELL_TOKEN=your_key\n");
$flowId = 'conversation_flow_097b2c1c2bca';

echo "=== Adding Reschedule Nodes (v2) ===\n\n";

$flow = json_decode(file_get_contents('/tmp/current_flow.json'), true);
echo "✅ Loaded " . count($flow['nodes']) . " nodes\n";

// Reschedule nodes with correct references
$rescheduleNodes = [
    [
        'name' => 'Umbuchungsdaten sammeln',
        'id' => 'node_collect_reschedule_info',
        'type' => 'conversation',
        'display_position' => ['x' => 1300, 'y' => 1300],
        'instruction' => [
            'type' => 'prompt',
            'text' => "UMBUCHUNG:\n\n\"Kein Problem! Auf wann möchten Sie den Termin verschieben?\"\n\nSammle {{new_datum}} und {{new_uhrzeit}}.\n\nWenn beides vorhanden → func_reschedule_appointment"
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
        'tool_id' => 'reschedule_appointment',
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
                'destination_node_id' => 'node_personalized_followup',  // ✅ Existing node
                'transition_condition' => [
                    'type' => 'prompt',
                    'prompt' => 'Rescheduling failed'
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
            'text' => 'Perfekt! Ihr Termin wurde erfolgreich verschoben. Kann ich noch etwas für Sie tun?'
        ],
        'edges' => [
            [
                'id' => 'edge_reschedule_success_to_followup',
                'destination_node_id' => 'node_personalized_followup',  // ✅ Existing node
                'transition_condition' => [
                    'type' => 'prompt',
                    'prompt' => 'Always'
                ]
            ]
        ]
    ]
];

$flow['nodes'] = array_merge($flow['nodes'], $rescheduleNodes);

// Intent router edge
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
        echo "✅ Added reschedule edge\n";
        break;
    }
}

echo "\n🚀 Updating...\n";

$ch = curl_init("https://api.retellai.com/update-conversation-flow/{$flowId}");
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PATCH');
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['nodes' => $flow['nodes']]));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $retellApiKey,
    'Content-Type: application/json'
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode >= 200 && $httpCode < 300) {
    echo "✅ SUCCESS! HTTP {$httpCode}\n";
    $resp = json_decode($response, true);
    echo "   Nodes: " . count($resp['nodes'] ?? []) . "\n";
    
    foreach ($resp['nodes'] ?? [] as $n) {
        if ($n['id'] === 'intent_router') {
            $dests = array_column($n['edges'], 'destination_node_id');
            echo "   Intent edges: " . implode(', ', $dests) . "\n";
            if (in_array('node_collect_reschedule_info', $dests)) {
                echo "\n   🎉 RESCHEDULE NOW ACTIVE!\n";
            }
        }
    }
} else {
    echo "❌ FAILED HTTP {$httpCode}\n{$response}\n";
}
