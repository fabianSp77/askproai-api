<?php
require_once 'vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

$apiKey = $_ENV['CALCOM_API_KEY'];
$eventTypeId = $_ENV['CALCOM_EVENT_TYPE_ID'];
$userId = $_ENV['CALCOM_USER_ID'] ?? null;
$teamSlug = $_ENV['CALCOM_TEAM_SLUG'] ?? 'askproai';

// Teste Verfügbarkeit für die nächsten 7 Tage
$dateFrom = date('Y-m-d');
$dateTo = date('Y-m-d', strtotime('+7 days'));

echo "=== Teste verschiedene Verfügbarkeitsabfragen ===\n\n";

// Test 1: Mit teamSlug
echo "Test 1: Mit teamSlug '$teamSlug'\n";
$url1 = "https://api.cal.com/v1/availability?apiKey=$apiKey&eventTypeId=$eventTypeId&dateFrom=$dateFrom&dateTo=$dateTo&teamSlug=$teamSlug";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url1);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
$response1 = curl_exec($ch);
$httpCode1 = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Status: $httpCode1\n";
echo "Response: " . json_encode(json_decode($response1, true), JSON_PRETTY_PRINT) . "\n\n";

// Test 2: Mit username (aus Event-Type Slug)
echo "Test 2: Mit username 'fabianspitzer'\n";
$url2 = "https://api.cal.com/v1/availability?apiKey=$apiKey&eventTypeId=$eventTypeId&dateFrom=$dateFrom&dateTo=$dateTo&username=fabianspitzer";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url2);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
$response2 = curl_exec($ch);
$httpCode2 = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Status: $httpCode2\n";
echo "Response: " . json_encode(json_decode($response2, true), JSON_PRETTY_PRINT) . "\n\n";

// Test 3: Mit userId (falls vorhanden)
if ($userId) {
    echo "Test 3: Mit userId '$userId'\n";
    $url3 = "https://api.cal.com/v1/availability?apiKey=$apiKey&eventTypeId=$eventTypeId&dateFrom=$dateFrom&dateTo=$dateTo&userId=$userId";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url3);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    $response3 = curl_exec($ch);
    $httpCode3 = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    echo "HTTP Status: $httpCode3\n";
    echo "Response: " . json_encode(json_decode($response3, true), JSON_PRETTY_PRINT) . "\n";
}
