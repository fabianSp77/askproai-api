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
curl_setopt($ch, CURLOPT_URL, "https://api.retellai.com/agent/$agentId");
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
echo "Last Modification: " . ($agent['last_modification_timestamp'] ?? 'N/A') . "\n\n";

// Check tool_ids
if (isset($agent['tool_ids']) && !empty($agent['tool_ids'])) {
    echo "✅ Tool IDs configured: " . count($agent['tool_ids']) . "\n";
    foreach ($agent['tool_ids'] as $toolId) {
        echo "   - $toolId\n";
    }
} else {
    echo "❌ NO TOOL_IDS CONFIGURED!\n";
}

echo "\n";

// Check custom_tools
if (isset($agent['custom_tools']) && !empty($agent['custom_tools'])) {
    echo "✅ Custom Tools configured: " . count($agent['custom_tools']) . "\n";
    foreach ($agent['custom_tools'] as $tool) {
        echo "   - " . ($tool['name'] ?? 'unnamed') . " → " . ($tool['url'] ?? 'no URL') . "\n";
    }
} else {
    echo "❌ NO CUSTOM TOOLS CONFIGURED!\n";
}

echo "\n";

// Save full agent data
file_put_contents(__DIR__ . '/agent_config_draft.json', json_encode($agent, JSON_PRETTY_PRINT));
echo "✅ Full agent config saved to: agent_config_draft.json\n";
