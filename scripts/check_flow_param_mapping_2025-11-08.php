#!/usr/bin/env php
<?php

require __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

$apiKey = $_ENV['RETELLAI_API_KEY'] ?? $_ENV['RETELL_TOKEN'];
$conversationFlowId = 'conversation_flow_a58405e3f67a';
$baseUrl = 'https://api.retellai.com';

echo "\n";
echo "Fetching conversation flow...\n";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "$baseUrl/get-conversation-flow/$conversationFlowId");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer $apiKey"
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode === 200) {
    $flow = json_decode($response, true);
    
    echo "\nFlow Version: " . ($flow['version'] ?? 'N/A') . "\n\n";
    
    echo "Checking tools for parameter_mapping...\n";
    echo "═══════════════════════════════════════════════════════════\n\n";
    
    foreach ($flow['tools'] as $tool) {
        if (in_array($tool['name'], ['start_booking', 'confirm_booking', 'check_availability_v17'])) {
            echo "Tool: " . $tool['name'] . "\n";
            echo "  Type: " . ($tool['type'] ?? 'N/A') . "\n";
            echo "  Has parameter_mapping: " . (isset($tool['parameter_mapping']) ? '✅ YES' : '❌ NO') . "\n";
            
            if (isset($tool['parameter_mapping'])) {
                echo "  parameter_mapping:\n";
                foreach ($tool['parameter_mapping'] as $key => $value) {
                    echo "    $key => $value\n";
                }
            } else {
                echo "  ⚠️  NO parameter_mapping found!\n";
            }
            echo "\n";
        }
    }
    
    echo "═══════════════════════════════════════════════════════════\n";
} else {
    echo "Failed: $httpCode\n";
    echo "Response: $response\n";
}
