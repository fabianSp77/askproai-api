<?php

require __DIR__ . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

$apiKey = $_ENV['RETELLAI_API_KEY'] ?? $_ENV['RETELL_TOKEN'] ?? null;
$agentId = 'agent_9a8202a740cd3120d96fcfda1e';

if (!$apiKey) {
    die("❌ No API key found!\n");
}

echo "🔍 Fetching agent details...\n";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "https://api.retellai.com/get-agent/$agentId");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer $apiKey",
    "Content-Type: application/json"
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Code: $httpCode\n";

if ($httpCode !== 200) {
    echo "❌ API Error!\n";
    echo "Response: $response\n";
    exit(1);
}

$agent = json_decode($response, true);

if (!$agent) {
    echo "❌ Failed to parse JSON!\n";
    echo "Response: $response\n";
    exit(1);
}

echo "\n✅ Agent Details:\n";
echo "Agent ID: " . ($agent['agent_id'] ?? 'N/A') . "\n";
echo "Agent Name: " . ($agent['agent_name'] ?? 'N/A') . "\n";

// Try different possible locations for conversation_flow_id
$flowId = $agent['conversation_flow_id']
    ?? $agent['response_engine']['conversation_flow_id']
    ?? $agent['llm_websocket_url']['conversation_flow_id']
    ?? null;

if ($flowId) {
    echo "\n🎯 Conversation Flow ID: $flowId\n";
} else {
    echo "\n❌ No conversation_flow_id found!\n";
    echo "\n📋 Full agent structure:\n";
    echo json_encode($agent, JSON_PRETTY_PRINT);
}
