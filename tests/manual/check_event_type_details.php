<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$calcomService = new CalcomService();
$eventTypeId = env('CALCOM_EVENT_TYPE_ID');

echo "=== Event-Type Details für ID: $eventTypeId ===\n\n";

$eventType = $calcomService->getEventType($eventTypeId);

if ($eventType) {
    echo "Titel: " . $eventType['title'] . "\n";
    echo "User ID: " . ($eventType['userId'] ?? 'N/A') . "\n";
    echo "Team ID: " . ($eventType['teamId'] ?? 'N/A') . "\n";
    echo "Schedule ID: " . ($eventType['scheduleId'] ?? 'N/A') . "\n";
    echo "Versteckt: " . ($eventType['hidden'] ? 'Ja' : 'Nein') . "\n";
    echo "Mindestvorlaufzeit: " . $eventType['minimumBookingNotice'] . " Minuten\n";
    echo "Zeitzone: " . ($eventType['timeZone'] ?? 'N/A') . "\n";
    
    if (isset($eventType['schedule'])) {
        echo "\nSchedule Details:\n";
        print_r($eventType['schedule']);
    }
    
    if (isset($eventType['hosts'])) {
        echo "\nHosts:\n";
        print_r($eventType['hosts']);
    }
} else {
    echo "Event-Type nicht gefunden!\n";
}

// Teste alternative Event-Types
echo "\n\n=== Test mit alternativen Event-Types ===\n";

$testEventTypes = [
    2547902 => '30 Minuten Termin mit Fabian Spitzer (Kopie)',
    2026301 => '15 Minuten Termin',
    2031135 => 'Herren: Waschen, Schneiden, Styling'
];

foreach ($testEventTypes as $id => $name) {
    echo "\nTeste Event-Type: $name (ID: $id)\n";
    
    $availability = $calcomService->checkAvailability($id, '2025-06-10', '2025-06-10');
    
    if ($availability && isset($availability['workingHours'])) {
        echo "✅ Verfügbarkeit vorhanden\n";
    } else {
        echo "❌ Keine Verfügbarkeit\n";
    }
}
