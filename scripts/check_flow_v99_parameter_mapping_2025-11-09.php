<?php
/**
 * Check Flow V99 parameter_mapping
 */

$apiKey = 'key_6ff998ba48e842092e04a5455d19';
$flowId = 'conversation_flow_a58405e3f67a';

echo "=== FLOW V99 PARAMETER MAPPING CHECK ===\n\n";

// Get flow
$ch = curl_init("https://api.retellai.com/get-conversation-flow/{$flowId}");
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        "Authorization: Bearer {$apiKey}",
        "Content-Type: application/json"
    ]
]);

$response = curl_exec($ch);
$flow = json_decode($response, true);
curl_close($ch);

echo "Flow Version: V{$flow['version']}\n";
echo "Published: " . ($flow['is_published'] ? 'YES' : 'NO') . "\n";
echo "Tools: " . count($flow['tools']) . "\n\n";

// Check get_current_context specifically
foreach ($flow['tools'] as $tool) {
    if ($tool['name'] === 'get_current_context') {
        echo "=== get_current_context Tool ===\n";
        echo "Name: {$tool['name']}\n";
        echo "Type: {$tool['type']}\n";

        // Check parameters
        if (isset($tool['parameters']['properties']['call_id'])) {
            echo "✅ Has call_id parameter\n";
        } else {
            echo "❌ NO call_id parameter\n";
        }

        // Check parameter_mapping
        if (isset($tool['parameter_mapping'])) {
            echo "✅ Has parameter_mapping\n";
            echo "Mapping:\n";
            echo json_encode($tool['parameter_mapping'], JSON_PRETTY_PRINT) . "\n";

            if (isset($tool['parameter_mapping']['call_id'])) {
                $value = $tool['parameter_mapping']['call_id'];
                echo "\ncall_id mapping: {$value}\n";

                if ($value === '{{call_id}}') {
                    echo "✅ CORRECT\n";
                } else {
                    echo "❌ WRONG (should be {{call_id}})\n";
                }
            } else {
                echo "❌ NO call_id in parameter_mapping\n";
            }
        } else {
            echo "❌ NO parameter_mapping field\n";
        }

        break;
    }
}

echo "\n=== END CHECK ===\n";
