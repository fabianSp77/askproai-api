<?php
/**
 * Cal.com V2 API Booking Test
 * Test actual booking creation with correct format
 */

require_once __DIR__ . '/vendor/autoload.php';

// Configuration
$apiKey = 'cal_live_bd7aedbdf12085c5312c79ba73585920'; // Company 85 API key
$baseUrlV2 = 'https://api.cal.com/v2';
$testEventTypeId = 2563193;

echo "Cal.com V2 API Booking Test\n";
echo "===========================\n\n";

// Helper function
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
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    return [
        'success' => $httpCode >= 200 && $httpCode < 300,
        'http_code' => $httpCode,
        'response' => json_decode($response, true),
        'raw_response' => $response,
        'error' => $error
    ];
}

// Step 1: Get available slot
echo "Step 1: Getting available slots...\n";
$startTime = date('Y-m-d', strtotime('+1 day')) . 'T00:00:00.000Z';
$endTime = date('Y-m-d', strtotime('+2 days')) . 'T23:59:59.999Z';

$result = makeV2Request('/slots/available', 'GET', null, [
    'eventTypeId' => $testEventTypeId,
    'startTime' => $startTime,
    'endTime' => $endTime,
    'timeZone' => 'Europe/Berlin'
]);

$availableSlot = null;
if ($result['success']) {
    $slots = $result['response']['data']['slots'] ?? [];
    foreach ($slots as $date => $daySlots) {
        if (!empty($daySlots)) {
            $availableSlot = $daySlots[0]['time'];
            echo "✓ Found available slot: $availableSlot\n\n";
            break;
        }
    }
}

if (!$availableSlot) {
    die("No available slots found!\n");
}

// Step 2: Test different booking formats
echo "Step 2: Testing booking formats...\n\n";

// Format 1: Without notes field
echo "Test 1: Booking without notes field\n";
$bookingData1 = [
    'eventTypeId' => $testEventTypeId,
    'start' => $availableSlot,
    'attendee' => [
        'name' => 'Test User V2',
        'email' => 'test-v2@example.com',
        'timeZone' => 'Europe/Berlin',
        'language' => 'de'
    ],
    'metadata' => [
        'source' => 'askproai',
        'via' => 'api_test_v2'
    ],
    'location' => 'phone'
];

echo "Request data:\n" . json_encode($bookingData1, JSON_PRETTY_PRINT) . "\n\n";
$result1 = makeV2Request('/bookings', 'POST', $bookingData1);

if ($result1['success']) {
    echo "✓ SUCCESS! Booking created\n";
    echo "Response:\n" . json_encode($result1['response'], JSON_PRETTY_PRINT) . "\n";
    $bookingId = $result1['response']['data']['id'] ?? null;
} else {
    echo "✗ FAILED! HTTP Code: {$result1['http_code']}\n";
    echo "Error: " . $result1['raw_response'] . "\n";
}

echo "\n" . str_repeat('-', 50) . "\n\n";

// Format 2: With notes in attendee object
echo "Test 2: Booking with notes in attendee object\n";
$bookingData2 = [
    'eventTypeId' => $testEventTypeId,
    'start' => $availableSlot,
    'attendee' => [
        'name' => 'Test User V2 with Notes',
        'email' => 'test-v2-notes@example.com',
        'timeZone' => 'Europe/Berlin',
        'language' => 'de',
        'notes' => 'This is a test booking with notes in attendee object'
    ],
    'metadata' => [
        'source' => 'askproai',
        'via' => 'api_test_v2',
        'phone' => '+49123456789'
    ],
    'location' => 'phone'
];

echo "Request data:\n" . json_encode($bookingData2, JSON_PRETTY_PRINT) . "\n\n";
$result2 = makeV2Request('/bookings', 'POST', $bookingData2);

if ($result2['success']) {
    echo "✓ SUCCESS! Booking created\n";
    echo "Response:\n" . json_encode($result2['response'], JSON_PRETTY_PRINT) . "\n";
} else {
    echo "✗ FAILED! HTTP Code: {$result2['http_code']}\n";
    echo "Error: " . $result2['raw_response'] . "\n";
}

echo "\n" . str_repeat('-', 50) . "\n\n";

// Format 3: With description field
echo "Test 3: Booking with description field\n";
$bookingData3 = [
    'eventTypeId' => $testEventTypeId,
    'start' => $availableSlot,
    'attendee' => [
        'name' => 'Test User V2 Description',
        'email' => 'test-v2-desc@example.com',
        'timeZone' => 'Europe/Berlin',
        'language' => 'de'
    ],
    'description' => 'This is a test booking with description field',
    'metadata' => [
        'source' => 'askproai',
        'via' => 'api_test_v2'
    ],
    'location' => 'phone'
];

echo "Request data:\n" . json_encode($bookingData3, JSON_PRETTY_PRINT) . "\n\n";
$result3 = makeV2Request('/bookings', 'POST', $bookingData3);

if ($result3['success']) {
    echo "✓ SUCCESS! Booking created\n";
    echo "Response:\n" . json_encode($result3['response'], JSON_PRETTY_PRINT) . "\n";
} else {
    echo "✗ FAILED! HTTP Code: {$result3['http_code']}\n";
    echo "Error: " . $result3['raw_response'] . "\n";
}

// Step 3: Cancel test bookings if created
if (!empty($bookingId)) {
    echo "\n\nStep 3: Cleaning up test booking...\n";
    $cancelResult = makeV2Request("/bookings/$bookingId/cancel", 'POST', [
        'reason' => 'API test completed'
    ]);
    
    if ($cancelResult['success']) {
        echo "✓ Test booking cancelled successfully\n";
    } else {
        echo "✗ Failed to cancel booking\n";
    }
}

// Summary
echo "\n\n";
echo "========================================\n";
echo "BOOKING FORMAT SUMMARY\n";
echo "========================================\n\n";

echo "V2 API Booking Requirements:\n";
echo "- eventTypeId: Required (integer)\n";
echo "- start: Required (ISO 8601 timestamp with timezone)\n";
echo "- attendee: Required object with:\n";
echo "  - name: Required\n";
echo "  - email: Required\n";
echo "  - timeZone: Required\n";
echo "  - language: Optional\n";
echo "  - notes: Might work here (test needed)\n";
echo "- metadata: Optional object for custom data\n";
echo "- location: Optional (e.g., 'phone')\n";
echo "- description: Might work as alternative to notes\n";
echo "\nNOTE: 'notes' field at root level is NOT allowed in V2\n";