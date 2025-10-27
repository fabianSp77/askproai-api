<?php

require __DIR__ . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

$apiKey = $_ENV['RETELLAI_API_KEY'] ?? $_ENV['RETELL_TOKEN'];
$agentId = 'agent_f1ce85d06a84afb989dfbb16a9';

echo "═══════════════════════════════════════════════════════\n";
echo "🔍 GETTING AGENT CONFIGURATION\n";
echo "═══════════════════════════════════════════════════════\n\n";

// List all agents to find the correct endpoint
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

if ($httpCode === 200) {
    $agents = json_decode($response, true);
    echo "✅ Found " . count($agents) . " agents\n\n";
    
    foreach ($agents as $agent) {
        if ($agent['agent_id'] === $agentId) {
            echo "📋 AGENT FOUND:\n";
            echo "─────────────────────────────────────────\n";
            echo "Agent ID: " . $agent['agent_id'] . "\n";
            echo "Agent Name: " . ($agent['agent_name'] ?? 'N/A') . "\n";
            echo "Last Modified: " . ($agent['last_modification_timestamp'] ?? 'N/A') . "\n\n";
            
            echo "🔔 WEBHOOK CONFIGURATION:\n";
            echo "─────────────────────────────────────────\n";
            
            if (isset($agent['webhook_url'])) {
                echo "Webhook URL: " . $agent['webhook_url'] . "\n";
            } else {
                echo "❌ NO WEBHOOK URL CONFIGURED!\n";
            }
            
            if (isset($agent['events_to_record'])) {
                echo "Events to Record:\n";
                foreach ($agent['events_to_record'] as $event) {
                    echo "  ✓ $event\n";
                }
            } else {
                echo "❌ NO EVENTS CONFIGURED!\n";
            }
            
            echo "\n";
            
            // Save full config
            file_put_contents(__DIR__ . '/agent_full_config.json', json_encode($agent, JSON_PRETTY_PRINT));
            echo "✅ Full config saved to: agent_full_config.json\n";
            exit(0);
        }
    }
    
    echo "❌ Agent $agentId not found in list!\n";
} else {
    echo "❌ Failed to list agents! HTTP $httpCode\n";
    echo "Response: $response\n";
}
