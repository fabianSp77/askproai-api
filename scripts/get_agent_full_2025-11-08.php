#!/usr/bin/env php
<?php

require __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

$apiKey = $_ENV['RETELLAI_API_KEY'] ?? $_ENV['RETELL_TOKEN'];
$agentId = 'agent_45daa54928c5768b52ba3db736';
$baseUrl = 'https://api.retellai.com';

echo "\n";
echo "Fetching agent config...\n";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "$baseUrl/get-agent/$agentId");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer $apiKey"
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode === 200) {
    $agent = json_decode($response, true);
    echo "\nAgent Structure:\n";
    print_r(array_keys($agent));
    echo "\n\n";
    
    echo "Agent Version: " . ($agent['version'] ?? 'N/A') . "\n";
    echo "Agent Name: " . ($agent['agent_name'] ?? 'N/A') . "\n";
    
    if (isset($agent['conversation_flow_id'])) {
        echo "Conversation Flow ID: " . $agent['conversation_flow_id'] . "\n";
    }
    
    // Save full config for inspection
    file_put_contents('/var/www/api-gateway/agent_full_config_2025-11-08.json', json_encode($agent, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    echo "\nFull config saved to: agent_full_config_2025-11-08.json\n";
} else {
    echo "Failed: $httpCode\n";
    echo "Response: $response\n";
}
