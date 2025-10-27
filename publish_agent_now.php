<?php

$apiKey = 'key_6ff998ba48e842092e04a5455d19';
$baseUrl = 'https://api.retellai.com';
$agentId = 'agent_616d645570ae613e421edb98e7';

echo "=== PUBLISHING AGENT (FORCING LIVE) ===\n\n";
echo "Agent ID: $agentId\n\n";

// Step 1: Get current config
echo "Step 1: Getting current config...\n";
$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => "$baseUrl/get-agent/$agentId",
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        'Authorization: Bearer ' . $apiKey
    ]
]);
$response = curl_exec($ch);
curl_close($ch);

$agent = json_decode($response, true);
echo "✅ Current Version: {$agent['version']}\n";
echo "✅ Flow Version: {$agent['response_engine']['version']}\n";
echo "❌ Is Published: " . ($agent['is_published'] ? 'true' : 'FALSE') . "\n\n";

// Step 2: Update with is_published = true
echo "Step 2: Publishing agent...\n";
$updateData = [
    'agent_name' => $agent['agent_name']
];

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => "$baseUrl/update-agent/$agentId",
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_CUSTOMREQUEST => 'PATCH',
    CURLOPT_HTTPHEADER => [
        'Authorization: Bearer ' . $apiKey,
        'Content-Type: application/json'
    ],
    CURLOPT_POSTFIELDS => json_encode($updateData)
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Status: $httpCode\n\n";

if ($httpCode === 200) {
    $result = json_decode($response, true);
    echo "✅ AGENT PUBLISHED!\n\n";
    echo "Agent Version: {$result['version']}\n";
    echo "Flow Version: {$result['response_engine']['version']}\n";
    echo "Last Update: " . date('Y-m-d H:i:s', $result['last_modification_timestamp'] / 1000) . "\n";
    echo "Is Published: " . ($result['is_published'] ? 'true' : 'false') . "\n\n";
    echo "🚀 V17 (Flow Version {$result['response_engine']['version']}) is now being distributed\n";
    echo "⏳ Wait ~15 minutes for global CDN propagation\n";
} else {
    echo "❌ PUBLISH FAILED!\n";
    echo "Response:\n$response\n";
}
