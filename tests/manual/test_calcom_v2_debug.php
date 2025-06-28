<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$apiKey = env('DEFAULT_CALCOM_API_KEY');
echo "=== Cal.com V2 API Debug ===\n\n";
echo "API Key (first 10 chars): " . substr($apiKey, 0, 10) . "...\n\n";

// Direkt testen ohne Service
echo "1. Test V2 Event Types (direkt):\n";
$response = Http::withHeaders([
    'Authorization' => 'Bearer ' . $apiKey,
    'cal-api-version' => '2024-08-13',
    'Content-Type' => 'application/json',
])->get('https://api.cal.com/v2/event-types');

echo "Status: " . $response->status() . "\n";
if (!$response->successful()) {
    echo "Error: " . $response->body() . "\n\n";
}

// Test V1 Event Types
echo "2. Test V1 Event Types (mit API key in URL):\n";
$response = Http::get('https://api.cal.com/v1/event-types?apiKey=' . $apiKey);
echo "Status: " . $response->status() . "\n";
if ($response->successful()) {
    $data = $response->json();
    echo "Event Types gefunden: " . count($data['event_types'] ?? []) . "\n";
    
    if (isset($data['event_types'][0])) {
        $eventTypeId = $data['event_types'][0]['id'];
        echo "Erste Event Type ID: " . $eventTypeId . "\n\n";
        
        // Test slots mit dieser ID
        echo "3. Test V2 Slots mit Event Type ID {$eventTypeId}:\n";
        $tomorrow = date('Y-m-d', strtotime('+1 day'));
        
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $apiKey,
            'cal-api-version' => '2024-08-13',
            'Content-Type' => 'application/json',
        ])->get('https://api.cal.com/v2/slots/available', [
            'eventTypeId' => $eventTypeId,
            'startTime' => $tomorrow . 'T00:00:00.000Z',
            'endTime' => $tomorrow . 'T23:59:59.999Z',
            'timeZone' => 'Europe/Berlin'
        ]);
        
        echo "Status: " . $response->status() . "\n";
        if ($response->successful()) {
            $data = $response->json();
            echo "Response structure:\n";
            print_r($data);
        } else {
            echo "Error: " . $response->body() . "\n";
        }
    }
}