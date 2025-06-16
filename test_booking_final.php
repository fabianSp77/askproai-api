<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Services\CalcomService;

echo "=== Finaler Buchungstest ===\n\n";

$apiKey = env('CALCOM_API_KEY');
$userId = env('CALCOM_USER_ID');
$eventTypeId = env('CALCOM_EVENT_TYPE_ID');

echo "Konfiguration:\n";
echo "API Key: " . substr($apiKey, 0, 15) . "...\n";
echo "User ID: $userId\n";
echo "Event Type ID: $eventTypeId\n\n";

try {
    $calcomService = new CalcomService();
    
    // Datum für Montag, 9. Juni 2025, 17:00 Uhr (neuer Zeitslot)
    $bookingDate = new DateTime('2025-06-09');
    $bookingDate->setTime(17, 0, 0);
    $startDateTime = $bookingDate->format('Y-m-d\TH:i:sP');
    
    echo "Buchungsversuch für: $startDateTime\n\n";
    
    $result = $calcomService->createBookingWithConfirmation(
        $apiKey,
        $userId,
        $eventTypeId,
        $startDateTime,
        'Test Buchung via Service ' . date('H:i:s'),
        'fabianspitzer@icloud.com',
        1
    );
    
    if ($result['success']) {
        echo "✅ ERFOLG!\n";
        echo "Buchungs-ID: " . $result['booking']['id'] . "\n";
        echo "UID: " . $result['booking']['uid'] . "\n";
        echo "\nDer Termin wurde erfolgreich gebucht!\n";
        echo "Prüfen Sie Ihr E-Mail-Postfach und Ihren Kalender.\n";
    } else {
        echo "❌ FEHLER: " . $result['message'] . "\n";
        if (isset($result['details'])) {
            echo "\nDetails:\n";
            print_r($result['details']);
        }
    }
    
} catch (Exception $e) {
    echo "❌ Exception: " . $e->getMessage() . "\n";
}
