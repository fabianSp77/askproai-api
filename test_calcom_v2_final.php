<?php
require_once 'vendor/autoload.php';

use App\Services\CalcomV2Service;

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$service = new CalcomV2Service();

echo "\n=== Cal.com V2 API Test (Final) ===\n\n";

// 1. Event Types testen (nutzt V1 als Fallback)
echo "1. Testing Event Types (via V1 fallback)...\n";
$eventTypes = $service->getEventTypes();
if ($eventTypes && isset($eventTypes['event_types'])) {
    echo "✅ Event Types erfolgreich abgerufen:\n";
    foreach ($eventTypes['event_types'] as $type) {
        echo "   - ID: {$type['id']} | Name: {$type['title']}\n";
    }
} else {
    echo "❌ Fehler beim Abrufen der Event Types\n";
}

// 2. Verfügbarkeit testen mit V2 slots/available (FUNKTIONIERT!)
echo "\n2. Testing V2 Availability (slots/available)...\n";
$eventTypeId = 2026302;
$tomorrow = date('Y-m-d 09:00:00', strtotime('+1 day'));
$dayAfter = date('Y-m-d 18:00:00', strtotime('+2 days'));

$availability = $service->checkAvailability($eventTypeId, $tomorrow, $dayAfter);
if ($availability && isset($availability['data']['slots'])) {
    echo "✅ V2 Verfügbarkeit erfolgreich geprüft!\n";
    $totalSlots = 0;
    foreach ($availability['data']['slots'] as $date => $slots) {
        $count = count($slots);
        $totalSlots += $count;
        echo "   $date: $count verfügbare Slots\n";
        // Zeige erste 3 Slots pro Tag
        for ($i = 0; $i < min(3, $count); $i++) {
            $time = new DateTime($slots[$i]['time']);
            $time->setTimezone(new DateTimeZone('Europe/Berlin'));
            echo "     - " . $time->format('H:i') . " Uhr\n";
        }
    }
    echo "   Gesamt: $totalSlots verfügbare Slots\n";
} else {
    echo "❌ Fehler beim Prüfen der Verfügbarkeit\n";
}

// 3. Buchung testen
echo "\n3. Testing V2 Booking...\n";
if ($availability && isset($availability['data']['slots'])) {
    // Nimm den ersten verfügbaren Slot
    $firstDate = array_key_first($availability['data']['slots']);
    $firstSlot = $availability['data']['slots'][$firstDate][0]['time'] ?? null;
    
    if ($firstSlot) {
        $startTime = new DateTime($firstSlot);
        $startTime->setTimezone(new DateTimeZone('Europe/Berlin'));
        $endTime = clone $startTime;
        $endTime->modify('+30 minutes');
        
        echo "   Versuche Buchung für: " . $startTime->format('d.m.Y H:i') . " Uhr\n";
        
        $customerData = [
            'name' => 'V2 Test ' . date('H:i'),
            'email' => 'v2test_' . time() . '@example.com',
            'phone' => '+49123456789',
            'service' => 'Haarschnitt'
        ];
        
        $booking = $service->bookAppointment(
            $eventTypeId,
            $startTime->format('Y-m-d H:i:s'),
            $endTime->format('Y-m-d H:i:s'),
            $customerData,
            'V2 Test Buchung - ' . date('d.m.Y H:i')
        );
        
        if ($booking) {
            echo "   ✅ Buchung erfolgreich!\n";
            if (isset($booking['data']['id'])) {
                echo "   Booking ID: " . $booking['data']['id'] . "\n";
                echo "   UID: " . ($booking['data']['uid'] ?? 'N/A') . "\n";
            } elseif (isset($booking['id'])) {
                echo "   Booking ID: " . $booking['id'] . "\n";
                echo "   UID: " . ($booking['uid'] ?? 'N/A') . "\n";
            }
        } else {
            echo "   ❌ Buchung fehlgeschlagen\n";
        }
    } else {
        echo "   ❌ Keine verfügbaren Slots gefunden\n";
    }
}

echo "\n=== Test abgeschlossen ===\n";

// Logs der letzten Minuten anzeigen
echo "\nLetzte Cal.com Log-Einträge:\n";
$logs = shell_exec("tail -n 20 storage/logs/laravel-" . date('Y-m-d') . ".log | grep -i 'cal.com'");
echo $logs ?: "Keine aktuellen Logs gefunden\n";
