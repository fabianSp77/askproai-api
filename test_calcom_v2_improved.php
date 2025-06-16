<?php
require_once 'vendor/autoload.php';

use App\Services\CalcomV2Service;

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$service = new CalcomV2Service();

echo "\n=== Cal.com V2 API Test ===\n\n";

// 1. Event Types testen
echo "1. Testing Event Types...\n";
$eventTypes = $service->getEventTypes();
if ($eventTypes) {
    echo "✅ Event Types erfolgreich abgerufen:\n";
    foreach ($eventTypes['event_types'] ?? [] as $type) {
        echo "   - ID: {$type['id']} | Name: {$type['title']} | Slug: {$type['slug']}\n";
    }
} else {
    echo "❌ Fehler beim Abrufen der Event Types\n";
}

// 2. Verfügbarkeit testen
echo "\n2. Testing Availability...\n";
$eventTypeId = 2026302; // Ihre Event Type ID
$dateFrom = date('Y-m-d\TH:i:s\Z', strtotime('+1 day'));
$dateTo = date('Y-m-d\TH:i:s\Z', strtotime('+2 days'));

$availability = $service->checkAvailability($eventTypeId, $dateFrom, $dateTo);
if ($availability) {
    echo "✅ Verfügbarkeit erfolgreich geprüft\n";
    if (isset($availability['slots']) && count($availability['slots']) > 0) {
        echo "   Verfügbare Slots: " . count($availability['slots']) . "\n";
    }
} else {
    echo "❌ Fehler beim Prüfen der Verfügbarkeit\n";
}

// 3. Buchung testen (mit verschiedenen Zeiten)
echo "\n3. Testing Booking...\n";
$testTimes = [
    ['hour' => 10, 'minute' => 0],
    ['hour' => 14, 'minute' => 30],
    ['hour' => 16, 'minute' => 0],
];

$bookingSuccessful = false;
foreach ($testTimes as $time) {
    $startTime = date('Y-m-d', strtotime('+2 days')) . sprintf(' %02d:%02d:00', $time['hour'], $time['minute']);
    $endTime = date('Y-m-d H:i:s', strtotime($startTime . ' +30 minutes'));
    
    echo "   Versuche Buchung um {$time['hour']}:{$time['minute']} Uhr...\n";
    
    $customerData = [
        'name' => 'Test V2 Kunde ' . date('H:i'),
        'email' => 'testv2_' . time() . '@example.com',
        'phone' => '+49123456789',
        'service' => 'Test V2 Service'
    ];
    
    $booking = $service->bookAppointment(
        $eventTypeId,
        $startTime,
        $endTime,
        $customerData,
        'Test V2 Buchung um ' . date('H:i')
    );
    
    if ($booking) {
        echo "   ✅ Buchung erfolgreich!\n";
        echo "   Booking ID: " . ($booking['data']['id'] ?? 'N/A') . "\n";
        $bookingSuccessful = true;
        break;
    } else {
        echo "   ❌ Buchung fehlgeschlagen für diese Zeit\n";
    }
}

if (!$bookingSuccessful) {
    echo "\n❌ Alle Buchungsversuche fehlgeschlagen\n";
}

echo "\n=== Test abgeschlossen ===\n\n";
