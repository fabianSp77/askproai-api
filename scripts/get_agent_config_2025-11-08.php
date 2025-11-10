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
    
    echo "\nAgent Version: " . ($agent['version'] ?? 'N/A') . "\n";
    echo "Conversation Flow ID: " . ($agent['conversationFlow']['conversation_flow_id'] ?? 'N/A') . "\n";
    echo "Conversation Flow Version: " . ($agent['conversationFlow']['version'] ?? 'N/A') . "\n\n";
    
    echo "Checking tools for parameter_mapping...\n\n";
    
    foreach ($agent['conversationFlow']['tools'] as $tool) {
        if (in_array($tool['name'], ['start_booking', 'confirm_booking'])) {
            echo "Tool: " . $tool['name'] . "\n";
            echo "  Has parameter_mapping: " . (isset($tool['parameter_mapping']) ? 'YES' : 'NO') . "\n";
            if (isset($tool['parameter_mapping'])) {
                echo "  parameter_mapping:\n";
                foreach ($tool['parameter_mapping'] as $key => $value) {
                    echo "    $key => $value\n";
                }
            }
            echo "\n";
        }
    }
} else {
    echo "Failed: $httpCode\n";
    echo "Response: $response\n";
}
