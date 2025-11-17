#!/usr/bin/env php
<?php

require __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

$apiKey = $_ENV['RETELLAI_API_KEY'] ?? $_ENV['RETELL_TOKEN'];
$baseUrl = 'https://api.retellai.com';
$agentId = 'agent_c1d8dea0445f375857a55ffd61';

echo "\n";
echo "═══════════════════════════════════════════════════════════\n";
echo "  FULL AGENT CONFIGURATION\n";
echo "═══════════════════════════════════════════════════════════\n\n";

echo "Agent ID: $agentId\n\n";

// Get agent details
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "$baseUrl/get-agent/$agentId");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer $apiKey"
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode !== 200) {
    echo "❌ Failed to fetch agent\n";
    echo "HTTP Status: $httpCode\n";
    echo "Response: $response\n";
    exit(1);
}

$agent = json_decode($response, true);

echo "Agent Name: " . ($agent['agent_name'] ?? 'N/A') . "\n";
echo "Agent ID: " . ($agent['agent_id'] ?? 'N/A') . "\n\n";

echo "═══════════════════════════════════════════════════════════\n";
echo "  RESPONSE ENGINE\n";
echo "═══════════════════════════════════════════════════════════\n\n";

if (isset($agent['response_engine'])) {
    $engine = $agent['response_engine'];

    echo "Type: " . ($engine['type'] ?? 'N/A') . "\n";

    if (isset($engine['llm_id'])) {
        echo "LLM ID: " . $engine['llm_id'] . "\n";
    }

    if (isset($engine['conversation_config_id'])) {
        echo "🔄 Conversation Flow ID: " . $engine['conversation_config_id'] . "\n";
        echo "\n";
        echo "Expected V109 Flow ID: conversation_flow_a58405e3f67a\n";
        echo "Actual Flow ID:        " . $engine['conversation_config_id'] . "\n\n";

        if ($engine['conversation_config_id'] === 'conversation_flow_a58405e3f67a') {
            echo "✅ CORRECT FLOW ASSIGNED!\n";
            echo "   Agent has V109 conversation flow\n\n";
        } else {
            echo "❌ WRONG FLOW!\n";
            echo "   Agent does NOT have V109 flow\n\n";
        }
    } else {
        echo "⚠️  No conversation_config_id found!\n";
        echo "   Agent might be using old LLM-only configuration\n\n";
    }

    if (isset($engine['begin_message'])) {
        echo "\nBegin Message: " . substr($engine['begin_message'], 0, 100) . "...\n";
    }

    if (isset($engine['general_prompt'])) {
        echo "\nGeneral Prompt (first 200 chars):\n";
        echo substr($engine['general_prompt'], 0, 200) . "...\n";
    }

    if (isset($engine['general_tools'])) {
        echo "\nGeneral Tools (" . count($engine['general_tools']) . " tools):\n";
        foreach ($engine['general_tools'] as $tool) {
            echo "  - " . ($tool['name'] ?? 'N/A') . "\n";
        }
    }
} else {
    echo "❌ No response_engine found!\n";
}

echo "\n";
echo "═══════════════════════════════════════════════════════════\n";
echo "  ANALYSIS\n";
echo "═══════════════════════════════════════════════════════════\n\n";

$agentName = $agent['agent_name'] ?? '';
$hasV109Flow = isset($agent['response_engine']['conversation_config_id']) &&
               $agent['response_engine']['conversation_config_id'] === 'conversation_flow_a58405e3f67a';

if ($hasV109Flow) {
    echo "✅ Agent HAS V109 flow assigned\n";

    if (strpos($agentName, 'V110') !== false) {
        echo "⚠️  BUT: Agent name still says 'V110'\n";
        echo "   This is just cosmetic - the flow itself is V109\n";
        echo "   The agent SHOULD work correctly despite the name\n";
    } else {
        echo "✅ Agent name is correct\n";
    }
} else {
    echo "❌ Agent DOES NOT have V109 flow!\n";
    echo "   This explains why phone calls fail\n";
    echo "   Need to update agent configuration\n";
}

echo "\n";
echo "═══════════════════════════════════════════════════════════\n";
echo "  FULL JSON\n";
echo "═══════════════════════════════════════════════════════════\n\n";

echo json_encode($agent, JSON_PRETTY_PRINT);

echo "\n\n";
echo "═══════════════════════════════════════════════════════════\n";
echo "  DONE\n";
echo "═══════════════════════════════════════════════════════════\n\n";
