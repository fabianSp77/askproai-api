<?php

/**
 * Check All Cal.com Event Types Configuration Status
 *
 * Tests each event type by checking availability to verify:
 * - If the event type is active
 * - If it's properly configured
 * - If it returns available slots
 */

require __DIR__ . '/../vendor/autoload.php';

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "═══════════════════════════════════════════════════════════════\n";
echo "Cal.com Event Types - Configuration Status Check\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

$calcomApiKey = config('services.calcom.api_key');
$calcomBaseUrl = config('services.calcom.base_url');
$calcomApiVersion = config('services.calcom.api_version');

// Get all services with Event Type IDs
$services = DB::select(
    'SELECT id, name, calcom_event_type_id, priority
     FROM services
     WHERE company_id = 1
     AND calcom_event_type_id IS NOT NULL
     ORDER BY priority ASC, name ASC'
);

echo "📋 Testing " . count($services) . " Event Types...\n\n";

// Test time range (tomorrow, 9-18h)
$startTime = Carbon::now('Europe/Berlin')->addDays(1)->setTime(9, 0, 0);
$endTime = Carbon::now('Europe/Berlin')->addDays(1)->setTime(18, 0, 0);

$results = [];

foreach ($services as $service) {
    $eventTypeId = $service->calcom_event_type_id;

    echo "Testing: {$service->name} (Event: {$eventTypeId})...\n";

    try {
        // Check availability for this event type
        $response = Http::withHeaders([
            'cal-api-version' => $calcomApiVersion,
            'Authorization' => "Bearer {$calcomApiKey}",
        ])->timeout(10)->get("{$calcomBaseUrl}/slots/available", [
            'eventTypeId' => $eventTypeId,
            'startTime' => $startTime->toIso8601String(),
            'endTime' => $endTime->toIso8601String(),
        ]);

        if ($response->successful()) {
            $slotsData = $response->json('data.slots') ?? [];

            // Count total slots
            $totalSlots = 0;
            foreach ($slotsData as $date => $dateSlots) {
                $totalSlots += count($dateSlots);
            }

            if ($totalSlots > 0) {
                echo "   ✅ ACTIVE - {$totalSlots} slots available\n";
                $results[$eventTypeId] = [
                    'name' => $service->name,
                    'status' => 'active',
                    'slots' => $totalSlots
                ];
            } else {
                echo "   ⚠️  ACTIVE but NO SLOTS (check availability settings)\n";
                $results[$eventTypeId] = [
                    'name' => $service->name,
                    'status' => 'active_no_slots',
                    'slots' => 0
                ];
            }
        } else {
            $statusCode = $response->status();
            $error = $response->json('error.message') ?? 'Unknown error';

            if ($statusCode === 404) {
                echo "   ❌ NOT FOUND (Event Type might be deleted)\n";
                $results[$eventTypeId] = [
                    'name' => $service->name,
                    'status' => 'not_found',
                    'error' => 'Event Type not found in Cal.com'
                ];
            } elseif ($statusCode === 401 || $statusCode === 403) {
                echo "   ❌ ACCESS DENIED\n";
                $results[$eventTypeId] = [
                    'name' => $service->name,
                    'status' => 'access_denied',
                    'error' => 'Insufficient permissions'
                ];
            } else {
                echo "   ⚠️  ERROR: {$statusCode} - {$error}\n";
                $results[$eventTypeId] = [
                    'name' => $service->name,
                    'status' => 'error',
                    'error' => $error
                ];
            }
        }

    } catch (Exception $e) {
        echo "   ❌ EXCEPTION: {$e->getMessage()}\n";
        $results[$eventTypeId] = [
            'name' => $service->name,
            'status' => 'exception',
            'error' => $e->getMessage()
        ];
    }

    echo "\n";

    // Rate limiting protection
    usleep(300000); // 300ms between requests
}

// Summary
echo str_repeat("═", 63) . "\n";
echo "📊 Summary Report\n";
echo str_repeat("═", 63) . "\n\n";

$activeCount = 0;
$activeNoSlotsCount = 0;
$errorCount = 0;

echo "✅ ACTIVE Event Types (with available slots):\n";
foreach ($results as $eventId => $result) {
    if ($result['status'] === 'active') {
        echo "   • {$result['name']} (Event: {$eventId}) - {$result['slots']} slots\n";
        $activeCount++;
    }
}
echo "\n";

if ($activeNoSlotsCount = array_filter($results, fn($r) => $r['status'] === 'active_no_slots')) {
    echo "⚠️  ACTIVE but NO SLOTS:\n";
    foreach ($results as $eventId => $result) {
        if ($result['status'] === 'active_no_slots') {
            echo "   • {$result['name']} (Event: {$eventId})\n";
        }
    }
    echo "\n";
}

if ($errors = array_filter($results, fn($r) => in_array($r['status'], ['not_found', 'error', 'exception', 'access_denied']))) {
    echo "❌ ERRORS:\n";
    foreach ($results as $eventId => $result) {
        if (in_array($result['status'], ['not_found', 'error', 'exception', 'access_denied'])) {
            echo "   • {$result['name']} (Event: {$eventId}) - {$result['error']}\n";
            $errorCount++;
        }
    }
    echo "\n";
}

echo str_repeat("─", 63) . "\n";
echo "Total Event Types: " . count($results) . "\n";
echo "✅ Active with slots: {$activeCount}\n";
echo "⚠️  Active but no slots: " . count($activeNoSlotsCount) . "\n";
echo "❌ Errors: {$errorCount}\n";
echo str_repeat("═", 63) . "\n\n";

// Save results to JSON for further analysis
$jsonOutput = [
    'timestamp' => now()->toIso8601String(),
    'total' => count($results),
    'active_count' => $activeCount,
    'error_count' => $errorCount,
    'results' => $results
];

file_put_contents(
    __DIR__ . '/../storage/logs/event_types_status_' . now()->format('Y-m-d_His') . '.json',
    json_encode($jsonOutput, JSON_PRETTY_PRINT)
);

echo "📄 Results saved to: storage/logs/event_types_status_" . now()->format('Y-m-d_His') . ".json\n";
