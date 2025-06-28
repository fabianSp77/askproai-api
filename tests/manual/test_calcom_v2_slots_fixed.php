<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Testdaten vorbereiten
$apiKey = env('DEFAULT_CALCOM_API_KEY');
$service = new CalcomV2Service($apiKey);

echo "=== Test Cal.com V2 Slots (Fixed) ===\n\n";

// 1. Event Types holen
echo "1. Hole Event Types...\n";
$eventTypes = $service->getEventTypes();

if ($eventTypes && isset($eventTypes['event_types'])) {
    echo "✓ Gefundene Event Types: " . count($eventTypes['event_types']) . "\n";
    
    if (count($eventTypes['event_types']) > 0) {
        $firstEventType = $eventTypes['event_types'][0];
        $eventTypeId = $firstEventType['id'];
        echo "✓ Verwende Event Type: {$firstEventType['title']} (ID: {$eventTypeId})\n\n";
        
        // 2. Verfügbarkeit prüfen
        echo "2. Prüfe Verfügbarkeit für morgen...\n";
        $tomorrow = date('Y-m-d', strtotime('+1 day'));
        
        $availability = $service->checkAvailability($eventTypeId, $tomorrow);
        
        if ($availability['success']) {
            echo "✓ Verfügbarkeit erfolgreich abgerufen\n";
            echo "✓ Raw slots structure (für Debugging):\n";
            print_r($availability['data']['raw_slots']);
            echo "\n";
            
            $slots = $availability['data']['slots'];
            echo "✓ Gefundene Slots (geflattened): " . count($slots) . "\n";
            
            if (count($slots) > 0) {
                echo "✓ Erste 5 verfügbare Slots:\n";
                foreach (array_slice($slots, 0, 5) as $slot) {
                    echo "  - " . $slot . "\n";
                }
                
                // Test ob Slots jetzt als einfache Strings vorliegen
                echo "\n✓ Slot Format Test:\n";
                $firstSlot = $slots[0];
                echo "  - Erster Slot: " . $firstSlot . "\n";
                echo "  - Slot ist String: " . (is_string($firstSlot) ? 'JA' : 'NEIN') . "\n";
                echo "  - Slot Format korrekt: " . (preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}/', $firstSlot) ? 'JA' : 'NEIN') . "\n";
            } else {
                echo "⚠️  Keine Slots gefunden für {$tomorrow}\n";
            }
        } else {
            echo "✗ Fehler beim Abrufen der Verfügbarkeit: " . $availability['error'] . "\n";
        }
    }
} else {
    echo "✗ Keine Event Types gefunden\n";
}

echo "\n=== Test abgeschlossen ===\n";