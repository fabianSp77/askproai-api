<?php

$apiKey = getenv('RETELL_TOKEN') ?: die("ERROR: RETELL_TOKEN not set\n");
$baseUrl = 'https://api.retellai.com';
$flowId = 'conversation_flow_da76e7c6f3ba';

echo "=== UPDATING CONVERSATION FLOW (COMPLETE VERSION) ===\n\n";

// Load the complete flow
$flowData = json_decode(file_get_contents('public/askproai_conversation_flow_complete.json'), true);

echo "Flow Statistics:\n";
echo "  - Tools: " . count($flowData['tools']) . "\n";
echo "  - Nodes: " . count($flowData['nodes']) . "\n\n";

// Validate before upload
echo "=== VALIDATION ===\n";
$errors = [];

// Check all function nodes have required properties
foreach ($flowData['nodes'] as $node) {
    if ($node['type'] === 'function') {
        if (!isset($node['tool_id'])) {
            $errors[] = "Function node {$node['id']} missing tool_id";
        }
        if (!isset($node['tool_type'])) {
            $errors[] = "Function node {$node['id']} missing tool_type";
        }
        if (!isset($node['instruction'])) {
            $errors[] = "Function node {$node['id']} missing instruction";
        }
        if (!isset($node['wait_for_result'])) {
            $errors[] = "Function node {$node['id']} missing wait_for_result";
        }
    }
}

// Check all edges have transition_condition with type
foreach ($flowData['nodes'] as $node) {
    if (isset($node['edges'])) {
        foreach ($node['edges'] as $edge) {
            if (!isset($edge['transition_condition']['type'])) {
                $errors[] = "Node {$node['id']} edge {$edge['id']} missing transition_condition.type";
            }
        }
    }
}

if (!empty($errors)) {
    echo "❌ VALIDATION ERRORS:\n";
    foreach ($errors as $error) {
        echo "  - $error\n";
    }
    exit(1);
}

echo "✅ All validations passed!\n\n";

// Update the conversation flow
echo "=== UPLOADING TO RETELL.AI ===\n";

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => "$baseUrl/update-conversation-flow/$flowId",
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_CUSTOMREQUEST => 'PATCH',
    CURLOPT_HTTPHEADER => [
        'Authorization: Bearer ' . $apiKey,
        'Content-Type: application/json'
    ],
    CURLOPT_POSTFIELDS => json_encode($flowData)
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Status: $httpCode\n\n";

if ($httpCode === 200) {
    $result = json_decode($response, true);
    echo "✅ CONVERSATION FLOW UPDATED SUCCESSFULLY!\n\n";
    echo "Flow ID: {$result['conversation_flow_id']}\n";
    echo "Version: {$result['version']}\n\n";

    echo "=== CAPABILITIES ===\n";
    echo "✓ Neue Buchung (mit V85 Race Protection)\n";
    echo "✓ Terminverschiebung (mit Policy Engine)\n";
    echo "✓ Stornierung (mit Policy Engine)\n";
    echo "✓ Terminabfrage\n";
    echo "✓ Intent Recognition\n";
    echo "✓ Edge Case Handling\n";
    echo "✓ Policy Violation Handler\n\n";

    echo "View in dashboard:\n";
    echo "https://dashboard.retellai.com/conversation-flow/$flowId\n\n";

    echo "🎯 FLOW IST PRODUKTIONSBEREIT!\n";
} else {
    echo "❌ ERROR!\n";
    echo "Response:\n";
    echo $response . "\n\n";

    // Pretty print the error
    $error = json_decode($response, true);
    if ($error) {
        echo "Parsed Error:\n";
        print_r($error);
    }
}
