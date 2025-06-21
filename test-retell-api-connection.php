<?php

// Test Retell API connection and list recent calls
$apiKey = 'key_6ff998ba48e842092e04a5455d19';
$baseUrl = 'https://api.retellai.com';

echo "Testing Retell API Connection...\n";
echo "API Key: " . substr($apiKey, 0, 10) . "...\n\n";

// Test 1: Get recent calls
echo "1. Fetching recent calls:\n";
$ch = curl_init($baseUrl . '/list-calls?limit=5');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $apiKey,
    'Accept: application/json'
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "   Response Code: $httpCode\n";

if ($httpCode == 200) {
    $data = json_decode($response, true);
    echo "   ✅ SUCCESS! Found " . count($data) . " calls\n";
    
    if (!empty($data)) {
        echo "\n   Recent calls:\n";
        foreach ($data as $call) {
            echo "   - Call ID: " . $call['call_id'] . "\n";
            echo "     Status: " . $call['call_status'] . "\n";
            echo "     Type: " . $call['call_type'] . "\n";
            echo "     From: " . ($call['from_number'] ?? 'N/A') . "\n";
            echo "     To: " . ($call['to_number'] ?? 'N/A') . "\n";
            echo "     Date: " . date('Y-m-d H:i:s', $call['start_timestamp'] / 1000) . "\n\n";
        }
    }
} else {
    echo "   ❌ FAILED! Error: " . substr($response, 0, 200) . "\n";
}

// Test 2: Get agent details
echo "\n2. Fetching agent details:\n";
$agentId = 'agent_9a8202a740cd3120d96fcfda1e';
$ch = curl_init($baseUrl . '/get-agent/' . $agentId);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $apiKey,
    'Accept: application/json'
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "   Response Code: $httpCode\n";

if ($httpCode == 200) {
    $agent = json_decode($response, true);
    echo "   ✅ Agent found!\n";
    echo "   - Name: " . ($agent['agent_name'] ?? 'N/A') . "\n";
    echo "   - Voice: " . ($agent['voice_id'] ?? 'N/A') . "\n";
    echo "   - Language: " . ($agent['language'] ?? 'N/A') . "\n";
    
    // Check webhook configuration
    if (isset($agent['webhook_url'])) {
        echo "\n   Webhook Configuration:\n";
        echo "   - URL: " . $agent['webhook_url'] . "\n";
        echo "   - Events: " . implode(', ', $agent['webhook_events'] ?? []) . "\n";
    } else {
        echo "\n   ⚠️  WARNING: No webhook URL configured for this agent!\n";
    }
} else {
    echo "   ❌ FAILED! Error: " . substr($response, 0, 200) . "\n";
}

// Test 3: Check webhook configuration
echo "\n3. Checking webhook endpoint availability:\n";
$webhookUrl = 'https://api.askproai.de/api/retell/webhook';
$ch = curl_init($webhookUrl);
curl_setopt($ch, CURLOPT_NOBODY, true);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);

curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "   Webhook URL: $webhookUrl\n";
echo "   Response Code: $httpCode\n";

if ($httpCode == 405 || $httpCode == 200) {
    echo "   ✅ Webhook endpoint is reachable\n";
} else {
    echo "   ❌ Webhook endpoint returned unexpected code\n";
}

echo "\nDone!\n";