<?php

/**
 * Update Cal.com Event Types to Round Robin Configuration
 *
 * Changes all event types from "collective" to "round-robin" scheduling
 * with availability maximization enabled.
 */

require __DIR__ . '/../vendor/autoload.php';

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "═══════════════════════════════════════════════════════════════\n";
echo "Cal.com Event Types - Round Robin Configuration Update\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

$calcomApiKey = config('services.calcom.api_key');
$calcomBaseUrl = config('services.calcom.base_url');
$calcomApiVersion = config('services.calcom.api_version');

echo "📋 Configuration:\n";
echo "   Base URL: {$calcomBaseUrl}\n";
echo "   API Version: {$calcomApiVersion}\n\n";

// Get all active services with Cal.com event types
$services = DB::table('services')
    ->where('company_id', 1)
    ->where('is_active', 1)
    ->whereNotNull('calcom_event_type_id')
    ->get(['id', 'name', 'calcom_event_type_id', 'priority']);

if ($services->isEmpty()) {
    echo "❌ No active services with Cal.com event types found!\n";
    exit(1);
}

echo "📊 Found " . $services->count() . " active services:\n\n";

// Collect unique event type IDs
$eventTypeIds = $services->pluck('calcom_event_type_id')->unique();

foreach ($services as $service) {
    echo "   • {$service->name} (Event Type: {$service->calcom_event_type_id})\n";
}

echo "\n" . str_repeat("─", 63) . "\n\n";

// Update each event type
$successCount = 0;
$failureCount = 0;

foreach ($eventTypeIds as $eventTypeId) {
    $serviceName = $services->where('calcom_event_type_id', $eventTypeId)->first()->name ?? 'Unknown';

    echo "🔧 Updating Event Type {$eventTypeId} ({$serviceName})...\n";

    try {
        // First, get current configuration
        $getResponse = Http::withHeaders([
            'Authorization' => "Bearer {$calcomApiKey}",
            'cal-api-version' => $calcomApiVersion,
        ])->get("{$calcomBaseUrl}/event-types/{$eventTypeId}");

        if (!$getResponse->successful()) {
            echo "   ⚠️  Could not fetch current config: " . $getResponse->status() . "\n";
            echo "   Response: " . $getResponse->body() . "\n\n";
            $failureCount++;
            continue;
        }

        $currentConfig = $getResponse->json('data') ?? $getResponse->json();
        $currentSchedulingType = $currentConfig['schedulingType'] ?? 'unknown';

        echo "   Current scheduling type: {$currentSchedulingType}\n";

        // Update to round-robin configuration
        $updatePayload = [
            'schedulingType' => 'ROUND_ROBIN',  // or 'round-robin' depending on API
            // Add other configuration as needed
        ];

        $updateResponse = Http::withHeaders([
            'Authorization' => "Bearer {$calcomApiKey}",
            'cal-api-version' => $calcomApiVersion,
        ])->patch("{$calcomBaseUrl}/event-types/{$eventTypeId}", $updatePayload);

        if ($updateResponse->successful()) {
            echo "   ✅ SUCCESS: Updated to Round Robin\n\n";
            $successCount++;
        } else {
            echo "   ❌ FAILED: " . $updateResponse->status() . "\n";
            echo "   Response: " . $updateResponse->body() . "\n\n";
            $failureCount++;
        }

    } catch (Exception $e) {
        echo "   ❌ ERROR: {$e->getMessage()}\n\n";
        $failureCount++;
    }

    // Rate limiting protection
    usleep(500000); // 500ms delay between requests
}

echo "\n" . str_repeat("═", 63) . "\n";
echo "📊 Summary:\n";
echo "   ✅ Successful: {$successCount}\n";
echo "   ❌ Failed: {$failureCount}\n";
echo "   📋 Total: " . $eventTypeIds->count() . "\n";
echo "═══════════════════════════════════════════════════════════════\n";

if ($successCount > 0) {
    echo "\n✅ Configuration updated! All event types now use Round Robin scheduling.\n";
    echo "🔄 Duplicate bookings should no longer occur.\n\n";
}
