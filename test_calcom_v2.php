<?php
require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Services\CalcomV2Service;

$service = new CalcomV2Service();

// Event Types abrufen
echo "=== Testing Event Types ===\n";
$eventTypes = $service->getEventTypes();
if ($eventTypes) {
    echo "Event Types gefunden: " . count($eventTypes['data'] ?? []) . "\n";
    foreach (($eventTypes['data'] ?? []) as $type) {
        echo "- ID: {$type['id']}, Title: {$type['title']}\n";
    }
} else {
    echo "Fehler beim Abrufen der Event Types\n";
}

// Verfügbarkeit prüfen (Beispiel für morgen)
echo "\n=== Testing Availability ===\n";
$tomorrow = date('Y-m-d\TH:i:s\Z', strtotime('+1 day'));
$dayAfter = date('Y-m-d\TH:i:s\Z', strtotime('+2 days'));

// Hier müssen Sie eine gültige Event Type ID einsetzen
$eventTypeId = 123; // ANPASSEN!

$availability = $service->checkAvailability($eventTypeId, $tomorrow, $dayAfter);
if ($availability) {
    echo "Verfügbare Slots gefunden\n";
} else {
    echo "Fehler beim Prüfen der Verfügbarkeit\n";
}
