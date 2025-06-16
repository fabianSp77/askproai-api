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
    echo "✓ Event Types retrieved successfully\n\n";
} else {
    echo "✗ Failed to get event types\n\n";
}

// 2. Test Booking erstellen
echo "2. Creating test booking...\n";

// Datum für übermorgen um 15:00 Uhr
$bookingDate = new DateTime('+2 days');
$bookingDate->setTime(15, 0);
$startTime = $bookingDate->format('Y-m-d\TH:i:s') . 'Z';

$customerData = [
    'name' => 'V2 Integration Test',
    'email' => 'v2test@askproai.de',
    'phone' => '+491234567890'
];

// Test Call erstellen
$testCall = Call::create([
    'call_id' => 'v2_test_002',
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
        'call_id' => 'v2_test_002',
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
    'Test-Notiz von V2 Integration'
);

if ($booking) {
    echo "✓ Booking created successfully!\n";
    echo "Booking ID: " . ($booking['data']['id'] ?? 'Unknown') . "\n\n";
    
    // Update Call mit Booking ID
    if (isset($booking['data']['id'])) {
        $testCall->update(['calcom_booking_id' => $booking['data']['id']]);
    }
} else {
    echo "✗ Failed to create booking\n\n";
}

// 3. Bookings abrufen
echo "3. Getting bookings...\n";
$bookings = $service->getBookings();
if ($bookings && isset($bookings['data'])) {
    $count = count($bookings['data']);
    echo "✓ Retrieved $count bookings\n\n";
} else {
    echo "✗ Failed to get bookings\n\n";
}

echo "✅ V2 Integration tests completed!\n";
