<?php
require_once 'vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

$apiKey = $_ENV['CALCOM_API_KEY'];
$eventTypeId = $_ENV['CALCOM_EVENT_TYPE_ID'];

// Teste Verfügbarkeit für die nächsten 7 Tage
$dateFrom = date('Y-m-d');
$dateTo = date('Y-m-d', strtotime('+7 days'));

echo "Prüfe Verfügbarkeit vom $dateFrom bis $dateTo\n";
echo "Event Type ID: $eventTypeId\n\n";

$url = "https://api.cal.com/v1/availability?apiKey=$apiKey&eventTypeId=$eventTypeId&dateFrom=$dateFrom&dateTo=$dateTo";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Status: $httpCode\n";
$data = json_decode($response, true);
echo "Response: " . json_encode($data, JSON_PRETTY_PRINT) . "\n";
