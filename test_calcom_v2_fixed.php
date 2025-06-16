<?php
require_once 'vendor/autoload.php';

use App\Services\CalcomV2Service;

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$service = new CalcomV2Service();

echo "\n=== Cal.com V2 API Test (Fixed) ===\n\n";

// 1. Verfügbarkeit prüfen
echo "1. Prüfe Verfügbarkeit...\n";
$eventTypeId = 2026302;
$tomorrow = date('Y-m-d 09:00:00', strtotime('+1 day'));
$dayAfter = date('Y-m-d 18:00:00', strtotime('+2 days'));

$availability = $service->checkAvailability($eventTypeId, $tomorrow, $dayAfter);
if ($availability && isset($availability['data']['slots'])) {
    echo "✅ Verfügbare Slots gefunden\n";
    
    // Ersten verfügbaren Slot nehmen
    $firstDate = array_key_first($availability['data']['slots']);
    $firstSlot = $availability['data']['slots'][$firstDate][0]['time'] ?? null;
    
    if ($firstSlot) {
        $startTime = new DateTime($firstSlot);
        $startTime->setTimezone(new DateTimeZone('Europe/Berlin'));
        echo "   Erster Slot: " . $startTime->format('d.m.Y H:i') . " Uhr\n";
        
        // 2. Buchung versuchen
        echo "\n2. Erstelle Buchung...\n";
        
        $customerData = [
            'name' => 'Test Kunde Fixed',
            'email' => 'fixed_' . time() . '@example.com',
            'phone' => '+491234567890',
            'service' => 'Haarschnitt'
        ];
        
        $booking = $service->bookAppointment(
            $eventTypeId,
            $startTime->format('Y-m-d H:i:s'),
            '',  // End time wird von Cal.com berechnet
            $customerData,
            'Testbuchung nach Fix'
        );
        
        if ($booking) {
            echo "✅ Buchung erfolgreich!\n";
            if (isset($booking['data']['id'])) {
                echo "   Booking ID: " . $booking['data']['id'] . "\n";
                echo "   UID: " . ($booking['data']['uid'] ?? 'N/A') . "\n";
            }
        } else {
            echo "❌ Buchung fehlgeschlagen\n";
        }
    }
} else {
    echo "❌ Keine verfügbaren Slots\n";
}

echo "\n=== Test abgeschlossen ===\n";
