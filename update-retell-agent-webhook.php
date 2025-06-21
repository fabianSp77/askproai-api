<?php

// Update Retell agent to enable webhook events
$apiKey = 'key_6ff998ba48e842092e04a5455d19';
$baseUrl = 'https://api.retellai.com';
$agentId = 'agent_9a8202a740cd3120d96fcfda1e';

echo "Updating Retell Agent Webhook Configuration...\n\n";

// Update agent to enable all webhook events
$updateData = [
    'webhook_url' => 'https://api.askproai.de/api/retell/webhook',
    'webhook_events' => [
        'call_started',
        'call_ended', 
        'call_analyzed'
    ]
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
curl_close($ch);

echo "Response Code: $httpCode\n";

if ($httpCode == 200 || $httpCode == 201) {
    echo "✅ SUCCESS! Agent webhook configuration updated.\n\n";
    
    $agent = json_decode($response, true);
    echo "Updated Configuration:\n";
    echo "- Webhook URL: " . $agent['webhook_url'] . "\n";
    echo "- Enabled Events: " . implode(', ', $agent['webhook_events'] ?? []) . "\n";
    
    // Verify the update
    echo "\nVerifying update...\n";
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
        echo "✅ Verification successful!\n";
        echo "- Events now enabled: " . implode(', ', $verifiedAgent['webhook_events'] ?? []) . "\n";
    }
    
} else {
    echo "❌ FAILED! Error: " . $response . "\n";
}

echo "\nDone!\n";