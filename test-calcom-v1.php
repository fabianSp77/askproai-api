<?php
require_once __DIR__ . '/vendor/autoload.php';

$apiKey = 'cal_live_e9aa2c4d18e0fd79cf4f8dddb90903da';

// Test 1: Event-Types mit verschiedenen Methoden
echo "=== TEST 1: Event-Types abrufen ===\n";

// Methode A: Query Parameter
$url1 = "https://api.cal.com/v1/event-types?apiKey={$apiKey}";
$ch1 = curl_init($url1);
curl_setopt($ch1, CURLOPT_RETURNTRANSFER, true);
$response1 = curl_exec($ch1);
$httpCode1 = curl_getinfo($ch1, CURLINFO_HTTP_CODE);
curl_close($ch1);

echo "Query Parameter Methode:\n";
echo "HTTP Code: {$httpCode1}\n";
echo "Response: " . substr($response1, 0, 200) . "...\n\n";

// Methode B: Authorization Header
$url2 = "https://api.cal.com/v1/event-types";
$ch2 = curl_init($url2);
curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch2, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $apiKey,
    'Content-Type: application/json'
]);
$response2 = curl_exec($ch2);
$httpCode2 = curl_getinfo($ch2, CURLINFO_HTTP_CODE);
curl_close($ch2);

echo "Authorization Header Methode:\n";
echo "HTTP Code: {$httpCode2}\n";
echo "Response: " . substr($response2, 0, 200) . "...\n\n";

// Test 2: Verfügbarkeit prüfen
echo "=== TEST 2: Verfügbarkeit prüfen ===\n";
$availUrl = "https://api.cal.com/v1/availability?apiKey={$apiKey}&eventTypeId=2026302&dateFrom=2025-06-07T08:00:00.000Z&dateTo=2025-06-07T18:00:00.000Z";
$ch3 = curl_init($availUrl);
curl_setopt($ch3, CURLOPT_RETURNTRANSFER, true);
$response3 = curl_exec($ch3);
$httpCode3 = curl_getinfo($ch3, CURLINFO_HTTP_CODE);
curl_close($ch3);

echo "HTTP Code: {$httpCode3}\n";
echo "Response: " . substr($response3, 0, 500) . "...\n";
