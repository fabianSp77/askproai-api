<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Company;
use App\Services\CalcomV2Client;

$company = Company::first();
$client = new CalcomV2Client($company);

echo "═══════════════════════════════════════════════════════════════\n";
echo "DEEP ANALYSIS: Why Working Event Types Work\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

// Get FULL details of working Event Type
$workingId = 3757759; // Dauerwelle: Auswaschen & Pflege (funktioniert)
$brokenId = 3982562;  // Ansatzfärbung: Ansatzfärbung auftragen (kaputt)

echo "WORKING Event Type: {$workingId}\n";
echo str_repeat("-", 70) . "\n";

$workingResponse = $client->getEventType($workingId);
$working = $workingResponse->json('data');

echo "Title: {$working['title']}\n";
echo "Slug: {$working['slug']}\n";
echo "Length: {$working['lengthInMinutes']} min\n\n";

echo "HOSTS:\n";
foreach ($working['hosts'] as $host) {
    echo "  - User ID: {$host['userId']}\n";
    echo "    Name: {$host['name']}\n";
    echo "    Username: {$host['username']}\n";
    echo "    Priority: " . ($host['priority'] ?? 'not set') . "\n";
    echo "    Mandatory: " . json_encode($host['mandatory'] ?? 'not set') . "\n";
    echo "\n";
}

echo "METADATA:\n";
echo json_encode($working['metadata'], JSON_PRETTY_PRINT) . "\n\n";

echo "LOCATIONS:\n";
echo json_encode($working['locations'], JSON_PRETTY_PRINT) . "\n\n";

echo "BOOKING FIELDS:\n";
foreach ($working['bookingFields'] as $field) {
    echo "  - {$field['slug']}: {$field['type']} (required: " . json_encode($field['required']) . ")\n";
}
echo "\n";

echo "═══════════════════════════════════════════════════════════════\n";
echo "BROKEN Event Type: {$brokenId}\n";
echo str_repeat("-", 70) . "\n";

$brokenResponse = $client->getEventType($brokenId);
$broken = $brokenResponse->json('data');

echo "Title: {$broken['title']}\n";
echo "Slug: {$broken['slug']}\n";
echo "Length: {$broken['lengthInMinutes']} min\n\n";

echo "HOSTS:\n";
foreach ($broken['hosts'] as $host) {
    echo "  - User ID: {$host['userId']}\n";
    echo "    Name: {$host['name']}\n";
    echo "    Username: {$host['username']}\n";
    echo "    Priority: " . ($host['priority'] ?? 'not set') . "\n";
    echo "    Mandatory: " . json_encode($host['mandatory'] ?? 'not set') . "\n";
    echo "\n";
}

echo "METADATA:\n";
echo json_encode($broken['metadata'], JSON_PRETTY_PRINT) . "\n\n";

echo "LOCATIONS:\n";
echo json_encode($broken['locations'], JSON_PRETTY_PRINT) . "\n\n";

echo "BOOKING FIELDS:\n";
foreach ($broken['bookingFields'] as $field) {
    echo "  - {$field['slug']}: {$field['type']} (required: " . json_encode($field['required']) . ")\n";
}
echo "\n";

echo "═══════════════════════════════════════════════════════════════\n";
echo "CRITICAL DIFFERENCES\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

// Compare Metadata
echo "Metadata Comparison:\n";
echo "  Working: " . json_encode($working['metadata']) . "\n";
echo "  Broken:  " . json_encode($broken['metadata']) . "\n\n";

if ($working['metadata'] !== $broken['metadata']) {
    echo "  ⚠️  METADATA DIFFERS!\n\n";
}

// Compare Hosts
echo "Host Comparison:\n";
echo "  Working host count: " . count($working['hosts']) . "\n";
echo "  Broken host count:  " . count($broken['hosts']) . "\n\n";

// Host structure comparison
if (isset($working['hosts'][0]['priority'])) {
    echo "  Working has priority field: " . $working['hosts'][0]['priority'] . "\n";
} else {
    echo "  Working has NO priority field\n";
}

if (isset($broken['hosts'][0]['priority'])) {
    echo "  Broken has priority field: " . $broken['hosts'][0]['priority'] . "\n";
} else {
    echo "  Broken has NO priority field\n";
}
echo "\n";

// Try UPDATE to fix broken
echo "═══════════════════════════════════════════════════════════════\n";
echo "ATTEMPTING FIX: Update Broken Event Type\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

// Attempt 1: Try to remove managedEventConfig by explicitly setting metadata to NULL
echo "Attempt 1: Set metadata to null\n";
$updateResponse1 = $client->updateEventType($brokenId, [
    'metadata' => null
]);

if ($updateResponse1->successful()) {
    echo "  ✅ Update successful\n";
    $verifyResponse1 = $client->getEventType($brokenId);
    $verified1 = $verifyResponse1->json('data.metadata');
    echo "  New metadata: " . json_encode($verified1) . "\n";
} else {
    echo "  ❌ Update failed: {$updateResponse1->status()}\n";
    echo "  Response: {$updateResponse1->body()}\n";
}
echo "\n";

// Attempt 2: Try to copy ALL fields from working Event Type
echo "Attempt 2: Copy exact structure from working Event Type\n";
$updatePayload = [
    'locations' => $working['locations'],
    'bookingFields' => $working['bookingFields'],
];

$updateResponse2 = $client->updateEventType($brokenId, $updatePayload);

if ($updateResponse2->successful()) {
    echo "  ✅ Update successful\n";

    // Now try to book it
    $testBooking = [
        'eventTypeId' => $brokenId,
        'start' => '2025-11-28T15:00:00.000Z',
        'name' => 'Test User',
        'email' => 'test@example.com',
        'timeZone' => 'Europe/Berlin',
    ];

    try {
        $bookingResponse = $client->createBooking($testBooking);

        if ($bookingResponse->successful()) {
            echo "  ✅ NOW BOOKABLE!\n";
            $booking = $bookingResponse->json('data');
            if (isset($booking['uid'])) {
                $client->cancelBooking($booking['uid'], 'Test - cancelled');
                echo "  (Test booking cancelled)\n";
            }
        } else {
            echo "  ❌ Still not bookable\n";
            $errorData = $bookingResponse->json();
            echo "  Error: " . ($errorData['error']['message'] ?? 'Unknown') . "\n";
        }
    } catch (\Exception $e) {
        echo "  ❌ Exception: {$e->getMessage()}\n";
    }
} else {
    echo "  ❌ Update failed: {$updateResponse2->status()}\n";
}
echo "\n";

echo "═══════════════════════════════════════════════════════════════\n";
echo "CONCLUSION\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

echo "If Attempt 1 or 2 worked:\n";
echo "  → We can fix the existing Event Types via API\n";
echo "  → No need to create new ones\n\n";

echo "If both failed:\n";
echo "  → Event Types are fundamentally broken\n";
echo "  → Need to investigate Cal.com UI creation vs. API creation\n";
echo "  → Possibly need to recreate via Cal.com UI (not API)\n";
