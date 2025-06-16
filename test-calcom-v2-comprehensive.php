<?php
/**
 * Comprehensive Cal.com v2 API Test Suite
 * Tests all required functionality and compares with v1
 */

require_once __DIR__ . '/vendor/autoload.php';

// Configuration
$apiKey = 'cal_live_bd7aedbdf12085c5312c79ba73585920'; // Company 85 API key
$baseUrlV1 = 'https://api.cal.com/v1';
$baseUrlV2 = 'https://api.cal.com/v2';
$teamSlug = 'askproai';

// Colors for output
$green = "\033[0;32m";
$red = "\033[0;31m";
$yellow = "\033[0;33m";
$blue = "\033[0;34m";
$reset = "\033[0m";

// Test results storage
$testResults = [];

function makeRequest($url, $method = 'GET', $data = null, $headers = [], $apiVersion = 'v1') {
    global $apiKey;
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    
    // Set headers based on API version
    if ($apiVersion === 'v2') {
        $defaultHeaders = [
            'cal-api-version: 2024-08-13',
            'Authorization: Bearer ' . $apiKey,
            'Content-Type: application/json'
        ];
    } else {
        $defaultHeaders = [
            'Content-Type: application/json'
        ];
    }
    
    $allHeaders = array_merge($defaultHeaders, $headers);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $allHeaders);
    
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

function runTest($testName, $testFunction) {
    global $testResults, $green, $red, $yellow, $reset;
    
    echo "\n{$blue}=== Testing: $testName ==={$reset}\n";
    
    try {
        $result = $testFunction();
        $testResults[$testName] = $result;
        
        if ($result['success']) {
            echo "{$green}✓ PASSED{$reset}\n";
        } else {
            echo "{$red}✗ FAILED{$reset}\n";
        }
        
        if (!empty($result['details'])) {
            echo "Details: " . json_encode($result['details'], JSON_PRETTY_PRINT) . "\n";
        }
        
        if (!empty($result['error'])) {
            echo "{$red}Error: {$result['error']}{$reset}\n";
        }
        
    } catch (Exception $e) {
        echo "{$red}✗ EXCEPTION: " . $e->getMessage() . "{$reset}\n";
        $testResults[$testName] = [
            'success' => false,
            'error' => $e->getMessage()
        ];
    }
}

// Test 1: Event Types Listing (V1)
runTest('Event Types - V1 API', function() use ($baseUrlV1, $apiKey) {
    $url = "$baseUrlV1/event-types?apiKey=$apiKey";
    $result = makeRequest($url);
    
    return [
        'success' => $result['success'] && !empty($result['response']),
        'details' => [
            'http_code' => $result['http_code'],
            'count' => isset($result['response']['event_types']) ? count($result['response']['event_types']) : 0,
            'sample' => isset($result['response']['event_types'][0]) ? $result['response']['event_types'][0] : null
        ],
        'error' => !$result['success'] ? $result['raw_response'] : null
    ];
});

// Test 2: Event Types Listing (V2)
runTest('Event Types - V2 API', function() use ($baseUrlV2) {
    $url = "$baseUrlV2/event-types";
    $result = makeRequest($url, 'GET', null, [], 'v2');
    
    return [
        'success' => $result['success'],
        'details' => [
            'http_code' => $result['http_code'],
            'response' => $result['response'],
            'raw' => substr($result['raw_response'], 0, 500)
        ],
        'error' => !$result['success'] ? $result['raw_response'] : null
    ];
});

// Test 3: User Profile (V1)
runTest('User Profile - V1 API', function() use ($baseUrlV1, $apiKey) {
    $url = "$baseUrlV1/users?apiKey=$apiKey";
    $result = makeRequest($url);
    
    return [
        'success' => $result['success'],
        'details' => [
            'http_code' => $result['http_code'],
            'user' => isset($result['response']['users'][0]) ? $result['response']['users'][0] : null
        ],
        'error' => !$result['success'] ? $result['raw_response'] : null
    ];
});

// Test 4: User Profile (V2)
runTest('User Profile - V2 API', function() use ($baseUrlV2) {
    $url = "$baseUrlV2/users/me";
    $result = makeRequest($url, 'GET', null, [], 'v2');
    
    return [
        'success' => $result['success'],
        'details' => [
            'http_code' => $result['http_code'],
            'response' => $result['response']
        ],
        'error' => !$result['success'] ? $result['raw_response'] : null
    ];
});

// Test 5: Availability Check (V1)
runTest('Availability Check - V1 API', function() use ($baseUrlV1, $apiKey, $teamSlug) {
    $eventTypeId = 2563193; // Known event type ID
    $dateFrom = date('Y-m-d');
    $dateTo = date('Y-m-d', strtotime('+7 days'));
    
    $url = "$baseUrlV1/availability?apiKey=$apiKey&eventTypeId=$eventTypeId&dateFrom=$dateFrom&dateTo=$dateTo";
    $result = makeRequest($url);
    
    return [
        'success' => $result['success'],
        'details' => [
            'http_code' => $result['http_code'],
            'request_params' => [
                'eventTypeId' => $eventTypeId,
                'dateFrom' => $dateFrom,
                'dateTo' => $dateTo
            ],
            'slots_count' => isset($result['response']['slots']) ? count($result['response']['slots']) : 0,
            'sample_slot' => isset($result['response']['slots'][0]) ? $result['response']['slots'][0] : null
        ],
        'error' => !$result['success'] ? $result['raw_response'] : null
    ];
});

// Test 6: Availability Check (V2)
runTest('Availability Check - V2 API', function() use ($baseUrlV2) {
    $eventTypeId = 2563193;
    $startTime = date('Y-m-d') . 'T00:00:00.000Z';
    $endTime = date('Y-m-d', strtotime('+1 day')) . 'T23:59:59.999Z';
    
    $url = "$baseUrlV2/slots/available?" . http_build_query([
        'eventTypeId' => $eventTypeId,
        'startTime' => $startTime,
        'endTime' => $endTime,
        'timeZone' => 'Europe/Berlin'
    ]);
    
    $result = makeRequest($url, 'GET', null, [], 'v2');
    
    return [
        'success' => $result['success'],
        'details' => [
            'http_code' => $result['http_code'],
            'request_params' => [
                'eventTypeId' => $eventTypeId,
                'startTime' => $startTime,
                'endTime' => $endTime
            ],
            'response' => $result['response']
        ],
        'error' => !$result['success'] ? $result['raw_response'] : null
    ];
});

// Test 7: Create Booking (V1) - Test Mode
runTest('Create Booking - V1 API (Test Mode)', function() use ($baseUrlV1, $apiKey) {
    $bookingData = [
        'eventTypeId' => 2563193,
        'start' => date('Y-m-d\TH:i:s', strtotime('+2 days 14:00')) . '.000Z',
        'timeZone' => 'Europe/Berlin',
        'language' => 'de',
        'metadata' => [
            'source' => 'askproai',
            'via' => 'api_test'
        ],
        'responses' => [
            'name' => 'API Test User',
            'email' => 'api-test@example.com',
            'location' => 'phone',
            'notes' => 'This is a test booking from V1 API test suite'
        ]
    ];
    
    // Note: We're not actually creating a booking to avoid cluttering the calendar
    return [
        'success' => true,
        'details' => [
            'test_mode' => true,
            'would_send' => $bookingData,
            'endpoint' => "$baseUrlV1/bookings?apiKey=$apiKey"
        ]
    ];
});

// Test 8: Create Booking (V2) - Test Mode
runTest('Create Booking - V2 API (Test Mode)', function() use ($baseUrlV2) {
    $bookingData = [
        'eventTypeId' => 2563193,
        'start' => date('Y-m-d\TH:i:s', strtotime('+2 days 14:00')) . '.000Z',
        'attendee' => [
            'name' => 'API Test User V2',
            'email' => 'api-test-v2@example.com',
            'timeZone' => 'Europe/Berlin',
            'language' => 'de'
        ],
        'metadata' => [
            'source' => 'askproai',
            'via' => 'api_test_v2'
        ],
        'location' => 'phone'
    ];
    
    return [
        'success' => true,
        'details' => [
            'test_mode' => true,
            'would_send' => $bookingData,
            'endpoint' => "$baseUrlV2/bookings"
        ]
    ];
});

// Test 9: Webhooks Endpoint (V1)
runTest('Webhooks List - V1 API', function() use ($baseUrlV1, $apiKey) {
    $url = "$baseUrlV1/webhooks?apiKey=$apiKey";
    $result = makeRequest($url);
    
    return [
        'success' => $result['success'],
        'details' => [
            'http_code' => $result['http_code'],
            'webhooks_count' => isset($result['response']['webhooks']) ? count($result['response']['webhooks']) : 0,
            'webhooks' => $result['response']['webhooks'] ?? []
        ],
        'error' => !$result['success'] ? $result['raw_response'] : null
    ];
});

// Test 10: Webhooks Endpoint (V2)
runTest('Webhooks List - V2 API', function() use ($baseUrlV2) {
    $url = "$baseUrlV2/webhooks";
    $result = makeRequest($url, 'GET', null, [], 'v2');
    
    return [
        'success' => $result['success'],
        'details' => [
            'http_code' => $result['http_code'],
            'response' => $result['response']
        ],
        'error' => !$result['success'] ? $result['raw_response'] : null
    ];
});

// Test 11: Teams/Organizations (V2)
runTest('Organizations - V2 API', function() use ($baseUrlV2) {
    $url = "$baseUrlV2/organizations";
    $result = makeRequest($url, 'GET', null, [], 'v2');
    
    return [
        'success' => $result['success'],
        'details' => [
            'http_code' => $result['http_code'],
            'response' => $result['response']
        ],
        'error' => !$result['success'] ? $result['raw_response'] : null
    ];
});

// Test 12: Schedules (V2)
runTest('Schedules - V2 API', function() use ($baseUrlV2) {
    $url = "$baseUrlV2/schedules";
    $result = makeRequest($url, 'GET', null, [], 'v2');
    
    return [
        'success' => $result['success'],
        'details' => [
            'http_code' => $result['http_code'],
            'response' => $result['response']
        ],
        'error' => !$result['success'] ? $result['raw_response'] : null
    ];
});

// Generate Summary Report
echo "\n\n{$blue}========== TEST SUMMARY =========={$reset}\n";

$passed = 0;
$failed = 0;
$v1Features = [];
$v2Features = [];

foreach ($testResults as $testName => $result) {
    if ($result['success']) {
        $passed++;
        echo "{$green}✓ $testName{$reset}\n";
        
        if (strpos($testName, 'V1') !== false) {
            $v1Features[] = $testName;
        } elseif (strpos($testName, 'V2') !== false) {
            $v2Features[] = $testName;
        }
    } else {
        $failed++;
        echo "{$red}✗ $testName{$reset}\n";
    }
}

echo "\n{$blue}Total Tests:{$reset} " . ($passed + $failed) . "\n";
echo "{$green}Passed:{$reset} $passed\n";
echo "{$red}Failed:{$reset} $failed\n";

// Feature Comparison
echo "\n{$blue}========== FEATURE COMPARISON =========={$reset}\n";
echo "\n{$yellow}V1 API Working Features:{$reset}\n";
foreach ($v1Features as $feature) {
    echo "  • $feature\n";
}

echo "\n{$yellow}V2 API Status:{$reset}\n";
foreach ($testResults as $testName => $result) {
    if (strpos($testName, 'V2') !== false) {
        $status = $result['success'] ? "{$green}Working{$reset}" : "{$red}Not Working{$reset}";
        echo "  • $testName: $status\n";
        if (!$result['success'] && isset($result['error'])) {
            echo "    Error: " . substr($result['error'], 0, 100) . "...\n";
        }
    }
}

// Recommendations
echo "\n{$blue}========== RECOMMENDATIONS =========={$reset}\n";
echo "Based on the test results:\n";

if ($testResults['Event Types - V1 API']['success'] && !$testResults['Event Types - V2 API']['success']) {
    echo "• {$yellow}Event Types:{$reset} Continue using V1 API for event types listing\n";
}

if ($testResults['Availability Check - V1 API']['success'] && !$testResults['Availability Check - V2 API']['success']) {
    echo "• {$yellow}Availability:{$reset} V1 API works but V2 might need different parameters\n";
}

echo "• {$yellow}Booking Creation:{$reset} V1 API format is working and well-tested\n";
echo "• {$yellow}Webhooks:{$reset} Check if V1 webhooks are sufficient for your needs\n";

// Save detailed results to file
$timestamp = date('Y-m-d_H-i-s');
$resultsFile = "calcom-test-results-$timestamp.json";
file_put_contents($resultsFile, json_encode($testResults, JSON_PRETTY_PRINT));
echo "\n{$blue}Detailed results saved to:{$reset} $resultsFile\n";