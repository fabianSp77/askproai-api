<?php

$agentId = 'agent_45daa54928c5768b52ba3db736';
$apiKey = 'key_6ff998ba48e842092e04a5455d19';

$ch = curl_init("https://api.retellai.com/get-agent/{$agentId}");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer {$apiKey}",
    "Content-Type: application/json"
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode !== 200) {
    echo "Error: HTTP {$httpCode}\n";
    echo $response . "\n";
    exit(1);
}

$agent = json_decode($response, true);

echo "Agent Name: " . ($agent['agent_name'] ?? 'N/A') . "\n";
echo "Agent ID: " . ($agent['agent_id'] ?? 'N/A') . "\n";
echo "Response Engine Type: " . ($agent['response_engine']['type'] ?? 'N/A') . "\n";

if (isset($agent['response_engine']['llm_id'])) {
    echo "LLM ID: " . $agent['response_engine']['llm_id'] . "\n";

    // Now get the LLM config
    $llmId = $agent['response_engine']['llm_id'];
    $ch = curl_init("https://api.retellai.com/get-retell-llm/{$llmId}");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: Bearer {$apiKey}",
        "Content-Type: application/json"
    ]);

    $llmResponse = curl_exec($ch);
    curl_close($ch);

    $llm = json_decode($llmResponse, true);

    if (isset($llm['general_tools'])) {
        echo "\n=== Tools Configuration ===\n";
        foreach ($llm['general_tools'] as $tool) {
            echo "\nTool: " . ($tool['name'] ?? 'N/A') . "\n";
            echo "Type: " . ($tool['type'] ?? 'N/A') . "\n";

            if (isset($tool['parameters']['properties'])) {
                echo "Parameters:\n";
                foreach ($tool['parameters']['properties'] as $paramName => $paramConfig) {
                    $required = in_array($paramName, $tool['parameters']['required'] ?? []) ? ' (required)' : '';
                    echo "  - {$paramName}: {$paramConfig['type']}{$required}\n";

                    // Check if call_id exists
                    if ($paramName === 'call_id') {
                        echo "    ✅ call_id parameter EXISTS\n";
                        echo "    Description: " . ($paramConfig['description'] ?? 'N/A') . "\n";
                    }
                }

                // Check if call_id is missing
                if (!isset($tool['parameters']['properties']['call_id'])) {
                    echo "  ❌ call_id parameter MISSING\n";
                }
            }
        }
    }
} else {
    echo "No LLM ID found (might be custom LLM)\n";
}
