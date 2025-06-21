<?php

// Get call details directly from Retell API
$apiKey = 'key_6ff998ba48e842092e04a5455d19';
$baseUrl = 'https://api.retellai.com';

// Test with the last known call ID
$callId = 'call_ab6d2dfdd5ebb507c6c5a2f127c';

echo "Fetching call details from Retell API...\n";
echo "Call ID: $callId\n\n";

$ch = curl_init($baseUrl . '/v2/get-call/' . $callId);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $apiKey,
    'Accept: application/json'
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Status: $httpCode\n";

if ($httpCode == 200) {
    $callData = json_decode($response, true);
    echo "\nCall Details:\n";
    echo "Status: " . ($callData['status'] ?? 'N/A') . "\n";
    echo "Duration: " . ($callData['duration_ms'] ?? 0) / 1000 . " seconds\n";
    echo "Start Time: " . ($callData['start_timestamp'] ?? 'N/A') . "\n";
    echo "End Time: " . ($callData['end_timestamp'] ?? 'N/A') . "\n";
    echo "Transcript Available: " . (isset($callData['transcript']) ? 'YES' : 'NO') . "\n";
    echo "Summary Available: " . (isset($callData['call_analysis']['call_summary']) ? 'YES' : 'NO') . "\n";
    
    if (isset($callData['webhook_url'])) {
        echo "\nWebhook Configuration:\n";
        echo "URL: " . $callData['webhook_url'] . "\n";
        echo "Events: " . json_encode($callData['webhook_events'] ?? []) . "\n";
    }
    
    echo "\nFull Response:\n";
    print_r($callData);
} else {
    echo "Failed to get call details. Response:\n";
    echo substr($response, 0, 500) . "\n";
    
    // Try v1 API
    echo "\nTrying v1 API...\n";
    $ch = curl_init($baseUrl . '/get-call/' . $callId);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $apiKey,
        'Accept: application/json'
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    echo "V1 API Status: $httpCode\n";
    if ($httpCode == 200) {
        $callData = json_decode($response, true);
        echo "Call found in v1 API!\n";
        print_r($callData);
    }
}

echo "\nDone!\n";