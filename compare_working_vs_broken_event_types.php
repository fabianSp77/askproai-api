<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Company;
use App\Services\CalcomV2Client;
use App\Models\CalcomEventMap;
use App\Models\Service;

$company = Company::first();
$client = new CalcomV2Client($company);

echo "═══════════════════════════════════════════════════════════════\n";
echo "Comparing WORKING vs. BROKEN Event Types\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

// Find a working composite service (existing one that worked before)
$existingMaps = CalcomEventMap::where('service_id', '!=', 440)
    ->where('service_id', '!=', 442)
    ->where('service_id', '!=', 444)
    ->whereNotNull('event_type_id')
    ->get();

echo "Found " . $existingMaps->count() . " existing CalcomEventMaps (non-new services)\n\n";

if ($existingMaps->count() > 0) {
    // Get a working Event Type
    $workingMap = $existingMaps->first();
    $workingEventTypeId = $workingMap->event_type_id;

    echo "═══════════════════════════════════════════════════════════════\n";
    echo "WORKING Event Type: {$workingEventTypeId}\n";
    echo "═══════════════════════════════════════════════════════════════\n\n";

    $workingResponse = $client->getEventType($workingEventTypeId);

    if ($workingResponse->successful()) {
        $workingET = $workingResponse->json('data');

        echo "ID: {$workingET['id']}\n";
        echo "Title: {$workingET['title']}\n";
        echo "Slug: {$workingET['slug']}\n\n";

        echo "Hosts:\n";
        foreach ($workingET['hosts'] ?? [] as $host) {
            echo "  - {$host['name']} (User ID: {$host['userId']})\n";
        }
        echo "Host count: " . count($workingET['hosts'] ?? []) . "\n\n";

        echo "Metadata:\n";
        echo json_encode($workingET['metadata'] ?? [], JSON_PRETTY_PRINT) . "\n\n";

        // Try to book it
        echo "Testing booking capability...\n";
        $testBooking = [
            'eventTypeId' => $workingEventTypeId,
            'start' => '2025-11-28T15:00:00.000Z',
            'name' => 'Test User',
            'email' => 'test@example.com',
            'timeZone' => 'Europe/Berlin',
        ];

        $bookingResponse = $client->createBooking($testBooking);

        if ($bookingResponse->successful()) {
            echo "✅ CAN be booked\n";
            $booking = $bookingResponse->json('data');
            // Cancel immediately
            if (isset($booking['uid'])) {
                $client->cancelBooking($booking['uid'], 'Test - cancelled immediately');
                echo "   (Test booking cancelled)\n";
            }
        } else {
            echo "❌ CANNOT be booked\n";
            echo "   Error: {$bookingResponse->body()}\n";
        }
    }
}

echo "\n";
echo "═══════════════════════════════════════════════════════════════\n";
echo "BROKEN Event Type: 3982562\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

$brokenResponse = $client->getEventType(3982562);

if ($brokenResponse->successful()) {
    $brokenET = $brokenResponse->json('data');

    echo "ID: {$brokenET['id']}\n";
    echo "Title: {$brokenET['title']}\n";
    echo "Slug: {$brokenET['slug']}\n\n";

    echo "Hosts:\n";
    foreach ($brokenET['hosts'] ?? [] as $host) {
        echo "  - {$host['name']} (User ID: {$host['userId']})\n";
    }
    echo "Host count: " . count($brokenET['hosts'] ?? []) . "\n\n";

    echo "Metadata:\n";
    echo json_encode($brokenET['metadata'] ?? [], JSON_PRETTY_PRINT) . "\n\n";

    // Try to book it
    echo "Testing booking capability...\n";
    $testBooking = [
        'eventTypeId' => 3982562,
        'start' => '2025-11-28T15:00:00.000Z',
        'name' => 'Test User',
        'email' => 'test@example.com',
        'timeZone' => 'Europe/Berlin',
    ];

    $bookingResponse = $client->createBooking($testBooking);

    if ($bookingResponse->successful()) {
        echo "✅ CAN be booked\n";
    } else {
        echo "❌ CANNOT be booked\n";
        $errorData = $bookingResponse->json();
        if (isset($errorData['error']['message'])) {
            echo "   Error: {$errorData['error']['message']}\n";
        }
    }
}

echo "\n";
echo "═══════════════════════════════════════════════════════════════\n";
echo "KEY DIFFERENCES\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

if (isset($workingET, $brokenET)) {
    echo "Working Event Type:\n";
    echo "  Hosts: " . count($workingET['hosts'] ?? []) . "\n";
    echo "  Metadata managedEventConfig: " . json_encode($workingET['metadata']['managedEventConfig'] ?? 'not set') . "\n";
    echo "  Bookable: YES\n\n";

    echo "Broken Event Type:\n";
    echo "  Hosts: " . count($brokenET['hosts'] ?? []) . "\n";
    echo "  Metadata managedEventConfig: " . json_encode($brokenET['metadata']['managedEventConfig'] ?? 'not set') . "\n";
    echo "  Bookable: NO\n\n";

    echo "HYPOTHESIS:\n";
    if (count($workingET['hosts'] ?? []) !== count($brokenET['hosts'] ?? [])) {
        echo "  → Number of hosts differs\n";
        echo "  → Working has " . count($workingET['hosts'] ?? []) . " host(s)\n";
        echo "  → Broken has " . count($brokenET['hosts'] ?? []) . " host(s)\n";
        echo "  → Conclusion: Multi-host Event Types may require special configuration\n";
    } else {
        echo "  → Both have same number of hosts\n";
        echo "  → Difference must be in creation method or configuration\n";
    }
}
