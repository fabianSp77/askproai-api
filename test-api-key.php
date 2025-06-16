<?php
// Test 1: Direkter Test ohne Parameter
$apiKey = 'cal_live_bd7aedbdf12085c5312c79ba73585920';
$url = "https://api.cal.com/v1/event-types?apiKey=$apiKey";

echo "Testing API Key: $apiKey\n";
echo "URL: $url\n\n";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_VERBOSE, true);
curl_setopt($ch, CURLOPT_HEADER, true);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
$header = substr($response, 0, $headerSize);
$body = substr($response, $headerSize);

curl_close($ch);

echo "HTTP Code: $httpCode\n";
echo "Headers:\n$header\n";
echo "Body:\n$body\n";

// Test 2: Prüfe alle API-Keys in .env
echo "\n\n=== CHECKING ALL API KEYS IN .ENV ===\n";

$envFile = file_get_contents('/var/www/api-gateway/.env');
preg_match_all('/CALCOM_API_KEY=(cal_live_[a-zA-Z0-9]+)/', $envFile, $matches);

foreach ($matches[1] as $key) {
    echo "\nTesting key: $key\n";
    $testUrl = "https://api.cal.com/v1/event-types?apiKey=$key";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $testUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    echo "Result: HTTP $httpCode\n";
    
    if ($httpCode === 200) {
        echo "✅ GÜLTIGER API-KEY GEFUNDEN!\n";
        $data = json_decode($response, true);
        if (isset($data['event_types'])) {
            echo "Event-Types: " . count($data['event_types']) . "\n";
        }
    }
}
?>
