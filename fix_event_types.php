<?php

/**
 * Fix Cal.com Event Types - Hosts, schedulingType, Location
 *
 * Probleme:
 * 1. Neue Event Types haben keine Hosts → niemand kann buchen!
 * 2. schedulingType ist "collective" statt "roundRobin"
 * 3. Location ist "cal-video" statt "attendeeDefined"
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Http;

$baseUrl = rtrim(config('services.calcom.base_url'), '/');
$apiKey = config('services.calcom.api_key');
$teamId = 34209;

// Hosts wie bei alten Services
$hosts = [
    ['userId' => 1414768],  // Fabian Spitzer (askproai)
    ['userId' => 1346408],  // Fabian Spitzer (fabianspitzer)
];

// Event Type IDs der neuen Services
$eventTypeIds = [
    3719738, // Kinderhaarschnitt
    3719739, // Trockenschnitt
    3719740, // Waschen & Styling
    3719741, // Waschen, schneiden, föhnen
    3719742, // Haarspende
    3719743, // Beratung
    3719744, // Hairdetox
    3719745, // Rebuild Treatment Olaplex
    3719746, // Intensiv Pflege Maria Nila
    3719747, // Gloss
    3719748, // Ansatzfärbung, waschen, schneiden, föhnen
    3719749, // Ansatz, Längenausgleich, waschen, schneiden, föhnen
    3719750, // Klassisches Strähnen-Paket
    3719751, // Globale Blondierung
    3719752, // Strähnentechnik Balayage
    3719753, // Faceframe
];

echo "=== EVENT TYPES KORRIGIEREN ===" . PHP_EOL;
echo "Anzahl: " . count($eventTypeIds) . PHP_EOL;
echo PHP_EOL;

$fixed = 0;
$failed = 0;

foreach ($eventTypeIds as $idx => $eventTypeId) {
    $num = $idx + 1;
    echo "[$num/" . count($eventTypeIds) . "] Event Type {$eventTypeId}..." . PHP_EOL;

    try {
        // Update Event Type
        $payload = [
            'schedulingType' => 'roundRobin',  // ← FIX: collective → roundRobin
            'hosts' => $hosts,                 // ← FIX: Hosts hinzufügen
            'locations' => [
                ['type' => 'attendeeDefined']  // ← FIX: Video → attendeeDefined
            ],
        ];

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $apiKey,
            'cal-api-version' => '2024-08-13',
            'Content-Type' => 'application/json'
        ])->patch($baseUrl . '/teams/' . $teamId . '/event-types/' . $eventTypeId, $payload);

        if ($response->successful()) {
            echo "  ✅ Aktualisiert" . PHP_EOL;
            $fixed++;
        } else {
            echo "  ❌ Fehler: " . $response->status() . PHP_EOL;
            echo "     " . $response->body() . PHP_EOL;
            $failed++;
        }

    } catch (Exception $e) {
        echo "  ❌ Exception: " . $e->getMessage() . PHP_EOL;
        $failed++;
    }

    echo PHP_EOL;
}

echo "=== ZUSAMMENFASSUNG ===" . PHP_EOL;
echo "✅ Erfolgreich: {$fixed}" . PHP_EOL;
echo "❌ Fehler: {$failed}" . PHP_EOL;
echo PHP_EOL;

if ($fixed > 0) {
    echo "=== ÄNDERUNGEN ===" . PHP_EOL;
    echo "1. schedulingType: collective → roundRobin" . PHP_EOL;
    echo "2. Hosts hinzugefügt: 2 Mitarbeiter (User IDs: 1414768, 1346408)" . PHP_EOL;
    echo "3. Location: cal-video → attendeeDefined" . PHP_EOL;
    echo PHP_EOL;
    echo "Die Event Types sind jetzt identisch zu den alten Services!" . PHP_EOL;
}
