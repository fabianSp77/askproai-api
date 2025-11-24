<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Company;
use App\Services\CalcomV2Client;

$company = Company::first();
$client = new CalcomV2Client($company);

echo "═══════════════════════════════════════════════════════════════\n";
echo "Testing: Can we specify HOST when booking?\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

$brokenEventTypeId = 3982562;

echo "Event Type: {$brokenEventTypeId}\n";
echo "Hosts: 1346408 (fabianspitzer), 1414768 (askproai)\n\n";

// Attempt 1: Specify hosts array in payload
echo "Attempt 1: Include 'hosts' in booking payload\n";
$bookingPayload1 = [
    'eventTypeId' => $brokenEventTypeId,
    'start' => '2025-11-28T15:00:00.000Z',
    'name' => 'Test User',
    'email' => 'test@example.com',
    'timeZone' => 'Europe/Berlin',
    'hosts' => [
        ['userId' => 1346408] // Specify which host to use
    ]
];

try {
    $response1 = $client->createBooking($bookingPayload1);

    if ($response1->successful()) {
        echo "  ✅ BOOKING SUCCESSFUL with host specification!\n";
        $booking = $response1->json('data');
        echo "  Booking ID: {$booking['id']}\n";
        echo "  Booking UID: {$booking['uid']}\n";

        // Check which host was assigned
        if (isset($booking['attendees'])) {
            echo "  Attendees: " . json_encode($booking['attendees']) . "\n";
        }

        // Cancel immediately
        $client->cancelBooking($booking['uid'], 'Test - cancelled');
        echo "  (Test booking cancelled)\n\n";

        echo "✅ SOLUTION FOUND: We can use existing Event Types by specifying host!\n";
        exit(0);
    } else {
        echo "  ❌ Failed: {$response1->status()}\n";
        $errorData = $response1->json();
        echo "  Error: " . ($errorData['error']['message'] ?? 'Unknown') . "\n\n";
    }
} catch (\Exception $e) {
    echo "  ❌ Exception: {$e->getMessage()}\n\n";
}

// Attempt 2: Use 'attendees' instead of 'hosts'
echo "Attempt 2: Use 'attendees' field\n";
$bookingPayload2 = [
    'eventTypeId' => $brokenEventTypeId,
    'start' => '2025-11-28T15:00:00.000Z',
    'name' => 'Test User',
    'email' => 'test@example.com',
    'timeZone' => 'Europe/Berlin',
    'attendees' => [
        ['userId' => 1346408]
    ]
];

try {
    $response2 = $client->createBooking($bookingPayload2);

    if ($response2->successful()) {
        echo "  ✅ BOOKING SUCCESSFUL with attendees specification!\n";
        $booking = $response2->json('data');
        echo "  Booking ID: {$booking['id']}\n";

        $client->cancelBooking($booking['uid'], 'Test - cancelled');
        echo "  (Test booking cancelled)\n\n";

        echo "✅ SOLUTION FOUND: We can use existing Event Types with attendees!\n";
        exit(0);
    } else {
        echo "  ❌ Failed: {$response2->status()}\n";
        $errorData = $response2->json();
        echo "  Error: " . ($errorData['error']['message'] ?? 'Unknown') . "\n\n";
    }
} catch (\Exception $e) {
    echo "  ❌ Exception: {$e->getMessage()}\n\n";
}

// Attempt 3: Try with hostId field
echo "Attempt 3: Use 'hostId' field\n";
$bookingPayload3 = [
    'eventTypeId' => $brokenEventTypeId,
    'start' => '2025-11-28T15:00:00.000Z',
    'name' => 'Test User',
    'email' => 'test@example.com',
    'timeZone' => 'Europe/Berlin',
    'hostId' => 1346408
];

try {
    $response3 = $client->createBooking($bookingPayload3);

    if ($response3->successful()) {
        echo "  ✅ BOOKING SUCCESSFUL with hostId!\n";
        $booking = $response3->json('data');
        echo "  Booking ID: {$booking['id']}\n";

        $client->cancelBooking($booking['uid'], 'Test - cancelled');
        echo "  (Test booking cancelled)\n\n";

        echo "✅ SOLUTION FOUND: We can use existing Event Types with hostId!\n";
        exit(0);
    } else {
        echo "  ❌ Failed: {$response3->status()}\n";
        $errorData = $response3->json();
        echo "  Error: " . ($errorData['error']['message'] ?? 'Unknown') . "\n\n";
    }
} catch (\Exception $e) {
    echo "  ❌ Exception: {$e->getMessage()}\n\n";
}

echo "═══════════════════════════════════════════════════════════════\n";
echo "CONCLUSION\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

echo "❌ All attempts to specify host failed.\n";
echo "   The 'managedEventConfig' issue cannot be worked around.\n\n";

echo "NEXT STEP: Check if we can DELETE managed config in Cal.com UI\n";
echo "           and recreate Event Types correctly.\n";
