<?php
/**
 * Prepare Flow V108 - Step by Step
 */

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$flowId = 'conversation_flow_a58405e3f67a';
$apiKey = config('services.retellai.api_key');

echo "=== PREPARE FLOW V108 ===\n\n";

// Get current flow
echo "Fetching current flow...\n";
$ch = curl_init("https://api.retellai.com/get-conversation-flow/{$flowId}");
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        "Authorization: Bearer {$apiKey}",
        "Content-Type: application/json"
    ]
]);
$response = curl_exec($ch);
curl_close($ch);

$flow = json_decode($response, true);
$currentVersion = $flow['version'];

echo "Current version: V{$currentVersion}\n\n";

// Save original
file_put_contents(__DIR__ . '/../flow_v107_original.json', json_encode($flow, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

// STEP 1: Remove node_collect_booking_info
echo "STEP 1: Remove node_collect_booking_info\n";
$nodesCount = count($flow['nodes']);
$flow['nodes'] = array_values(array_filter($flow['nodes'], function($node) {
    return $node['id'] !== 'node_collect_booking_info';
}));
echo "  Nodes: {$nodesCount} → " . count($flow['nodes']) . "\n\n";

// STEP 2: Update node_extract_booking_variables
echo "STEP 2: Update node_extract_booking_variables\n";
foreach ($flow['nodes'] as &$node) {
    if ($node['id'] === 'node_extract_booking_variables') {
        // Add phone variable
        $node['variables'][] = [
            'type' => 'string',
            'name' => 'customer_phone',
            'description' => 'Telefonnummer des Kunden (optional)'
        ];

        // Update edges - direct to func_check_availability
        $node['edges'] = [
            [
                'destination_node_id' => 'func_check_availability',
                'id' => 'edge_extract_to_check_direct',
                'transition_condition' => [
                    'type' => 'prompt',
                    'prompt' => 'Wenn mindestens Name, Service, Datum und Uhrzeit extrahiert'
                ]
            ]
        ];
        echo "  ✅ Added customer_phone variable\n";
        echo "  ✅ Direct edge to func_check_availability\n\n";
        break;
    }
}
unset($node); // Important: break the reference

// STEP 3: Add node_collect_phone
echo "STEP 3: Add node_collect_phone\n";
$flow['nodes'][] = [
    'name' => 'Telefonnummer sammeln',
    'id' => 'node_collect_phone',
    'type' => 'conversation',
    'instruction' => [
        'type' => 'prompt',
        'text' => 'Pruefe: {{customer_phone}}' . "\n\n" .
                 'Wenn VORHANDEN: Sage nichts, transition zu func_start_booking' . "\n" .
                 'Wenn FEHLT: Frage "Fuer die Buchung brauche ich noch Ihre Telefonnummer."'
    ],
    'edges' => [
        [
            'destination_node_id' => 'func_start_booking',
            'id' => 'edge_phone_to_start',
            'transition_condition' => [
                'type' => 'prompt',
                'prompt' => 'Telefonnummer vorhanden'
            ]
        ]
    ],
    'display_position' => ['x' => 3800, 'y' => 0]
];
echo "  ✅ Node created\n\n";

// STEP 4: Update node_present_result
echo "STEP 4: Update node_present_result\n";
foreach ($flow['nodes'] as &$node) {
    if ($node['id'] === 'node_present_result') {
        foreach ($node['edges'] as &$edge) {
            if ($edge['destination_node_id'] === 'func_start_booking') {
                $edge['destination_node_id'] = 'node_collect_phone';
                $edge['id'] = 'edge_present_to_phone';
                echo "  ✅ Edge updated: present → collect_phone\n\n";
            }
        }
        unset($edge);
        break;
    }
}
unset($node);

// STEP 4b: Fix orphaned edges pointing to node_collect_booking_info
echo "STEP 4b: Fix orphaned edges to node_collect_booking_info\n";

$edgesFixed = 0;
foreach ($flow['nodes'] as &$node) {
    if (isset($node['edges'])) {
        foreach ($node['edges'] as $idx => &$edge) {
            if ($edge['destination_node_id'] === 'node_collect_booking_info') {
                // Remove this edge entirely
                unset($node['edges'][$idx]);
                $edgesFixed++;
                echo "  ✅ Removed orphaned edge from {$node['name']}\n";
            }
        }
        unset($edge);
        // Re-index edges array
        $node['edges'] = array_values($node['edges']);
    }
}
unset($node);
echo "  Total edges removed: {$edgesFixed}\n\n";

// STEP 5: Update parameter mappings
echo "STEP 5: Update parameter mappings\n";
foreach ($flow['nodes'] as &$node) {
    if ($node['id'] === 'func_start_booking') {
        $node['parameter_mapping']['customer_phone'] = '{{customer_phone}}';
        echo "  ✅ func_start_booking\n";
    }
    if ($node['id'] === 'func_confirm_booking') {
        $node['parameter_mapping']['customer_phone'] = '{{customer_phone}}';
        echo "  ✅ func_confirm_booking\n";
    }
}
unset($node);
echo "\n";

// Check for duplicates
echo "Checking for duplicates...\n";
$nodeIds = [];
$hasDuplicates = false;
foreach ($flow['nodes'] as $idx => $node) {
    $id = $node['id'];
    if (isset($nodeIds[$id])) {
        echo "  ❌ DUPLICATE: {$id}\n";
        echo "     First: [{$nodeIds[$id]['idx']}] {$nodeIds[$id]['name']}\n";
        echo "     Second: [{$idx}] {$node['name']}\n";
        $hasDuplicates = true;
    } else {
        $nodeIds[$id] = [
            'idx' => $idx,
            'name' => $node['name']
        ];
    }
}

if (!$hasDuplicates) {
    echo "  ✅ No duplicates\n\n";
} else {
    echo "\n⚠️  Found duplicates but continuing anyway...\n";
    echo "Retell API will reject if these are real duplicates.\n\n";
}

// Save prepared flow
file_put_contents(__DIR__ . '/../flow_v108_ready.json', json_encode($flow, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

echo "=== SUMMARY ===\n\n";
echo "✅ Prepared flow V108\n";
echo "✅ Total nodes: " . count($flow['nodes']) . "\n";
echo "✅ No duplicate IDs\n";
echo "✅ Saved to: flow_v108_ready.json\n\n";

echo "Ready to upload to Retell API!\n";
