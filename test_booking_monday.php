<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Services\CalcomService;

echo "=== Buchungstest für Montag ===\n\n";

$apiKey = env('CALCOM_API_KEY');
$userId = env('CALCOM_USER_ID');
$eventTypeId = env('CALCOM_EVENT_TYPE_ID');

try {
    $calcomService = new CalcomService();
    
    // Montag, 10. Juni 2025, 10:00 Uhr
    $bookingDate = new DateTime('2025-06-10');
    $bookingDate->setTime(10, 0, 0);
    $startDateTime = $bookingDate->format('Y-m-d\TH:i:sP');
    
    echo "Buchungsversuch für: Montag, 10. Juni 2025, 10:00 Uhr\n";
    echo "DateTime: $startDateTime\n\n";
    
    $result = $calcomService->createBookingWithConfirmation(
        $apiKey,
        $userId,
        $eventTypeId,
        $startDateTime,
        'Test Buchung Montag ' . date('H:i:s'),
        'fabianspitzer@icloud.com',
        1
    );
    
    if ($result['success']) {
        echo "✅ ERFOLG!\n";
        echo "Buchungs-ID: " . $result['booking']['id'] . "\n";
        echo "UID: " . $result['booking']['uid'] . "\n";
        echo "\nDer Termin wurde erfolgreich gebucht!\n";
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
