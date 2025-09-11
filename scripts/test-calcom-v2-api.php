#!/usr/bin/env php
<?php

require_once __DIR__ . '/../vendor/autoload.php';

// Bootstrap Laravel
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Services\CalcomV2Service;
use Illuminate\Support\Facades\Log;

echo "\n";
echo "════════════════════════════════════════════════════════════════\n";
echo "  Cal.com V2 API Connection Test\n";
echo "════════════════════════════════════════════════════════════════\n\n";

$apiKey = config('services.calcom.api_key');
$baseUrl = config('services.calcom.v2_base_url');

echo "📋 Configuration:\n";
echo "  • Base URL: {$baseUrl}\n";
echo "  • API Key: " . (empty($apiKey) ? '❌ NOT SET' : '✅ Set (' . substr($apiKey, 0, 10) . '...)') . "\n";
echo "  • API Version: " . config('services.calcom.v2_api_version') . "\n\n";

if (empty($apiKey)) {
    echo "❌ Error: API key not configured. Please set CALCOM_API_KEY in .env\n\n";
    exit(1);
}

$service = new CalcomV2Service();

// Test 1: Get Bookings (basic test)
echo "🔍 Test 1: Fetching recent bookings...\n";
try {
    $bookings = $service->getBookings(['limit' => 5]);
    echo "  ✅ Success! Found " . count($bookings) . " bookings\n";
    
    if (!empty($bookings)) {
        echo "  First booking:\n";
        $first = $bookings[0];
        echo "    • ID: " . ($first['id'] ?? 'N/A') . "\n";
        echo "    • UID: " . ($first['uid'] ?? 'N/A') . "\n";
        echo "    • Status: " . ($first['status'] ?? 'N/A') . "\n";
    }
} catch (\Exception $e) {
    echo "  ❌ Failed: " . $e->getMessage() . "\n";
}

echo "\n";

// Test 2: Try different endpoint paths for event types
echo "🔍 Test 2: Testing event type endpoints...\n";
$endpoints = [
    '/event-types',
    '/eventTypes',
    '/event_types',
    '/users/me/event-types',
];

foreach ($endpoints as $endpoint) {
    echo "  Trying: {$endpoint}... ";
    try {
        $response = makeDirectApiCall($baseUrl . $endpoint, $apiKey);
        if ($response !== false) {
            echo "✅ Works!\n";
            $data = json_decode($response, true);
            if (isset($data['data'])) {
                echo "    Found " . count($data['data']) . " event types\n";
            }
            break;
        } else {
            echo "❌ Failed\n";
        }
    } catch (\Exception $e) {
        echo "❌ " . $e->getMessage() . "\n";
    }
}

echo "\n";

// Test 3: Try to get organization info
echo "🔍 Test 3: Testing organization endpoint...\n";
try {
    $orgId = config('services.calcom.organization_id');
    if ($orgId) {
        echo "  Organization ID: {$orgId}\n";
        $org = $service->getOrganization();
        echo "  ✅ Success! Organization: " . ($org['name'] ?? 'Unknown') . "\n";
    } else {
        echo "  ⚠️  No organization ID configured\n";
    }
} catch (\Exception $e) {
    echo "  ❌ Failed: " . $e->getMessage() . "\n";
}

echo "\n";

// Test 4: Check available endpoints
echo "🔍 Test 4: Discovering available endpoints...\n";
$testEndpoints = [
    '/me' => 'User Profile',
    '/schedules' => 'Schedules',
    '/availability' => 'Availability',
    '/bookings' => 'Bookings',
    '/event-types' => 'Event Types',
    '/teams' => 'Teams',
    '/slots' => 'Available Slots',
    '/webhooks' => 'Webhooks',
];

foreach ($testEndpoints as $endpoint => $name) {
    echo "  Testing {$name} ({$endpoint}): ";
    try {
        $response = makeDirectApiCall($baseUrl . $endpoint, $apiKey);
        if ($response !== false) {
            $data = json_decode($response, true);
            if (isset($data['status']) && $data['status'] === 'success') {
                echo "✅ Available\n";
            } elseif (isset($data['error'])) {
                echo "⚠️  Error: " . $data['error']['message'] . "\n";
            } else {
                echo "✅ Response received\n";
            }
        } else {
            echo "❌ Not accessible\n";
        }
    } catch (\Exception $e) {
        echo "❌ Failed\n";
    }
}

echo "\n";
echo "════════════════════════════════════════════════════════════════\n";
echo "  Test Complete\n";
echo "════════════════════════════════════════════════════════════════\n\n";

function makeDirectApiCall($url, $apiKey) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $apiKey,
        'cal-api-version: 2024-08-13',
        'Content-Type: application/json',
        'Accept: application/json'
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode >= 200 && $httpCode < 300) {
        return $response;
    }
    
    return false;
}