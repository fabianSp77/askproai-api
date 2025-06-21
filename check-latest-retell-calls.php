<?php

$apiKey = 'key_6ff998ba48e842092e04a5455d19';
$baseUrl = 'https://api.retellai.com';

echo "Checking latest Retell calls...\n\n";

// List recent calls
$ch = curl_init($baseUrl . '/list-calls');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $apiKey,
    'Accept: application/json'
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode == 200) {
    $calls = json_decode($response, true);
    
    if (is_array($calls) && !empty($calls)) {
        echo "Found " . count($calls) . " calls:\n\n";
        
        foreach (array_slice($calls, 0, 3) as $call) {
            echo "Call ID: " . $call['call_id'] . "\n";
            echo "Status: " . $call['call_status'] . "\n";
            echo "From: " . ($call['from_number'] ?? 'N/A') . "\n";
            echo "To: " . ($call['to_number'] ?? 'N/A') . "\n";
            
            if (isset($call['start_timestamp'])) {
                $startTime = date('Y-m-d H:i:s', $call['start_timestamp'] / 1000);
                echo "Start: " . $startTime . "\n";
                
                // Calculate how long ago
                $minutesAgo = round((time() - ($call['start_timestamp'] / 1000)) / 60);
                echo "Minutes ago: " . $minutesAgo . "\n";
            }
            
            echo "Agent: " . ($call['agent_id'] ?? 'N/A') . "\n";
            echo "---\n\n";
        }
    } else {
        echo "No calls found or unexpected response format.\n";
        echo "Response: " . substr($response, 0, 500) . "\n";
    }
} else {
    echo "Failed to get calls. HTTP Code: $httpCode\n";
    echo "Response: " . $response . "\n";
}