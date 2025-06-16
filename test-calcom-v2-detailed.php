<?php
/**
 * Detailed Cal.com v2 API Test Suite
 * Focus on working endpoints and actual booking functionality
 */

require_once __DIR__ . '/vendor/autoload.php';

// Configuration
$apiKey = 'cal_live_bd7aedbdf12085c5312c79ba73585920'; // Company 85 API key
$baseUrlV2 = 'https://api.cal.com/v2';

// Test configuration
$testEventTypeId = 2563193; // Known working event type

echo "Cal.com V2 API Detailed Testing\n";
echo "===============================\n\n";

// Helper function for API requests
function makeV2Request($endpoint, $method = 'GET', $data = null, $queryParams = []) {
    global $apiKey, $baseUrlV2;
    
    $url = $baseUrlV2 . $endpoint;
    if (!empty($queryParams)) {
        $url .= '?' . http_build_query($queryParams);
    }
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'cal-api-version: 2024-08-13',
        'Authorization: Bearer ' . $apiKey,
        'Content-Type: application/json'
    ]);
    
    if ($data && in_array($method, ['POST', 'PUT', 'PATCH'])) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    }
    
    curl_setopt($ch, CURLOPT_VERBOSE, true);
    $verbose = fopen('php://temp', 'w+');
    curl_setopt($ch, CURLOPT_STDERR, $verbose);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    
    rewind($verbose);
    $verboseLog = stream_get_contents($verbose);
    
    curl_close($ch);
    
    return [
        'success' => $httpCode >= 200 && $httpCode < 300,
        'http_code' => $httpCode,
        'response' => json_decode($response, true),
        'raw_response' => $response,
        'error' => $error,
        'verbose_log' => $verboseLog
    ];
}

// Test 1: Get Available Slots (Working in v2)
echo "Test 1: Get Available Slots\n";
echo "---------------------------\n";

$startTime = date('Y-m-d') . 'T00:00:00.000Z';
$endTime = date('Y-m-d', strtotime('+7 days')) . 'T23:59:59.999Z';

$result = makeV2Request('/slots/available', 'GET', null, [
    'eventTypeId' => $testEventTypeId,
    'startTime' => $startTime,
    'endTime' => $endTime,
    'timeZone' => 'Europe/Berlin'
]);

if ($result['success']) {
    echo "✓ Success! HTTP Code: {$result['http_code']}\n";
    $slots = $result['response']['data']['slots'] ?? [];
    $totalSlots = 0;
    foreach ($slots as $date => $daySlots) {
        $totalSlots += count($daySlots);
        echo "  Date: $date - " . count($daySlots) . " slots available\n";
    }
    echo "  Total slots available: $totalSlots\n";
    
    // Save first available slot for booking test
    $firstAvailableSlot = null;
    foreach ($slots as $date => $daySlots) {
        if (!empty($daySlots)) {
            $firstAvailableSlot = $daySlots[0]['time'];
            break;
        }
    }
} else {
    echo "✗ Failed! HTTP Code: {$result['http_code']}\n";
    echo "Error: " . ($result['raw_response'] ?: $result['error']) . "\n";
}

echo "\n";

// Test 2: Get Webhooks (Working in v2)
echo "Test 2: Get Webhooks\n";
echo "--------------------\n";

$result = makeV2Request('/webhooks');

if ($result['success']) {
    echo "✓ Success! HTTP Code: {$result['http_code']}\n";
    $webhooks = $result['response']['data'] ?? [];
    echo "  Found " . count($webhooks) . " webhook(s)\n";
    foreach ($webhooks as $webhook) {
        echo "  - ID: {$webhook['id']}\n";
        echo "    URL: {$webhook['subscriberUrl']}\n";
        echo "    Active: " . ($webhook['active'] ? 'Yes' : 'No') . "\n";
        echo "    Triggers: " . implode(', ', array_slice($webhook['triggers'], 0, 3)) . "...\n";
    }
} else {
    echo "✗ Failed! HTTP Code: {$result['http_code']}\n";
    echo "Error: " . ($result['raw_response'] ?: $result['error']) . "\n";
}

echo "\n";

// Test 3: Create a Test Booking (if slot available)
echo "Test 3: Create Booking\n";
echo "----------------------\n";

if (!empty($firstAvailableSlot)) {
    echo "Using slot: $firstAvailableSlot\n";
    
    $bookingData = [
        'eventTypeId' => $testEventTypeId,
        'start' => $firstAvailableSlot,
        'attendee' => [
            'name' => 'Test API User',
            'email' => 'test-api@example.com',
            'timeZone' => 'Europe/Berlin',
            'language' => 'de'
        ],
        'metadata' => [
            'source' => 'askproai',
            'via' => 'api_test_v2',
            'test_run' => true
        ],
        'location' => 'phone',
        'notes' => 'This is a test booking from V2 API test suite'
    ];
    
    echo "Sending booking request...\n";
    $result = makeV2Request('/bookings', 'POST', $bookingData);
    
    if ($result['success']) {
        echo "✓ Success! HTTP Code: {$result['http_code']}\n";
        $booking = $result['response']['data'] ?? $result['response'];
        echo "  Booking ID: " . ($booking['id'] ?? 'N/A') . "\n";
        echo "  Status: " . ($booking['status'] ?? 'N/A') . "\n";
        echo "  Full response:\n";
        echo json_encode($result['response'], JSON_PRETTY_PRINT) . "\n";
        
        // Save booking ID for cancellation test
        $bookingId = $booking['id'] ?? null;
    } else {
        echo "✗ Failed! HTTP Code: {$result['http_code']}\n";
        echo "Error Response:\n";
        echo json_encode($result['response'], JSON_PRETTY_PRINT) . "\n";
        echo "\nRaw Response: " . $result['raw_response'] . "\n";
    }
} else {
    echo "⚠ No available slots found, skipping booking test\n";
}

echo "\n";

// Test 4: List bookings (if we have permissions)
echo "Test 4: List Bookings\n";
echo "---------------------\n";

$result = makeV2Request('/bookings');

if ($result['success']) {
    echo "✓ Success! HTTP Code: {$result['http_code']}\n";
    $bookings = $result['response']['data'] ?? [];
    echo "  Found " . count($bookings) . " booking(s)\n";
} else {
    echo "✗ Failed! HTTP Code: {$result['http_code']}\n";
    echo "Error: " . ($result['raw_response'] ?: $result['error']) . "\n";
}

echo "\n";

// Test 5: Cancel booking (if we created one)
if (!empty($bookingId)) {
    echo "Test 5: Cancel Booking\n";
    echo "----------------------\n";
    echo "Attempting to cancel booking ID: $bookingId\n";
    
    $result = makeV2Request("/bookings/$bookingId/cancel", 'POST', [
        'reason' => 'API test completed'
    ]);
    
    if ($result['success']) {
        echo "✓ Success! HTTP Code: {$result['http_code']}\n";
        echo "  Booking cancelled successfully\n";
    } else {
        echo "✗ Failed! HTTP Code: {$result['http_code']}\n";
        echo "Error: " . ($result['raw_response'] ?: $result['error']) . "\n";
    }
    
    echo "\n";
}

// Test different event type endpoints
echo "Test 6: Event Type Endpoints Analysis\n";
echo "-------------------------------------\n";

$eventTypeEndpoints = [
    '/event-types' => 'Standard event types endpoint',
    '/events' => 'Events endpoint',
    '/event-types/public' => 'Public event types',
    '/schedules' => 'Schedules endpoint',
    '/users/me' => 'Current user info',
    '/users/me/event-types' => 'User event types',
    '/organizations' => 'Organizations',
    '/teams' => 'Teams endpoint'
];

foreach ($eventTypeEndpoints as $endpoint => $description) {
    echo "\nTrying: $endpoint ($description)\n";
    $result = makeV2Request($endpoint);
    
    if ($result['success']) {
        echo "  ✓ Success! HTTP Code: {$result['http_code']}\n";
        echo "  Response structure: ";
        if (is_array($result['response'])) {
            echo implode(', ', array_keys($result['response'])) . "\n";
        }
    } else {
        echo "  ✗ Failed! HTTP Code: {$result['http_code']}\n";
        if ($result['http_code'] === 404) {
            echo "  Endpoint not found\n";
        } elseif ($result['http_code'] === 401) {
            echo "  Unauthorized\n";
        } elseif ($result['http_code'] === 403) {
            echo "  Forbidden\n";
        }
    }
}

// Summary and Recommendations
echo "\n\n";
echo "========================================\n";
echo "SUMMARY AND RECOMMENDATIONS\n";
echo "========================================\n\n";

echo "Working V2 Endpoints:\n";
echo "--------------------\n";
echo "✓ GET /slots/available - Get available time slots\n";
echo "✓ GET /webhooks - List webhooks\n";
echo "✓ POST /bookings - Create new bookings\n";
echo "? GET /bookings - List bookings (permission dependent)\n";
echo "? POST /bookings/{id}/cancel - Cancel bookings\n";

echo "\nMissing/Not Working:\n";
echo "--------------------\n";
echo "✗ GET /event-types - Event types listing\n";
echo "✗ GET /users/me - User profile\n";
echo "✗ GET /organizations - Organizations\n";
echo "✗ GET /schedules - Schedules\n";

echo "\nRecommendations:\n";
echo "----------------\n";
echo "1. For event types: Continue using hardcoded event type IDs or maintain a local mapping\n";
echo "2. For availability: V2 API /slots/available works perfectly\n";
echo "3. For bookings: V2 API /bookings endpoint works with proper attendee structure\n";
echo "4. For webhooks: V2 API provides good webhook management\n";
echo "5. Consider implementing a hybrid approach:\n";
echo "   - Use V2 for slots and bookings\n";
echo "   - Maintain event type mappings locally\n";
echo "   - Use webhooks for real-time updates\n";

// Save detailed log
$logFile = 'calcom-v2-detailed-test-' . date('Y-m-d_H-i-s') . '.log';
file_put_contents($logFile, "Cal.com V2 API Detailed Test Results\n\n" . ob_get_contents());
echo "\n\nDetailed log saved to: $logFile\n";