#!/usr/bin/env php
<?php

require_once __DIR__ . '/../vendor/autoload.php';

use App\Services\CalcomHybridService;
use Illuminate\Support\Facades\Log;

// Bootstrap Laravel
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "\n=====================================\n";
echo "  Cal.com Hybrid Service Test Suite  \n";
echo "=====================================\n";

$service = new CalcomHybridService();

// Test 1: Check service mode
echo "\n[TEST 1] Service Configuration\n";
echo "--------------------------------\n";
$isHybrid = $service->isHybridMode() ? 'ENABLED' : 'DISABLED';
echo "✓ Hybrid Mode: {$isHybrid}\n";

// Test 2: Get Event Type (V1 only)
echo "\n[TEST 2] Get Event Type (V1 Only)\n";
echo "--------------------------------\n";
try {
    $eventTypeId = (int)config('services.calcom.event_type_id', 2026979);
    echo "Testing with Event Type ID: {$eventTypeId}\n";
    
    $eventType = $service->getEventType($eventTypeId);
    
    if (isset($eventType['id'])) {
        echo "✓ Event Type Retrieved: {$eventType['title']} (ID: {$eventType['id']})\n";
        echo "  Duration: {$eventType['length']} minutes\n";
        if (isset($eventType['team'])) {
            echo "  Team: {$eventType['team']['name']} (Slug: {$eventType['team']['slug']})\n";
        }
    } else {
        echo "✗ Failed to retrieve event type\n";
        print_r($eventType);
    }
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
}

// Test 3: Check Availability (V1 only)
echo "\n[TEST 3] Check Availability (V1 Only)\n";
echo "--------------------------------------\n";
try {
    $startDate = date('Y-m-d', strtotime('+1 day'));
    $endDate = date('Y-m-d', strtotime('+7 days'));
    
    echo "Checking availability from {$startDate} to {$endDate}\n";
    
    $availability = $service->checkAvailability([
        'eventTypeId' => config('services.calcom.event_type_id', 2026979),
        'dateFrom' => $startDate,
        'dateTo' => $endDate,
        'timeZone' => 'Europe/Berlin'
    ]);
    
    if (isset($availability['busy'])) {
        echo "✓ Availability Retrieved\n";
        echo "  Busy periods: " . count($availability['busy']) . "\n";
        
        if (isset($availability['slots'])) {
            // V1 format with slots
            $slotCount = 0;
            foreach ($availability['slots'] as $date => $times) {
                $slotCount += count($times);
            }
            echo "  Total slots available: {$slotCount}\n";
        } elseif (isset($availability['dateRanges'])) {
            // V1 format with date ranges
            echo "  Available date ranges: " . count($availability['dateRanges']) . "\n";
            echo "  Working hours configured: " . count($availability['workingHours']) . "\n";
            
            // Check if user is out of office
            if (!empty($availability['datesOutOfOffice'])) {
                echo "  ⚠ User has out-of-office dates configured\n";
            }
        }
    } else {
        echo "✗ Failed to retrieve availability\n";
        print_r($availability);
    }
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
}

// Test 4: Get Bookings (V2 preferred)
echo "\n[TEST 4] Get Bookings (V2 Preferred)\n";
echo "-------------------------------------\n";
try {
    $bookings = $service->getBookings([
        'status' => 'upcoming',
        'limit' => 5
    ]);
    
    if (isset($bookings['data'])) {
        $count = count($bookings['data']);
        echo "✓ Bookings Retrieved: {$count} upcoming bookings\n";
        
        foreach ($bookings['data'] as $booking) {
            echo "  - {$booking['title']} on {$booking['startTime']}\n";
            echo "    Attendee: {$booking['attendees'][0]['name']} ({$booking['attendees'][0]['email']})\n";
        }
    } else {
        echo "✓ No upcoming bookings found (or empty response)\n";
    }
} catch (Exception $e) {
    echo "⚠ Warning: " . $e->getMessage() . "\n";
    echo "  (This is expected if no bookings exist)\n";
}

// Test 5: Create Test Booking (V2 preferred, V1 fallback)
echo "\n[TEST 5] Create Booking (V2 Preferred)\n";
echo "---------------------------------------\n";
echo "⚠ Skipping actual booking creation to avoid creating test data\n";
echo "  To test booking creation, uncomment the code below\n";

/*
try {
    $bookingData = [
        'eventTypeId' => (int)config('services.calcom.event_type_id', 2026979),
        'start' => date('Y-m-d\TH:i:s.000\Z', strtotime('+3 days 14:00')),
        'attendee' => [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'timeZone' => 'Europe/Berlin',
            'language' => 'de'
        ],
        'metadata' => [
            'test' => true,
            'source' => 'hybrid_test'
        ],
        'location' => ['type' => 'address', 'address' => 'Test Location']
    ];
    
    echo "Creating test booking...\n";
    $booking = $service->createBooking($bookingData);
    
    if (isset($booking['uid'])) {
        echo "✓ Booking Created Successfully\n";
        echo "  UID: {$booking['uid']}\n";
        echo "  ID: {$booking['id']}\n";
        
        // Test cancellation
        echo "\nCancelling test booking...\n";
        $cancelled = $service->cancelBooking($booking['uid'], 'Test booking - auto cancelled');
        echo "✓ Booking Cancelled\n";
    } else {
        echo "✗ Failed to create booking\n";
        print_r($booking);
    }
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
}
*/

// Test 6: Get Metrics
echo "\n[TEST 6] Service Metrics\n";
echo "------------------------\n";
$metrics = $service->getMetrics();
echo "✓ API Usage Statistics:\n";
echo "  Total API Calls: {$metrics['total_calls']}\n";
echo "  V1 Calls: {$metrics['v1_calls']} ({$metrics['v1_percentage']}%)\n";
echo "  V2 Calls: {$metrics['v2_calls']} ({$metrics['v2_percentage']}%)\n";
echo "  Errors: {$metrics['errors']}\n";
echo "  Hybrid Mode: " . ($metrics['hybrid_mode'] ? 'ENABLED' : 'DISABLED') . "\n";
echo "  V1 Deprecation Date: {$metrics['deprecation_date']}\n";

if ($metrics['v1_percentage'] > 50) {
    echo "\n⚠ WARNING: High V1 API usage detected!\n";
    echo "  Migration to V2 required before {$metrics['deprecation_date']}\n";
}

// Test 7: Log metrics
echo "\n[TEST 7] Logging Metrics\n";
echo "------------------------\n";
$service->logMetrics();
echo "✓ Metrics logged to Laravel log\n";

// Summary
echo "\n=====================================\n";
echo "  Test Suite Complete                \n";
echo "=====================================\n";
echo "\nSummary:\n";
echo "- Hybrid mode is {$isHybrid}\n";
echo "- V1 API used for: Event Types, Availability\n";
echo "- V2 API used for: Bookings, Cancellations, Rescheduling\n";
echo "- Total API calls made: {$metrics['total_calls']}\n";

if ($metrics['errors'] > 0) {
    echo "\n⚠ {$metrics['errors']} errors encountered during testing\n";
    echo "  Check Laravel logs for details\n";
}

echo "\n";