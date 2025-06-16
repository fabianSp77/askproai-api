<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Services\CalcomService;

echo "=== Test der neuen CalcomService createBooking Methode ===\n\n";

$calcomService = new CalcomService();

$customerData = [
    'name' => 'Test Kunde',
    'email' => 'fabianspitzer@icloud.com',
    'phone' => '+49123456789'
];

$eventTypeId = 2026302; // 30 Minuten Termin
$startTime = '2025-06-12T14:00:00+02:00';

try {
    $booking = $calcomService->createBooking($eventTypeId, $startTime, $customerData);
    
    if ($booking) {
        echo "✅ Buchung erfolgreich!\n";
        echo "Booking ID: " . $booking['id'] . "\n";
        echo "UID: " . $booking['uid'] . "\n";
        echo "Status: " . $booking['status'] . "\n";
        echo "Start: " . $booking['startTime'] . "\n";
        echo "Ende: " . $booking['endTime'] . "\n";
    } else {
        echo "❌ Buchung fehlgeschlagen\n";
    }
} catch (\Exception $e) {
    echo "❌ Fehler: " . $e->getMessage() . "\n";
}
