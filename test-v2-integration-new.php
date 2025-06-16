<?php
require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->handle($request = Illuminate\Http\Request::capture());

use App\Services\CalcomV2Service;
use App\Models\Call;

echo "Testing Cal.com V2 Integration...\n\n";

$service = new CalcomV2Service();

// 1. Event Types abrufen
echo "1. Getting Event Types...\n";
$eventTypes = $service->getEventTypes();
if ($eventTypes) {
    echo "✓ Event Types retrieved successfully\n";
    // Zeige verfügbare Event Types
    if (isset($eventTypes['event_types'])) {
        foreach ($eventTypes['event_types'] as $type) {
            echo "  - ID: {$type['id']}, Title: {$type['title']}\n";
        }
    }
    echo "\n";
} else {
    echo "✗ Failed to get event types\n\n";
}

// 2. Test Booking erstellen mit zufälliger Zeit
echo "2. Creating test booking...\n";

// Zufällige Zeit generieren um Konflikte zu vermeiden
$randomDay = rand(3, 7); // 3-7 Tage in der Zukunft
$randomHour = rand(9, 16); // 9-16 Uhr
$bookingDate = new DateTime("+$randomDay days");
$bookingDate->setTime($randomHour, 0);
$startTime = $bookingDate->format('Y-m-d\TH:i:s') . 'Z';

// Eindeutige Test-ID
$testId = 'v2_test_' . time();

echo "Booking for: " . $bookingDate->format('Y-m-d H:i') . "\n";

$customerData = [
    'name' => 'Test Kunde ' . date('H:i'),
    'email' => 'test-' . time() . '@askproai.de',
    'phone' => '+49170' . rand(1000000, 9999999)
];

// Test Call erstellen
$testCall = Call::create([
    'call_id' => $testId,
    'phone_number' => $customerData['phone'],
    'datum_termin' => $bookingDate->format('Y-m-d'),
    'uhrzeit_termin' => $bookingDate->format('H:i:s'),
    'dienstleistung' => 'Haarschnitt',
    'name' => $customerData['name'],
    'email' => $customerData['email'],
    'call_status' => 'completed',
    'call_successful' => true,
    'successful' => true,
    'type' => 'inbound',
    'raw_data' => json_encode([
        'call_id' => $testId,
        'phone_number' => $customerData['phone'],
        '_datum__termin' => $bookingDate->format('Y-m-d'),
        '_uhrzeit__termin' => $bookingDate->format('H:i'),
        '_dienstleistung' => 'Haarschnitt',
        '_name' => $customerData['name'],
        '_email' => $customerData['email'],
        'status' => 'completed',
        'call_successful' => true
    ])
]);

$booking = $service->bookAppointment(
    2026302, // Event Type ID
    $startTime,
    null,
    $customerData,
    'Test-Buchung erstellt am ' . date('Y-m-d H:i:s')
);

if ($booking) {
    echo "✓ Booking created successfully!\n";
    if (isset($booking['data']['id'])) {
        echo "Booking ID: " . $booking['data']['id'] . "\n";
        $testCall->update(['calcom_booking_id' => $booking['data']['id']]);
    } elseif (isset($booking['id'])) {
        echo "Booking ID: " . $booking['id'] . "\n";
        $testCall->update(['calcom_booking_id' => $booking['id']]);
    }
    echo "Details: " . json_encode($booking, JSON_PRETTY_PRINT) . "\n\n";
} else {
    echo "✗ Failed to create booking\n\n";
}

// 3. Bookings abrufen
echo "3. Getting bookings...\n";
$bookings = $service->getBookings(['limit' => 5]);
if ($bookings) {
    if (isset($bookings['bookings'])) {
        $count = count($bookings['bookings']);
        echo "✓ Retrieved $count bookings\n";
    } else {
        echo "✓ Bookings retrieved (format different than expected)\n";
    }
} else {
    echo "✗ Failed to get bookings\n";
}

echo "\n✅ V2 Integration tests completed!\n";
echo "Test ID was: $testId\n";
