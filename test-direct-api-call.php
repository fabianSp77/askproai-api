<?php

echo "\n=== DIRECT API CALL TEST ===\n";
echo "Testing the actual /business/api/dashboard endpoint\n\n";

// Test with different ranges
$ranges = ['today', 'week', 'month'];

foreach ($ranges as $range) {
    echo "Testing range: $range\n";
    echo "------------------------\n";
    
    $url = "https://api.askproai.de/business/api/dashboard?range=$range";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Accept: application/json',
        'X-Requested-With: XMLHttpRequest'
    ]);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    echo "HTTP Code: $httpCode\n";
    
    if ($error) {
        echo "CURL Error: $error\n";
    } elseif ($response) {
        $data = json_decode($response, true);
        if ($data && isset($data['performance'])) {
            echo "Performance data:\n";
            echo "  - Answer rate: " . ($data['performance']['answer_rate'] ?? 'N/A') . "%\n";
            echo "  - Booking rate: " . ($data['performance']['booking_rate'] ?? 'N/A') . "%\n";
            echo "  - Avg call duration: " . ($data['performance']['avg_call_duration'] ?? 'N/A') . " seconds\n";
            echo "  - Formatted: " . gmdate("i:s", $data['performance']['avg_call_duration'] ?? 0) . "\n";
            
            echo "Stats:\n";
            echo "  - Calls today: " . ($data['stats']['calls_today'] ?? 'N/A') . "\n";
        } elseif ($data && isset($data['error'])) {
            echo "API Error: " . $data['error'] . "\n";
        } else {
            echo "Response (first 200 chars): " . substr($response, 0, 200) . "\n";
        }
    } else {
        echo "No response received\n";
    }
    
    echo "\n";
}

echo "=== END TEST ===\n";