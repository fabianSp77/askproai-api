#!/usr/bin/env php
<?php

require_once __DIR__ . '/vendor/autoload.php';

use Illuminate\Support\Facades\Http;
use App\Models\Company;
use App\Services\CalcomV2Service;

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$company = Company::first();
if (!$company || !$company->calcom_api_key) {
    echo "❌ No company or Cal.com API key found\n";
    exit(1);
}

$apiKey = decrypt($company->calcom_api_key);
$teamSlug = $company->calcom_team_slug;

echo "Testing Cal.com API Connection\n";
echo "==============================\n";
echo "Company: {$company->name}\n";
echo "Team Slug: {$teamSlug}\n";
echo "API Key: " . substr($apiKey, 0, 10) . "...\n\n";

// Test using CalcomV2Service
$calcom = new CalcomV2Service($apiKey);

// Test 1: Get Me (User Info)
echo "Test 1: Get User Info\n";
echo "--------------------\n";

$result = $calcom->getMe();
if ($result['success']) {
    echo "✅ Success! User details:\n";
    $user = $result['data'];
    echo "  ID: " . ($user['id'] ?? 'N/A') . "\n";
    echo "  Username: " . ($user['username'] ?? 'N/A') . "\n";
    echo "  Email: " . ($user['email'] ?? 'N/A') . "\n";
    echo "  Name: " . ($user['name'] ?? 'N/A') . "\n";
} else {
    echo "❌ Failed: " . ($result['error'] ?? 'Unknown error') . "\n";
}

echo "\n";

// Test 2: Get Event Types
echo "Test 2: Get Event Types\n";
echo "-----------------------\n";

$result = $calcom->getEventTypes();
if ($result['success']) {
    $eventTypes = $result['data'];
    echo "✅ Success! Found " . count($eventTypes) . " event types\n";
    
    foreach (array_slice($eventTypes, 0, 5) as $eventType) {
        echo "  - {$eventType['title']} (ID: {$eventType['id']})\n";
        echo "    Slug: {$eventType['slug']}\n";
        echo "    Length: {$eventType['length']} minutes\n";
        echo "    Active: " . ($eventType['hidden'] ? 'No' : 'Yes') . "\n";
    }
} else {
    echo "❌ Failed: " . ($result['error'] ?? 'Unknown error') . "\n";
}

echo "\n";

// Test 3: Get Bookings
echo "Test 3: Get Recent Bookings\n";
echo "---------------------------\n";

$result = $calcom->getBookings([
    'dateFrom' => now()->subDays(30)->format('Y-m-d'),
    'dateTo' => now()->format('Y-m-d')
]);

if ($result['success']) {
    $bookings = $result['data'];
    echo "✅ Success! Found " . count($bookings) . " bookings in last 30 days\n";
    
    foreach (array_slice($bookings, 0, 5) as $booking) {
        echo "  - Booking ID: {$booking['id']}\n";
        echo "    Title: {$booking['title']}\n";
        echo "    Start: {$booking['startTime']}\n";
        echo "    Status: {$booking['status']}\n";
        if (isset($booking['attendees'][0])) {
            echo "    Attendee: {$booking['attendees'][0]['name']} ({$booking['attendees'][0]['email']})\n";
        }
    }
} else {
    echo "❌ Failed: " . ($result['error'] ?? 'Unknown error') . "\n";
}

echo "\n";

// Test 4: Get Availability
echo "Test 4: Check Availability\n";
echo "--------------------------\n";

// Get first event type ID
$eventTypeId = null;
if (isset($eventTypes[0]['id'])) {
    $eventTypeId = $eventTypes[0]['id'];
    
    $result = $calcom->getAvailability([
        'eventTypeId' => $eventTypeId,
        'dateFrom' => now()->format('Y-m-d'),
        'dateTo' => now()->addDays(7)->format('Y-m-d')
    ]);
    
    if ($result['success']) {
        $slots = $result['data']['slots'] ?? [];
        echo "✅ Success! Found " . count($slots) . " available slots\n";
        
        foreach (array_slice($slots, 0, 5) as $date => $daySlots) {
            echo "  Date: {$date}\n";
            foreach (array_slice($daySlots, 0, 3) as $slot) {
                echo "    - {$slot['time']}\n";
            }
        }
    } else {
        echo "❌ Failed: " . ($result['error'] ?? 'Unknown error') . "\n";
    }
} else {
    echo "⚠️ No event types available to check availability\n";
}

echo "\n";
echo "Test complete!\n";