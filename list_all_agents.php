<?php

require __DIR__ . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

$apiKey = $_ENV['RETELLAI_API_KEY'] ?? $_ENV['RETELL_TOKEN'] ?? null;

if (!$apiKey) {
    die("❌ No API key found!\n");
}

echo "🔍 Listing all agents...\n\n";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "https://api.retellai.com/list-agents");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer $apiKey",
    "Content-Type: application/json"
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode !== 200) {
    echo "❌ API Error!\n";
    echo "Response: $response\n";
    exit(1);
}

echo "Raw Response:\n";
echo substr($response, 0, 500) . "...\n\n";

$data = json_decode($response, true);

if (!$data) {
    echo "❌ Failed to parse JSON!\n";
    echo "JSON Error: " . json_last_error_msg() . "\n";
    exit(1);
}

if (!isset($data['agents'])) {
    echo "❌ No 'agents' key in response!\n";
    echo "Keys found: " . implode(', ', array_keys($data)) . "\n";
    exit(1);
}

$agents = $data['agents'];

echo "Total Agents: " . count($agents) . "\n\n";

foreach ($agents as $index => $agent) {
    echo "─────────────────────────────────────────\n";
    echo "Agent #" . ($index + 1) . "\n";
    echo "─────────────────────────────────────────\n";
    echo "ID: " . ($agent['agent_id'] ?? 'N/A') . "\n";
    echo "Name: " . ($agent['agent_name'] ?? 'N/A') . "\n";
    echo "Type: " . ($agent['response_engine']['type'] ?? 'N/A') . "\n";

    if ($agent['response_engine']['type'] === 'conversation_flow') {
        echo "🎯 CONVERSATION FLOW ID: " . ($agent['response_engine']['conversation_flow_id'] ?? 'N/A') . "\n";
        echo "✅ This is a Conversation Flow agent!\n";
    } elseif ($agent['response_engine']['type'] === 'retell-llm') {
        echo "LLM ID: " . ($agent['response_engine']['llm_id'] ?? 'N/A') . "\n";
        echo "Version: " . ($agent['response_engine']['version'] ?? 'N/A') . "\n";
    }

    echo "\n";
}

echo "─────────────────────────────────────────\n";
echo "\n🔍 Looking for 'Friseur' in agent names...\n\n";

foreach ($agents as $agent) {
    if (stripos($agent['agent_name'] ?? '', 'friseur') !== false ||
        stripos($agent['agent_name'] ?? '', 'conversation') !== false) {
        echo "✨ FOUND:\n";
        echo "  ID: " . ($agent['agent_id'] ?? 'N/A') . "\n";
        echo "  Name: " . ($agent['agent_name'] ?? 'N/A') . "\n";
        echo "  Type: " . ($agent['response_engine']['type'] ?? 'N/A') . "\n";

        if ($agent['response_engine']['type'] === 'conversation_flow') {
            $flowId = $agent['response_engine']['conversation_flow_id'] ?? 'N/A';
            echo "  🎯 Flow ID: $flowId\n";
        }

        echo "\n";
    }
}
