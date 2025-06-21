<?php

// Check current Retell agent configuration
$apiKey = 'key_6ff998ba48e842092e04a5455d19';
$baseUrl = 'https://api.retellai.com';
$agentId = 'agent_9a8202a740cd3120d96fcfda1e';

echo "Checking Retell agent configuration...\n\n";

$ch = curl_init($baseUrl . '/get-agent/' . $agentId);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $apiKey,
    'Accept: application/json'
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode == 200) {
    $agent = json_decode($response, true);
    
    echo "Agent Configuration:\n";
    echo "ID: " . $agent['agent_id'] . "\n";
    echo "Name: " . ($agent['agent_name'] ?? 'N/A') . "\n";
    echo "Webhook URL: " . ($agent['webhook_url'] ?? 'NOT SET') . "\n";
    echo "Webhook Events: " . json_encode($agent['webhook_events'] ?? []) . "\n";
    echo "\n";
    
    if (empty($agent['webhook_events'])) {
        echo "⚠️  WARNING: No webhook events configured!\n";
        echo "The agent won't send any webhooks.\n";
    } else {
        echo "✅ Webhook events are configured: " . implode(', ', $agent['webhook_events']) . "\n";
    }
    
    if (isset($agent['webhook_url']) && $agent['webhook_url'] == 'https://api.askproai.de/api/retell/debug-webhook') {
        echo "✅ Webhook URL is correctly set to debug endpoint.\n";
    } else {
        echo "❌ Webhook URL is not set to debug endpoint!\n";
        echo "Current URL: " . ($agent['webhook_url'] ?? 'NONE') . "\n";
    }
    
} else {
    echo "Failed to get agent configuration. HTTP Code: $httpCode\n";
    echo "Response: " . substr($response, 0, 500) . "\n";
}

echo "\nDone!\n";