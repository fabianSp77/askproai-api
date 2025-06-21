<?php

// Update Retell agent webhook URL to debug endpoint
$apiKey = 'key_6ff998ba48e842092e04a5455d19';
$baseUrl = 'https://api.retellai.com';
$agentId = 'agent_9a8202a740cd3120d96fcfda1e';

echo "Updating Retell agent webhook URL to debug endpoint...\n\n";

// Update agent webhook URL
$updateData = [
    'webhook_url' => 'https://api.askproai.de/api/retell/debug-webhook'
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
    echo "✅ SUCCESS! Webhook URL updated to debug endpoint.\n";
    
    // Verify the update
    sleep(1);
    
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
        echo "\nVerified webhook URL: " . $verifiedAgent['webhook_url'] . "\n";
        echo "\n🎯 The webhook URL has been changed to the debug endpoint.\n";
        echo "This endpoint will accept webhooks WITHOUT signature verification.\n";
        echo "\nPlease make another test call to verify it works!\n";
    }
} else {
    echo "❌ FAILED! Error: " . substr($response, 0, 500) . "\n";
}

echo "\nDone!\n";