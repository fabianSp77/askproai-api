<?php

/**
 * Debug Cal.com Booking Response Structure
 */

require __DIR__ . '/../vendor/autoload.php';

use Illuminate\Support\Facades\Http;
use Carbon\Carbon;

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$calcomApiKey = config('services.calcom.api_key');
$calcomBaseUrl = config('services.calcom.base_url');
$calcomApiVersion = config('services.calcom.api_version');
$eventTypeId = 3757770;

$bookingSlot = Carbon::now('Europe/Berlin')->addDays(1)->setTime(15, 0, 0);

echo "Creating test booking...\n";
echo "Time: {$bookingSlot->format('Y-m-d H:i:s')} Europe/Berlin\n\n";

$response = Http::withHeaders([
    'cal-api-version' => $calcomApiVersion,
    'Authorization' => "Bearer {$calcomApiKey}",
])->post("{$calcomBaseUrl}/bookings", [
    'eventTypeId' => $eventTypeId,
    'start' => $bookingSlot->toIso8601String(),
    'attendee' => [
        'name' => 'Debug Test',
        'email' => 'debug.test.' . time() . '@askproai.de',
        'timeZone' => 'Europe/Berlin',
        'language' => 'de',
    ],
    'metadata' => [
        'phone' => '+493099999999',
        'debug' => 'true',
    ],
]);

echo "Status: " . $response->status() . "\n\n";
echo "Full Response:\n";
echo json_encode($response->json(), JSON_PRETTY_PRINT);
echo "\n\n";

// Cleanup
if ($response->successful()) {
    $bookingUid = $response->json('data.uid');
    if ($bookingUid) {
        echo "Cleaning up (cancelling {$bookingUid})...\n";
        Http::withHeaders([
            'cal-api-version' => $calcomApiVersion,
            'Authorization' => "Bearer {$calcomApiKey}",
        ])->delete("{$calcomBaseUrl}/bookings/{$bookingUid}", [
            'cancellationReason' => 'Debug test cleanup',
        ]);
        echo "Done.\n";
    }
}
