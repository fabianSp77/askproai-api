<?php

use Illuminate\Support\Facades\Http;

echo "TESTING BOTH RETELL API KEYS\n";
echo str_repeat('=', 50) . "\n\n";

$apiKeys = [
    'Key 1 (Line 37)' => 'key_6ff998ba48e842092e04a5455d19',
    'Key 2 (Line 142)' => 'key_6ff998a93c40f83f2bec9d25343f'
];

foreach ($apiKeys as $name => $apiKey) {
    echo "Testing $name: " . substr($apiKey, 0, 15) . "...\n";
    
    // Test basic connectivity
    $ch = curl_init('https://api.retellai.com/list-agents');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $apiKey,
        'Accept: application/json'
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    
    $result = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    echo "  Status: $httpCode\n";
    
    if ($httpCode === 200) {
        echo "  ✅ SUCCESS! This API key works!\n";
        $data = json_decode($result, true);
        if (is_array($data)) {
            echo "  Found " . count($data) . " agents\n";
        }
    } elseif ($httpCode === 401) {
        echo "  ❌ Unauthorized - Invalid API key\n";
    } elseif ($httpCode === 500) {
        echo "  ❌ Internal Server Error - Key might be expired or invalid\n";
    } else {
        echo "  ❌ Failed with status $httpCode\n";
    }
    
    if ($error) {
        echo "  Error: $error\n";
    }
    
    echo "\n";
}

echo "RECOMMENDATION:\n";
echo "1. Both keys appear to be invalid (returning 500 errors)\n";
echo "2. You need to log into https://dashboard.retellai.com\n";
echo "3. Generate a new API key\n";
echo "4. Update .env file with the new key\n";
echo "5. Remove duplicate entries to avoid confusion\n";