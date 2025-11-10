<?php

/**
 * Debug Cal.com Slots Response Structure
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

$startTime = Carbon::now('Europe/Berlin')->addDays(1)->setTime(14, 0, 0);
$endTime = $startTime->copy()->addHours(2);

echo "Checking availability...\n";
echo "Event Type: {$eventTypeId}\n";
echo "Start: {$startTime->toIso8601String()}\n";
echo "End: {$endTime->toIso8601String()}\n\n";

$response = Http::withHeaders([
    'cal-api-version' => $calcomApiVersion,
    'Authorization' => "Bearer {$calcomApiKey}",
])->get("{$calcomBaseUrl}/slots/available", [
    'eventTypeId' => $eventTypeId,
    'startTime' => $startTime->toIso8601String(),
    'endTime' => $endTime->toIso8601String(),
]);

echo "Status: " . $response->status() . "\n\n";
echo "Full Response:\n";
echo json_encode($response->json(), JSON_PRETTY_PRINT);
echo "\n";
