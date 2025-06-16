<?php
require_once 'vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

$apiKey = $_ENV['CALCOM_API_KEY'];
$eventTypeId = $_ENV['CALCOM_EVENT_TYPE_ID'];

echo "Verwende API Key: " . substr($apiKey, 0, 10) . "...\n";
echo "Verwende Event Type ID: $eventTypeId\n\n";

// Event Types abrufen
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "https://api.cal.com/v1/event-types?apiKey=$apiKey");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Status: $httpCode\n";
$data = json_decode($response, true);

if (isset($data['event_types'])) {
    foreach ($data['event_types'] as $eventType) {
        echo "\n--- Event Type ---\n";
        echo "ID: " . $eventType['id'] . "\n";
        echo "Title: " . $eventType['title'] . "\n";
        echo "Slug: " . $eventType['slug'] . "\n";
        echo "Length: " . $eventType['length'] . " min\n";
        echo "Hidden: " . ($eventType['hidden'] ? 'Ja' : 'Nein') . "\n";
        if (isset($eventType['hosts'])) {
            echo "Hosts: " . count($eventType['hosts']) . "\n";
        }
    }
} else {
    echo "Response: " . json_encode($data, JSON_PRETTY_PRINT) . "\n";
}
