<?php
$apiKey = 'cal_live_e9aa2c4d18e0fd79cf4f8dddb90903da';

echo "Testing Cal.com API v2...\n\n";

// Test verschiedene Endpunkte
$endpoints = [
    'https://api.cal.com/v2/event-types?apiKey=' . $apiKey,
    'https://api.cal.com/v1/availability?apiKey=' . $apiKey,
    'https://cal.com/api/v1/me?apiKey=' . $apiKey,
];

foreach ($endpoints as $url) {
    echo "Testing: $url\n";
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
    ]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    echo "HTTP Code: $httpCode\n";
    echo "Response: " . substr($response, 0, 200) . "...\n\n";
}
