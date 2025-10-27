<?php

$apiKey = 'key_6ff998ba48e842092e04a5455d19';
$baseUrl = 'https://api.retellai.com';
$agentId = 'agent_616d645570ae613e421edb98e7';

echo "=== RE-PUBLISHING AGENT (V17 CDN PROPAGATION) ===\n\n";
echo "Agent ID: $agentId\n\n";

// Step 1: Get current agent configuration
echo "Step 1: Fetching current agent config...\n";
$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => "$baseUrl/get-agent/$agentId",
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        'Authorization: Bearer ' . $apiKey
    ]
]);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode !== 200) {
    echo "❌ Failed to fetch agent config\n";
    echo "Response: $response\n";
    exit(1);
}

$agent = json_decode($response, true);
echo "✅ Current version: {$agent['agent_version']}\n\n";

// Step 2: Update agent (triggers republish)
echo "Step 2: Triggering republish...\n";
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
    echo "✅ AGENT RE-PUBLISHED!\n\n";
    echo "Agent ID: {$result['agent_id']}\n";
    echo "New Version: {$result['agent_version']}\n";
    echo "Last Update: " . date('Y-m-d H:i:s', $result['last_modification_timestamp'] / 1000) . "\n\n";
    echo "🚀 V17 Flow Version 18 is now being distributed via CDN\n";
    echo "⏳ Wait ~15 minutes for global CDN propagation\n";
    echo "📞 Then make a test call to verify\n";
} else {
    echo "❌ RE-PUBLISH FAILED!\n";
    echo "Response:\n";
    echo $response . "\n\n";

    $error = json_decode($response, true);
    if ($error) {
        echo "Parsed Error:\n";
        print_r($error);
    }
}
