<?php
/**
 * Check why services are missing
 */

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Service;
use App\Services\CalcomService;
use Illuminate\Support\Facades\Http;

$companyId = '7fc13e06-ba89-4c54-a2d9-ecabe50abb7a'; // Friseur 1

echo "=== SERVICES STATUS CHECK ===\n\n";

// 1. Check database
echo "1. Checking database...\n";
$servicesTotal = Service::where('company_id', $companyId)->count();
$servicesActive = Service::where('company_id', $companyId)->where('is_active', true)->count();
$servicesInactive = Service::where('company_id', $companyId)->where('is_active', false)->count();

echo "   Total services: {$servicesTotal}\n";
echo "   Active: {$servicesActive}\n";
echo "   Inactive: {$servicesInactive}\n\n";

if ($servicesTotal > 0) {
    echo "   Sample services:\n";
    $samples = Service::where('company_id', $companyId)->take(5)->get();
    foreach ($samples as $service) {
        echo "   - {$service->name} ({$service->duration_minutes} min) - Active: " . ($service->is_active ? 'YES' : 'NO') . "\n";
        echo "     Cal.com Event Type ID: " . ($service->calcom_event_type_id ?? 'NULL') . "\n";
    }
    echo "\n";
}

// 2. Check Cal.com connectivity
echo "2. Checking Cal.com API...\n";

$calcomApiKey = config('calcom.api_key');
$calcomBaseUrl = config('calcom.base_url');

if (!$calcomApiKey) {
    echo "   ❌ Cal.com API key not configured\n\n";
    exit(1);
}

echo "   Cal.com URL: {$calcomBaseUrl}\n";
echo "   API Key configured: YES\n\n";

// 3. Fetch event types from Cal.com
echo "3. Fetching event types from Cal.com...\n";

try {
    $response = Http::withHeaders([
        'cal-api-version' => '2024-08-13',
        'Authorization' => 'Bearer ' . $calcomApiKey,
    ])->get($calcomBaseUrl . '/event-types', [
        'take' => 50
    ]);

    if ($response->successful()) {
        $data = $response->json();
        $eventTypes = $data['data'] ?? [];

        echo "   ✅ Successfully retrieved " . count($eventTypes) . " event types\n\n";

        if (count($eventTypes) > 0) {
            echo "   Sample event types:\n";
            foreach (array_slice($eventTypes, 0, 10) as $et) {
                echo "   - ID: {$et['id']}\n";
                echo "     Title: {$et['title']}\n";
                echo "     Length: " . ($et['lengthInMinutes'] ?? $et['length'] ?? 'N/A') . " min\n";
                echo "     Slug: {$et['slug']}\n";
                echo "\n";
            }
        } else {
            echo "   ⚠️  No event types found in Cal.com\n\n";
        }
    } else {
        echo "   ❌ Cal.com API error: " . $response->status() . "\n";
        echo "   Response: " . $response->body() . "\n\n";
    }
} catch (\Exception $e) {
    echo "   ❌ Exception: " . $e->getMessage() . "\n\n";
}

// 4. Check sync command
echo "4. Checking sync command...\n";

try {
    $exitCode = \Artisan::call('calcom:sync-services', [
        '--company' => $companyId
    ]);

    $output = \Artisan::output();
    echo "   Exit code: {$exitCode}\n";
    echo "   Output:\n";
    echo $output;
    echo "\n";
} catch (\Exception $e) {
    echo "   ❌ Error running sync: " . $e->getMessage() . "\n\n";
}

// 5. Check database again after sync
echo "5. Checking database after sync...\n";
$servicesAfter = Service::where('company_id', $companyId)->where('is_active', true)->count();
echo "   Active services: {$servicesAfter}\n\n";

if ($servicesAfter > 0) {
    echo "   ✅ Services synced successfully!\n";
    $samples = Service::where('company_id', $companyId)->where('is_active', true)->take(5)->get();
    foreach ($samples as $service) {
        echo "   - {$service->name} ({$service->duration_minutes} min)\n";
    }
} else {
    echo "   ❌ Services still missing after sync\n";
}

echo "\n=== DIAGNOSIS COMPLETE ===\n";
