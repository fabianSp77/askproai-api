<?php
/**
 * Get Agent configuration including tools
 */

$apiKey = 'key_6ff998ba48e842092e04a5455d19';
$agentId = 'agent_9a8202a740cd3120d96fcfda1e';

echo "=== AGENT + TOOLS CONFIGURATION ===\n\n";

// 1. Get Agent
echo "1. Fetching agent...\n";
$ch = curl_init("https://api.retellai.com/get-agent/{$agentId}");
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        "Authorization: Bearer {$apiKey}",
        "Content-Type: application/json"
    ]
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode !== 200) {
    die("❌ Failed to fetch agent. HTTP {$httpCode}\n");
}

$agent = json_decode($response, true);
echo "✅ Agent: {$agent['agent_name']}\n\n";

// 2. Check Tools (LLM functions)
echo "2. Checking LLM Functions...\n";
if (isset($agent['llm_websocket_url_config']['llm_websocket_url'])) {
    echo "  Using LLM WebSocket\n";
}

if (isset($agent['response_engine'])) {
    $engine = $agent['response_engine'];
    echo "  Response Engine: " . ($engine['type'] ?? 'N/A') . "\n";
}

// Check for function calling configuration
if (isset($agent['response_engine']['llm_functions'])) {
    $functions = $agent['response_engine']['llm_functions'];
    echo "\n  📋 LLM Functions found: " . count($functions) . "\n\n";

    foreach ($functions as $func) {
        $name = $func['name'] ?? 'unknown';
        echo "  Function: {$name}\n";

        if (isset($func['parameters'])) {
            echo "    Parameters: " . json_encode($func['parameters']) . "\n";
        }

        if (isset($func['parameter_mapping'])) {
            echo "    ✅ parameter_mapping: " . json_encode($func['parameter_mapping']) . "\n";
        } else {
            echo "    ❌ NO parameter_mapping\n";
        }

        if (isset($func['url'])) {
            echo "    URL: {$func['url']}\n";
        }

        echo "\n";
    }
} else {
    echo "  ❌ No llm_functions found in response_engine\n";
}

// 3. Check Conversation Flow reference
echo "3. Conversation Flow:\n";
if (isset($agent['conversation_flow_id'])) {
    echo "  Flow ID: {$agent['conversation_flow_id']}\n";
} else {
    echo "  ❌ No conversation_flow_id\n";
}

// 4. Save full agent config for inspection
$filename = '/var/www/api-gateway/agent_config_with_tools_2025-11-09.json';
file_put_contents($filename, json_encode($agent, JSON_PRETTY_PRINT));
echo "\n✅ Full agent config saved to: {$filename}\n";

echo "\n=== END ===\n";
