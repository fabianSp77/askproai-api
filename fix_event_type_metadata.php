<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Company;
use App\Services\CalcomV2Client;

$company = Company::first();
$client = new CalcomV2Client($company);

echo "═══════════════════════════════════════════════════════════════\n";
echo "Fixing Event Type Metadata (Remove managedEventConfig)\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

$brokenEventTypes = [
    3982562, 3982564, 3982566, 3982568, // Ansatzfärbung
    3982570, 3982572, 3982574, 3982576, // Ansatz + Längenausgleich
    3982578, 3982580, 3982582, 3982584, // Komplette Umfärbung
];

foreach ($brokenEventTypes as $eventTypeId) {
    echo "Processing Event Type {$eventTypeId}...\n";

    // Get current data
    $getResponse = $client->getEventType($eventTypeId);

    if (!$getResponse->successful()) {
        echo "  ❌ Failed to get Event Type\n";
        continue;
    }

    $currentData = $getResponse->json('data');
    echo "  Current title: {$currentData['title']}\n";
    echo "  Current metadata: " . json_encode($currentData['metadata'] ?? []) . "\n";

    // Attempt 1: Update with empty metadata
    echo "  Attempting to clear metadata...\n";

    $updatePayload = [
        'metadata' => [] // Try to set to empty object
    ];

    $updateResponse = $client->updateEventType($eventTypeId, $updatePayload);

    if ($updateResponse->successful()) {
        echo "  ✅ Update successful\n";

        // Verify the change
        $verifyResponse = $client->getEventType($eventTypeId);
        if ($verifyResponse->successful()) {
            $newData = $verifyResponse->json('data');
            echo "  New metadata: " . json_encode($newData['metadata'] ?? []) . "\n";

            // Test if it's now bookable
            $testBooking = [
                'eventTypeId' => $eventTypeId,
                'start' => '2025-11-28T15:00:00.000Z',
                'name' => 'Test User',
                'email' => 'test@example.com',
                'timeZone' => 'Europe/Berlin',
            ];

            $bookingResponse = $client->createBooking($testBooking);

            if ($bookingResponse->successful()) {
                echo "  ✅ NOW BOOKABLE!\n";
                // Cancel test booking
                $booking = $bookingResponse->json('data');
                if (isset($booking['uid'])) {
                    $client->cancelBooking($booking['uid'], 'Test - cancelled');
                }
            } else {
                echo "  ❌ Still not bookable\n";
                $errorData = $bookingResponse->json();
                if (isset($errorData['error']['message'])) {
                    echo "  Error: {$errorData['error']['message']}\n";
                }
            }
        }
    } else {
        echo "  ❌ Update failed: {$updateResponse->body()}\n";
    }

    echo "\n";
}

echo "═══════════════════════════════════════════════════════════════\n";
echo "Summary\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

echo "If this worked, all Event Types should now be bookable.\n";
echo "If not, we need to:\n";
echo "  1. Delete these Event Types in Cal.com UI\n";
echo "  2. Create new ones WITHOUT managed event config\n";
echo "  3. Update CalcomEventMaps with new IDs\n";
