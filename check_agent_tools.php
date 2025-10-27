<?php

require __DIR__ . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

$apiKey = $_ENV['RETELLAI_API_KEY'] ?? $_ENV['RETELL_TOKEN'];
$agentId = 'agent_f1ce85d06a84afb989dfbb16a9';

echo "═══════════════════════════════════════════════════════\n";
echo "🔍 CHECKING AGENT TOOLS CONFIGURATION\n";
echo "═══════════════════════════════════════════════════════\n\n";

// Get agent details
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "https://api.retellai.com/v2/get-agent/$agentId");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer $apiKey",
    "Content-Type: application/json"
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode !== 200) {
    die("❌ Failed to fetch agent! HTTP $httpCode\nResponse: $response\n");
}

$agent = json_decode($response, true);

echo "Agent Name: " . ($agent['agent_name'] ?? 'N/A') . "\n";
echo "Version: " . ($agent['version'] ?? 'N/A') . "\n\n";

// Check tools
if (isset($agent['tool_ids']) && !empty($agent['tool_ids'])) {
    echo "✅ Tool IDs configured: " . count($agent['tool_ids']) . "\n";
    foreach ($agent['tool_ids'] as $toolId) {
        echo "   - $toolId\n";
    }
} else {
    echo "❌ NO TOOLS CONFIGURED!\n";
}

echo "\n";

// Check function_call_config
if (isset($agent['function_call_config'])) {
    echo "Function Call Config:\n";
    echo json_encode($agent['function_call_config'], JSON_PRETTY_PRINT) . "\n";
} else {
    echo "❌ NO FUNCTION CALL CONFIG!\n";
}

echo "\n";

// Save full agent data
file_put_contents(__DIR__ . '/agent_config.json', json_encode($agent, JSON_PRETTY_PRINT));
echo "✅ Full agent config saved to: agent_config.json\n";
