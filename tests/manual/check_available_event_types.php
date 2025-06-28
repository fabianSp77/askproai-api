<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$apiKey = env('CALCOM_API_KEY');
$userId = env('CALCOM_USER_ID', 1414768);

echo "=== Alle Event-Types abrufen ===\n\n";

// Hole alle Event-Types
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "https://api.cal.com/v1/event-types?apiKey=$apiKey");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode == 200) {
    $data = json_decode($response, true);
    
    if (isset($data['event_types'])) {
        echo "Gefundene Event-Types:\n\n";
        
        foreach ($data['event_types'] as $eventType) {
            echo "ID: " . $eventType['id'] . "\n";
            echo "Titel: " . $eventType['title'] . "\n";
            echo "User ID: " . ($eventType['userId'] ?? 'Team') . "\n";
            echo "Team ID: " . ($eventType['teamId'] ?? 'N/A') . "\n";
            echo "Versteckt: " . ($eventType['hidden'] ? 'Ja' : 'Nein') . "\n";
            echo "Slug: " . $eventType['slug'] . "\n";
            echo "Link: " . $eventType['link'] . "\n";
            
            // Prüfe Hosts
            if (isset($eventType['hosts']) && count($eventType['hosts']) > 0) {
                echo "Hosts: ";
                foreach ($eventType['hosts'] as $host) {
                    echo $host['name'] . " ";
                }
                echo "\n";
            } else {
                echo "Hosts: KEINE HOSTS ZUGEWIESEN!\n";
            }
            
            echo "----------------------------------------\n\n";
        }
    }
} else {
    echo "Fehler beim Abrufen der Event-Types: $httpCode\n";
    echo $response . "\n";
}

// Teste spezifisch den Event-Type 2026302
echo "\n=== Test Verfügbarkeit für Event-Type 2026302 ===\n";

$ch = curl_init();
$url = "https://api.cal.com/v1/availability?apiKey=$apiKey&eventTypeId=2026302&dateFrom=2025-06-10&dateTo=2025-06-10";
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$data = json_decode($response, true);
echo "HTTP Code: $httpCode\n";
echo "Response: " . json_encode($data, JSON_PRETTY_PRINT) . "\n";
