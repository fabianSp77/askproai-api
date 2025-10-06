<?php

/**
 * SuperClaude Command: /sc:calcom-verify
 * Comprehensive Cal.com synchronization verification
 */

use App\Models\Service;
use App\Services\CalcomService;
use Illuminate\Support\Facades\Http;

echo "\n╔═══════════════════════════════════════════════════════════════╗\n";
echo "║           Cal.com Synchronization Verification                 ║\n";
echo "║                  " . date('Y-m-d H:i:s') . "                        ║\n";
echo "╚═══════════════════════════════════════════════════════════════╝\n";

// Step 1: Configuration Check
echo "\n📋 Configuration Status\n";
echo "──────────────────────\n";

$apiKey = config('services.calcom.api_key');
$baseUrl = config('services.calcom.base_url');
$teamSlug = config('services.calcom.team_slug');
$webhookSecret = config('services.calcom.webhook_secret');

echo "  API Key: " . ($apiKey ? '✅ Configured (' . substr($apiKey, 0, 15) . '...)' : '❌ Missing') . "\n";
echo "  Base URL: " . ($baseUrl ? '✅ ' . $baseUrl : '❌ Missing') . "\n";
echo "  Team Slug: " . ($teamSlug ? '✅ ' . $teamSlug : '⚠️ Optional - Not set') . "\n";
echo "  Webhook Secret: " . ($webhookSecret ? '✅ Configured' : '⚠️ Optional - Not set') . "\n";

// Step 2: API Connectivity Test
echo "\n🌐 API Connectivity\n";
echo "──────────────────\n";

if ($apiKey && $baseUrl) {
    try {
        $response = Http::withHeaders(['Accept' => 'application/json'])
            ->get($baseUrl . '/event-types?apiKey=' . $apiKey);

        if ($response->successful()) {
            $data = $response->json();
            $eventTypeCount = isset($data['event_types']) ? count($data['event_types']) : 0;
            echo "  Status: ✅ Connected\n";
            echo "  Response: HTTP " . $response->status() . "\n";
            echo "  Event Types in Cal.com: $eventTypeCount\n";
        } else {
            echo "  Status: ❌ Failed\n";
            echo "  Response: HTTP " . $response->status() . "\n";
            echo "  Error: " . substr($response->body(), 0, 100) . "\n";
        }
    } catch (Exception $e) {
        echo "  Status: ❌ Exception\n";
        echo "  Error: " . $e->getMessage() . "\n";
    }
} else {
    echo "  Status: ⏭️ Skipped (missing configuration)\n";
}

// Step 3: Database Analysis
echo "\n💾 Database Analysis\n";
echo "──────────────────\n";

$totalServices = Service::count();
$syncedServices = Service::whereNotNull('calcom_event_type_id')->count();
$activeServices = Service::where('is_active', true)->count();
$onlineServices = Service::where('is_online', true)->count();

echo "  Total Services: $totalServices\n";
echo "  Active Services: $activeServices\n";
echo "  Online Bookable: $onlineServices\n";
echo "  Synced with Cal.com: $syncedServices\n";

// Check for fake IDs
$fakeIds = Service::whereNotNull('calcom_event_type_id')
    ->where(function ($query) {
        $query->whereRaw('calcom_event_type_id REGEXP "^[0-9]{1,3}$"')
            ->orWhere('calcom_event_type_id', 'LIKE', 'cal_%');
    })
    ->count();

if ($fakeIds > 0) {
    echo "  ⚠️ WARNING: Found $fakeIds services with fake Event IDs!\n";
}

// Step 4: Sync Status Details
echo "\n📊 Sync Status Details\n";
echo "──────────────────────\n";

$services = Service::whereNotNull('calcom_event_type_id')
    ->with('company:id,name')
    ->get();

if ($services->isEmpty()) {
    echo "  No services currently synced with Cal.com\n";
} else {
    foreach ($services as $service) {
        $isFake = false;
        if (is_numeric($service->calcom_event_type_id) && $service->calcom_event_type_id < 100) {
            $isFake = true;
        } elseif (str_starts_with($service->calcom_event_type_id, 'cal_')) {
            $isFake = true;
        }

        $status = $isFake ? '❌ FAKE' : '✅ REAL';
        echo "  [{$status}] {$service->name} (ID: {$service->calcom_event_type_id})\n";
        echo "        Company: " . ($service->company ? $service->company->name : 'N/A') . "\n";
    }
}

// Step 5: Real Cal.com Event Types
if ($apiKey && $baseUrl) {
    echo "\n📅 Real Cal.com Event Types\n";
    echo "────────────────────────────\n";

    try {
        $response = Http::withHeaders(['Accept' => 'application/json'])
            ->get($baseUrl . '/event-types?apiKey=' . $apiKey);

        if ($response->successful()) {
            $data = $response->json();
            if (isset($data['event_types']) && count($data['event_types']) > 0) {
                foreach (array_slice($data['event_types'], 0, 5) as $eventType) {
                    echo "  ID: {$eventType['id']} | {$eventType['title']}\n";
                    echo "      Duration: {$eventType['length']} min | Slug: {$eventType['slug']}\n";
                }

                if (count($data['event_types']) > 5) {
                    $remaining = count($data['event_types']) - 5;
                    echo "  ... and $remaining more event types\n";
                }
            } else {
                echo "  No event types found in Cal.com\n";
            }
        }
    } catch (Exception $e) {
        echo "  Error fetching event types: " . $e->getMessage() . "\n";
    }
}

// Step 6: Recommendations
echo "\n🎯 Recommendations\n";
echo "──────────────────\n";

$recommendations = [];

if (!$apiKey) {
    $recommendations[] = "Configure Cal.com API key in .env file";
}

if ($fakeIds > 0) {
    $recommendations[] = "Run /sc:calcom-clean to remove fake Event IDs";
}

if ($syncedServices == 0 && $totalServices > 0) {
    $recommendations[] = "Run /sc:calcom-integrate to sync services with Cal.com";
}

if ($apiKey && $syncedServices < $activeServices) {
    $unsyncedCount = $activeServices - $syncedServices;
    $recommendations[] = "Sync remaining $unsyncedCount active services with Cal.com";
}

if (empty($recommendations)) {
    echo "  ✅ System appears properly configured!\n";
} else {
    foreach ($recommendations as $i => $rec) {
        echo "  " . ($i + 1) . ". $rec\n";
    }
}

echo "\n╔═══════════════════════════════════════════════════════════════╗\n";
echo "║                    Verification Complete                       ║\n";
echo "╚═══════════════════════════════════════════════════════════════╝\n\n";