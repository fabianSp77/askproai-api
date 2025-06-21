<?php

// Enable webhook events for Retell agent
$apiKey = 'key_6ff998ba48e842092e04a5455d19';
$baseUrl = 'https://api.retellai.com';
$agentId = 'agent_9a8202a740cd3120d96fcfda1e';

echo "Enabling webhook events for Retell agent...\n\n";

// First, get the current agent configuration
echo "1. Fetching current agent configuration...\n";
$ch = curl_init($baseUrl . '/get-agent/' . $agentId);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $apiKey,
    'Accept: application/json'
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode != 200) {
    die("Failed to get agent. HTTP Code: $httpCode\n");
}

$agent = json_decode($response, true);
echo "   ✅ Agent found: " . $agent['agent_name'] . "\n";
echo "   Current webhook URL: " . $agent['webhook_url'] . "\n";
echo "   Current webhook events: " . json_encode($agent['webhook_events'] ?? []) . "\n\n";

// Update the agent with webhook events enabled
echo "2. Updating agent to enable webhook events...\n";

// Prepare update payload - only include fields we want to change
$updateData = [
    'webhook_url' => 'https://api.askproai.de/api/retell/webhook',
    'webhook_events' => ['call_started', 'call_ended', 'call_analyzed']
];

$ch = curl_init($baseUrl . '/update-agent/' . $agentId);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PATCH');
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($updateData));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $apiKey,
    'Content-Type: application/json',
    'Accept: application/json'
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$errorInfo = curl_error($ch);
curl_close($ch);

echo "   Response Code: $httpCode\n";

if ($httpCode == 200 || $httpCode == 201) {
    $updatedAgent = json_decode($response, true);
    echo "   ✅ SUCCESS! Agent updated.\n";
    
    // The Retell API might not return webhook_events in the response
    // Let's verify by fetching the agent again
    echo "\n3. Verifying the update...\n";
    
    sleep(1); // Give the API a moment to update
    
    $ch = curl_init($baseUrl . '/get-agent/' . $agentId);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $apiKey,
        'Accept: application/json'
    ]);
    
    $verifyResponse = curl_exec($ch);
    $verifyCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($verifyCode == 200) {
        $verifiedAgent = json_decode($verifyResponse, true);
        echo "   Webhook URL: " . $verifiedAgent['webhook_url'] . "\n";
        
        // Check if webhook_events field exists in response
        if (isset($verifiedAgent['webhook_events'])) {
            echo "   Webhook events: " . json_encode($verifiedAgent['webhook_events']) . "\n";
        } else {
            echo "   ⚠️  Note: webhook_events field not returned by API (this might be normal)\n";
        }
    }
    
    echo "\n✅ Webhook configuration updated!\n";
    echo "\nIMPORTANT: Even if webhook_events don't show in the response,\n";
    echo "the webhooks should now be active. Make a test call to verify.\n";
    
} else {
    echo "   ❌ FAILED! Error updating agent.\n";
    echo "   Response: " . substr($response, 0, 500) . "\n";
    if ($errorInfo) {
        echo "   CURL Error: " . $errorInfo . "\n";
    }
    
    // Try alternative approach - update with full agent data
    echo "\n4. Trying alternative update method...\n";
    
    // Copy all existing agent data and add webhook events
    $fullUpdate = $agent;
    $fullUpdate['webhook_url'] = 'https://api.askproai.de/api/retell/webhook';
    $fullUpdate['webhook_events'] = ['call_started', 'call_ended', 'call_analyzed'];
    
    // Remove fields that shouldn't be in update
    unset($fullUpdate['agent_id']);
    unset($fullUpdate['created_at']);
    unset($fullUpdate['updated_at']);
    unset($fullUpdate['version']);
    
    $ch = curl_init($baseUrl . '/update-agent/' . $agentId);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PATCH');
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fullUpdate));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $apiKey,
        'Content-Type: application/json',
        'Accept: application/json'
    ]);
    
    $altResponse = curl_exec($ch);
    $altCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($altCode == 200 || $altCode == 201) {
        echo "   ✅ Alternative method successful!\n";
    } else {
        echo "   ❌ Alternative method also failed. Code: $altCode\n";
        echo "   Response: " . substr($altResponse, 0, 500) . "\n";
    }
}

echo "\nDone!\n";