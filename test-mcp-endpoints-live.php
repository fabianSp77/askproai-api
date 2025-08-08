#!/usr/bin/env php
<?php
/**
 * Live MCP Endpoint Testing after Retell Configuration
 */

echo "\033[1;36m================================================================================\033[0m\n";
echo "\033[1;33mHair Salon MCP - Live Endpoint Testing\033[0m\n";
echo "Time: " . date('Y-m-d H:i:s') . "\n";
echo "\033[1;36m================================================================================\033[0m\n\n";

$baseUrl = 'https://api.askproai.de';
$testResults = [];

function testEndpoint($name, $url, $method = 'GET', $data = null, $headers = []) {
    echo "\n\033[1;35m► Testing: $name\033[0m\n";
    echo "  URL: $url\n";
    echo "  Method: $method\n";
    
    if ($data) {
        echo "  Payload: " . json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
    }
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    
    if ($data) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        $headers[] = 'Content-Type: application/json';
    }
    
    // Add MCP headers
    $headers[] = 'Accept: application/json';
    $headers[] = 'X-Company-ID: 1';
    $headers[] = 'X-MCP-Version: 2.0';
    
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    
    $startTime = microtime(true);
    $response = curl_exec($ch);
    $endTime = microtime(true);
    $responseTime = round(($endTime - $startTime) * 1000, 2);
    
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    echo "  Response Time: {$responseTime}ms\n";
    echo "  HTTP Status: $httpCode\n";
    
    if ($error) {
        echo "  \033[0;31m✗ FAILED - CURL Error: $error\033[0m\n";
        return ['success' => false, 'error' => $error];
    }
    
    $success = ($httpCode >= 200 && $httpCode < 300);
    
    if ($success) {
        echo "  \033[0;32m✓ SUCCESS\033[0m\n";
        $decoded = json_decode($response, true);
        if ($decoded) {
            echo "  Response: " . json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
            return ['success' => true, 'data' => $decoded];
        } else {
            echo "  Raw Response: " . substr($response, 0, 200) . "\n";
            return ['success' => true, 'data' => $response];
        }
    } else {
        echo "  \033[0;31m✗ FAILED\033[0m\n";
        if ($response) {
            $decoded = json_decode($response, true);
            if ($decoded) {
                echo "  Error Response: " . json_encode($decoded, JSON_PRETTY_PRINT) . "\n";
            } else {
                echo "  Raw Response: " . substr($response, 0, 500) . "\n";
            }
        }
        return ['success' => false, 'http_code' => $httpCode];
    }
}

// Test 1: Health Check
echo "\n\033[1;33m1️⃣ Health Check Endpoint\033[0m";
echo "\n" . str_repeat('=', 80) . "\n";

$healthResult = testEndpoint(
    'MCP Health Check',
    "$baseUrl/api/v2/hair-salon-mcp/mcp/health"
);
$testResults['health'] = $healthResult['success'];

// Test 2: Initialize MCP
echo "\n\033[1;33m2️⃣ Initialize MCP Connection\033[0m";
echo "\n" . str_repeat('=', 80) . "\n";

$initResult = testEndpoint(
    'Initialize MCP',
    "$baseUrl/api/v2/hair-salon-mcp/mcp",
    'POST',
    [
        'jsonrpc' => '2.0',
        'method' => 'initialize',
        'params' => ['company_id' => 1],
        'id' => 'init-' . time()
    ]
);
$testResults['initialize'] = $initResult['success'];

// Test 3: List Services
echo "\n\033[1;33m3️⃣ List Available Services\033[0m";
echo "\n" . str_repeat('=', 80) . "\n";

$servicesResult = testEndpoint(
    'List Services',
    "$baseUrl/api/v2/hair-salon-mcp/mcp",
    'POST',
    [
        'jsonrpc' => '2.0',
        'method' => 'list_services',
        'params' => [
            'company_id' => 1,
            'category' => null
        ],
        'id' => 'services-' . time()
    ]
);
$testResults['list_services'] = $servicesResult['success'];

// Test 4: Check Availability
echo "\n\033[1;33m4️⃣ Check Appointment Availability\033[0m";
echo "\n" . str_repeat('=', 80) . "\n";

$tomorrow = date('Y-m-d', strtotime('tomorrow'));
$availabilityResult = testEndpoint(
    'Check Availability',
    "$baseUrl/api/v2/hair-salon-mcp/mcp",
    'POST',
    [
        'jsonrpc' => '2.0',
        'method' => 'check_availability',
        'params' => [
            'company_id' => 1,
            'service_id' => 1,
            'date' => $tomorrow,
            'days_ahead' => 3,
            'staff_id' => 1
        ],
        'id' => 'avail-' . time()
    ]
);
$testResults['check_availability'] = $availabilityResult['success'];

// Test 5: Schedule Callback (for consultation services)
echo "\n\033[1;33m5️⃣ Schedule Consultation Callback\033[0m";
echo "\n" . str_repeat('=', 80) . "\n";

$callbackResult = testEndpoint(
    'Schedule Callback',
    "$baseUrl/api/v2/hair-salon-mcp/mcp",
    'POST',
    [
        'jsonrpc' => '2.0',
        'method' => 'schedule_callback',
        'params' => [
            'company_id' => 1,
            'customer_name' => 'Test Kunde ' . date('His'),
            'customer_phone' => '+49151' . rand(10000000, 99999999),
            'service_name' => 'Strähnen',
            'preferred_time' => 'Nachmittags zwischen 14-16 Uhr',
            'notes' => 'Möchte blonde Strähnen, hat langes Haar'
        ],
        'id' => 'callback-' . time()
    ]
);
$testResults['schedule_callback'] = $callbackResult['success'];

// Test 6: Book Appointment
echo "\n\033[1;33m6️⃣ Book Appointment (Test Booking)\033[0m";
echo "\n" . str_repeat('=', 80) . "\n";

$appointmentTime = date('Y-m-d H:i', strtotime('tomorrow 15:00'));
$bookingResult = testEndpoint(
    'Book Appointment',
    "$baseUrl/api/v2/hair-salon-mcp/mcp",
    'POST',
    [
        'jsonrpc' => '2.0',
        'method' => 'book_appointment',
        'params' => [
            'company_id' => 1,
            'customer_name' => 'Test Kunde ' . date('His'),
            'customer_phone' => '+49151' . rand(10000000, 99999999),
            'customer_email' => 'test' . time() . '@example.com',
            'service_id' => 1,
            'staff_id' => 1,
            'datetime' => $appointmentTime,
            'notes' => 'Testbuchung über MCP'
        ],
        'id' => 'book-' . time()
    ]
);
$testResults['book_appointment'] = $bookingResult['success'];

// Test 7: Direct Method Endpoints
echo "\n\033[1;33m7️⃣ Direct Method Endpoints\033[0m";
echo "\n" . str_repeat('=', 80) . "\n";

$directResult = testEndpoint(
    'Direct List Services Endpoint',
    "$baseUrl/api/v2/hair-salon-mcp/mcp/list_services",
    'POST',
    [
        'method' => 'list_services',
        'params' => ['company_id' => 1]
    ]
);
$testResults['direct_endpoint'] = $directResult['success'];

// Test 8: Webhook Endpoint
echo "\n\033[1;33m8️⃣ Webhook Handler Test\033[0m";
echo "\n" . str_repeat('=', 80) . "\n";

$webhookResult = testEndpoint(
    'Webhook - Function Call Simulation',
    "$baseUrl/api/v2/hair-salon-mcp/retell-webhook",
    'POST',
    [
        'event' => 'function_call',
        'call_id' => 'test-' . uniqid(),
        'function_name' => 'list_services',
        'arguments' => ['company_id' => 1]
    ]
);
$testResults['webhook'] = $webhookResult['success'];

// Test 9: Error Handling
echo "\n\033[1;33m9️⃣ Error Handling Test\033[0m";
echo "\n" . str_repeat('=', 80) . "\n";

$errorResult = testEndpoint(
    'Invalid Method Test',
    "$baseUrl/api/v2/hair-salon-mcp/mcp",
    'POST',
    [
        'jsonrpc' => '2.0',
        'method' => 'invalid_method_test',
        'params' => [],
        'id' => 'error-test'
    ]
);
// We expect this to fail properly
$testResults['error_handling'] = !$errorResult['success'] && isset($errorResult['http_code']);

// Summary
echo "\n\n\033[1;36m================================================================================\033[0m\n";
echo "\033[1;33m📊 Test Summary\033[0m\n";
echo "\033[1;36m================================================================================\033[0m\n\n";

$totalTests = count($testResults);
$passedTests = count(array_filter($testResults));
$failedTests = $totalTests - $passedTests;
$passRate = round(($passedTests / $totalTests) * 100, 2);

echo "Test Results:\n";
echo str_repeat('-', 50) . "\n";

foreach ($testResults as $test => $passed) {
    $status = $passed ? "\033[0;32m✓ PASS\033[0m" : "\033[0;31m✗ FAIL\033[0m";
    $testName = str_replace('_', ' ', ucfirst($test));
    echo sprintf("%-30s %s\n", $testName . ":", $status);
}

echo str_repeat('-', 50) . "\n";
echo "Total: $totalTests | Passed: $passedTests | Failed: $failedTests\n";
echo "Pass Rate: $passRate%\n\n";

if ($passRate == 100) {
    echo "\033[1;32m🎉 Excellent! All endpoints are working perfectly!\033[0m\n";
    echo "The MCP integration is fully operational.\n";
} elseif ($passRate >= 70) {
    echo "\033[1;33m⚠️ Good! Most endpoints are working.\033[0m\n";
    echo "Some endpoints need attention.\n";
} else {
    echo "\033[1;31m❌ Critical: Many endpoints are failing.\033[0m\n";
    echo "Please check the configuration and logs.\n";
}

// Next Steps
echo "\n\033[1;36m📝 Next Steps:\033[0m\n";
echo str_repeat('-', 50) . "\n";

if ($testResults['health'] && $testResults['list_services']) {
    echo "✅ Basic MCP connectivity confirmed!\n";
    echo "1. Test with actual Retell.ai agent call: +493033081738\n";
    echo "2. Say: 'Ich möchte einen Termin für einen Haarschnitt'\n";
    echo "3. Monitor logs: tail -f storage/logs/laravel.log | grep -i mcp\n";
} else {
    echo "❌ Fix connectivity issues first:\n";
    echo "1. Check Laravel logs: tail -100 storage/logs/laravel.log\n";
    echo "2. Clear cache: php artisan optimize:clear\n";
    echo "3. Verify routes: php artisan route:list | grep hair-salon\n";
}

echo "\n\033[1;36m🔍 Monitoring Commands:\033[0m\n";
echo str_repeat('-', 50) . "\n";
echo "# Watch MCP requests:\n";
echo "tail -f storage/logs/laravel.log | grep -i 'MCP\\|Retell'\n\n";

echo "# Test specific function:\n";
echo "curl -X POST $baseUrl/api/v2/hair-salon-mcp/mcp \\\n";
echo "  -H 'Content-Type: application/json' \\\n";
echo "  -H 'X-Company-ID: 1' \\\n";
echo "  -d '{\"jsonrpc\":\"2.0\",\"method\":\"list_services\",\"params\":{},\"id\":\"1\"}'\n";

echo "\n\033[1;32m✅ Test completed at " . date('H:i:s') . "\033[0m\n";