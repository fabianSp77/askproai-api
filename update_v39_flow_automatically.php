<?php

require __DIR__ . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

$apiKey = $_ENV['RETELLAI_API_KEY'] ?? $_ENV['RETELL_TOKEN'] ?? null;
$flowId = 'conversation_flow_1607b81c8f93';

if (!$apiKey) {
    die("❌ No API key found!\n");
}

echo "═══════════════════════════════════════════════════════\n";
echo "🚀 AUTOMATIC V39 FLOW CANVAS FIX\n";
echo "═══════════════════════════════════════════════════════\n\n";

echo "🎯 Target Flow ID: $flowId\n\n";

// STEP 1: GET current flow
echo "📥 STEP 1: Fetching current flow...\n";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "https://api.retellai.com/get-conversation-flow/$flowId");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer $apiKey",
    "Content-Type: application/json"
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode !== 200) {
    die("❌ Failed to fetch flow! HTTP $httpCode\nResponse: $response\n");
}

$flow = json_decode($response, true);

if (!$flow || !isset($flow['nodes'])) {
    die("❌ Invalid flow structure!\n");
}

echo "✅ Flow fetched successfully!\n";
echo "   Nodes: " . count($flow['nodes']) . "\n";
echo "   Version: " . ($flow['version'] ?? 'N/A') . "\n\n";

// STEP 2: Find node_03c_anonymous_customer
echo "🔍 STEP 2: Finding node_03c_anonymous_customer...\n";

$node03cIndex = null;
foreach ($flow['nodes'] as $index => $node) {
    if ($node['id'] === 'node_03c_anonymous_customer') {
        $node03cIndex = $index;
        break;
    }
}

if ($node03cIndex === null) {
    die("❌ node_03c_anonymous_customer not found in flow!\n");
}

echo "✅ Found node_03c_anonymous_customer at index $node03cIndex\n\n";

// STEP 3: Check if check_availability function node already exists
echo "🔍 STEP 3: Checking for existing check_availability function node...\n";

$checkAvailNodeExists = false;
$checkAvailNodeId = null;

foreach ($flow['nodes'] as $node) {
    if ($node['type'] === 'function' &&
        isset($node['tool_id']) &&
        strpos($node['tool_id'], 'check_availability') !== false) {
        $checkAvailNodeExists = true;
        $checkAvailNodeId = $node['id'];
        break;
    }
}

if ($checkAvailNodeExists) {
    echo "✅ Function node already exists: $checkAvailNodeId\n";
    echo "   Will use existing node for edge connections.\n\n";
} else {
    echo "⚠️  No check_availability function node found.\n";
    echo "   Will create new function node.\n\n";

    // Create new function node
    $checkAvailNodeId = 'func_check_availability_auto_' . substr(md5(time()), 0, 8);

    $newFunctionNode = [
        'id' => $checkAvailNodeId,
        'name' => 'Check Availability',
        'type' => 'function',
        'tool_id' => 'tool-v17-check-availability', // From your dropdown list
        'tool_type' => 'local',
        'instruction' => [
            'type' => 'static_text',
            'text' => 'Einen Moment bitte, ich prüfe die Verfügbarkeit für Sie...'
        ],
        'speak_during_execution' => true,
        'wait_for_result' => true,
        'edges' => [],
        'display_position' => [
            'x' => 1800,
            'y' => 1400
        ]
    ];

    // Add to nodes array
    $flow['nodes'][] = $newFunctionNode;

    echo "✅ Created new function node: $checkAvailNodeId\n\n";
}

// STEP 4: Add edge from node_03c to check_availability node
echo "🔗 STEP 4: Adding edge from node_03c → check_availability...\n";

$edgeAlreadyExists = false;
foreach ($flow['nodes'][$node03cIndex]['edges'] ?? [] as $edge) {
    if ($edge['destination_node_id'] === $checkAvailNodeId) {
        $edgeAlreadyExists = true;
        break;
    }
}

if ($edgeAlreadyExists) {
    echo "✅ Edge already exists!\n\n";
} else {
    // Add edge to node_03c
    if (!isset($flow['nodes'][$node03cIndex]['edges'])) {
        $flow['nodes'][$node03cIndex]['edges'] = [];
    }

    $newEdge = [
        'id' => 'edge_03c_to_check_avail_' . substr(md5(time()), 0, 8),
        'destination_node_id' => $checkAvailNodeId,
        'transition_condition' => [
            'type' => 'prompt',
            'prompt' => 'User wants to book an appointment or check availability'
        ]
    ];

    $flow['nodes'][$node03cIndex]['edges'][] = $newEdge;

    echo "✅ Edge added: node_03c_anonymous_customer → $checkAvailNodeId\n\n";
}

// STEP 5: Add success edge from check_availability to next node
echo "🔗 STEP 5: Adding success edge from check_availability...\n";

// Find the check_availability node in the updated nodes array
$checkAvailNodeIndex = null;
foreach ($flow['nodes'] as $index => $node) {
    if ($node['id'] === $checkAvailNodeId) {
        $checkAvailNodeIndex = $index;
        break;
    }
}

// Find a suitable next node (look for conversation nodes after node_03c)
$nextNodeId = null;
foreach ($flow['nodes'] as $node) {
    if ($node['type'] === 'conversation' &&
        $node['id'] !== 'node_03c_anonymous_customer' &&
        stripos($node['id'], 'present') !== false || stripos($node['id'], 'availability') !== false) {
        $nextNodeId = $node['id'];
        break;
    }
}

// If no specific next node found, use a general conversation node
if (!$nextNodeId) {
    foreach ($flow['nodes'] as $node) {
        if ($node['type'] === 'conversation' && $node['id'] !== 'node_03c_anonymous_customer') {
            $nextNodeId = $node['id'];
            break;
        }
    }
}

if ($nextNodeId && $checkAvailNodeIndex !== null) {
    if (!isset($flow['nodes'][$checkAvailNodeIndex]['edges'])) {
        $flow['nodes'][$checkAvailNodeIndex]['edges'] = [];
    }

    // Check if edge already exists
    $successEdgeExists = false;
    foreach ($flow['nodes'][$checkAvailNodeIndex]['edges'] as $edge) {
        if ($edge['destination_node_id'] === $nextNodeId) {
            $successEdgeExists = true;
            break;
        }
    }

    if (!$successEdgeExists) {
        $flow['nodes'][$checkAvailNodeIndex]['edges'][] = [
            'id' => 'edge_check_avail_success_' . substr(md5(time()), 0, 8),
            'destination_node_id' => $nextNodeId,
            'transition_condition' => [
                'type' => 'prompt',
                'prompt' => 'Availability check completed successfully'
            ]
        ];

        echo "✅ Success edge added: $checkAvailNodeId → $nextNodeId\n\n";
    } else {
        echo "✅ Success edge already exists!\n\n";
    }
} else {
    echo "⚠️  Could not find suitable next node. You may need to add edges manually.\n\n";
}

// STEP 6: Update flow via PATCH API
echo "📤 STEP 6: Updating flow via PATCH API...\n";

$updatePayload = json_encode([
    'nodes' => $flow['nodes']
], JSON_PRETTY_PRINT);

echo "Payload size: " . strlen($updatePayload) . " bytes\n";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "https://api.retellai.com/update-conversation-flow/$flowId");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PATCH");
curl_setopt($ch, CURLOPT_POSTFIELDS, $updatePayload);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer $apiKey",
    "Content-Type: application/json"
]);

$updateResponse = curl_exec($ch);
$updateHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($updateHttpCode !== 200) {
    echo "❌ Failed to update flow! HTTP $updateHttpCode\n";
    echo "Response: $updateResponse\n";
    exit(1);
}

$updatedFlow = json_decode($updateResponse, true);

echo "✅ Flow updated successfully!\n";
echo "   New Version: " . ($updatedFlow['version'] ?? 'N/A') . "\n\n";

// STEP 7: Summary
echo "═══════════════════════════════════════════════════════\n";
echo "🎉 SUCCESS! V39 FLOW CANVAS FIX DEPLOYED!\n";
echo "═══════════════════════════════════════════════════════\n\n";

echo "✅ Changes Applied:\n";
echo "   • Added/verified check_availability function node\n";
echo "   • Added edge: node_03c_anonymous_customer → check_availability\n";
echo "   • Added success edge from check_availability\n";
echo "   • Flow updated to version: " . ($updatedFlow['version'] ?? 'N/A') . "\n\n";

echo "🧪 NEXT STEPS:\n";
echo "1. Wait 60 seconds for deployment\n";
echo "2. Make test call: +493033081738\n";
echo "3. Say: \"Termin heute 16 Uhr für Herrenhaarschnitt\"\n";
echo "4. Expected: Agent pauses, then gives CORRECT availability\n";
echo "5. Check function traces in admin panel:\n";
echo "   https://api.askproai.de/admin/retell-call-sessions\n\n";

echo "🎯 Expected Behavior:\n";
echo "   ✅ Agent says: \"Einen Moment, ich prüfe...\"\n";
echo "   ✅ 2-3 second pause (function executing)\n";
echo "   ✅ check_availability appears in logs\n";
echo "   ✅ Agent gives CORRECT availability (no hallucination!)\n\n";

echo "═══════════════════════════════════════════════════════\n\n";
