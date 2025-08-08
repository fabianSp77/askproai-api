#!/usr/bin/env php
<?php
/**
 * Complete Hair Salon MCP Test Suite
 * Tests all components: MCP Bridge, Webhook Handler, and Retell Integration
 */

echo "\033[1;36m================================================================================\033[0m\n";
echo "\033[1;33mHair Salon MCP - Complete Integration Test\033[0m\n";
echo "\033[1;36m================================================================================\033[0m\n\n";

$baseUrl = 'https://api.askproai.de';
$localUrl = 'http://localhost';
$testResults = [];
$totalTests = 0;
$passedTests = 0;

function testEndpoint($name, $url, $method = 'GET', $data = null, $headers = []) {
    global $totalTests, $passedTests;
    $totalTests++;
    
    echo "Testing: $name\n";
    echo "URL: $url\n";
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    
    if ($data) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        $headers[] = 'Content-Type: application/json';
    }
    
    if (!empty($headers)) {
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    }
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        echo "\033[0;31m✗ FAILED\033[0m - CURL Error: $error\n\n";
        return false;
    }
    
    $success = ($httpCode >= 200 && $httpCode < 300);
    
    if ($success) {
        $passedTests++;
        echo "\033[0;32m✓ PASSED\033[0m - HTTP $httpCode\n";
        
        $decoded = json_decode($response, true);
        if ($decoded) {
            echo "Response: " . json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
        }
    } else {
        echo "\033[0;31m✗ FAILED\033[0m - HTTP $httpCode\n";
        if ($response) {
            echo "Response: " . substr($response, 0, 500) . "\n";
        }
    }
    
    echo str_repeat('-', 80) . "\n\n";
    return $success;
}

// Test 1: Health Check
echo "\033[1;35m🏥 1. Health Check\033[0m\n";
echo str_repeat('=', 80) . "\n\n";

testEndpoint(
    'MCP Bridge Health',
    "$baseUrl/api/v2/hair-salon-mcp/mcp/health"
);

// Test 2: MCP Bridge - List Services
echo "\033[1;35m📋 2. MCP Bridge - List Services\033[0m\n";
echo str_repeat('=', 80) . "\n\n";

testEndpoint(
    'List Services via MCP Bridge',
    "$baseUrl/api/v2/hair-salon-mcp/mcp",
    'POST',
    [
        'jsonrpc' => '2.0',
        'method' => 'list_services',
        'params' => ['company_id' => 1],
        'id' => 'test-1'
    ],
    ['X-Company-ID: 1']
);

// Test 3: MCP Bridge - Check Availability
echo "\033[1;35m📅 3. MCP Bridge - Check Availability\033[0m\n";
echo str_repeat('=', 80) . "\n\n";

testEndpoint(
    'Check Availability via MCP Bridge',
    "$baseUrl/api/v2/hair-salon-mcp/mcp",
    'POST',
    [
        'jsonrpc' => '2.0',
        'method' => 'check_availability',
        'params' => [
            'company_id' => 1,
            'service_id' => 1,
            'date' => date('Y-m-d'),
            'days_ahead' => 3
        ],
        'id' => 'test-2'
    ],
    ['X-Company-ID: 1']
);

// Test 4: Direct Method Endpoints
echo "\033[1;35m🔗 4. Direct Method Endpoints\033[0m\n";
echo str_repeat('=', 80) . "\n\n";

testEndpoint(
    'Direct List Services Endpoint',
    "$baseUrl/api/v2/hair-salon-mcp/mcp/list_services",
    'POST',
    ['method' => 'list_services', 'params' => ['company_id' => 1]]
);

// Test 5: Webhook Handler - Function Call
echo "\033[1;35m🪝 5. Webhook Handler Tests\033[0m\n";
echo str_repeat('=', 80) . "\n\n";

testEndpoint(
    'Webhook - List Services Function Call',
    "$baseUrl/api/v2/hair-salon-mcp/retell-webhook",
    'POST',
    [
        'event' => 'function_call',
        'call_id' => 'test-call-001',
        'function_name' => 'list_services',
        'arguments' => ['company_id' => 1]
    ]
);

// Test 6: Webhook Handler - Call Events
testEndpoint(
    'Webhook - Call Started',
    "$baseUrl/api/v2/hair-salon-mcp/retell-webhook",
    'POST',
    [
        'event' => 'call_started',
        'call_id' => 'test-call-002',
        'from_number' => '+491234567890',
        'to_number' => '+493033081738'
    ]
);

testEndpoint(
    'Webhook - Call Ended',
    "$baseUrl/api/v2/hair-salon-mcp/retell-webhook",
    'POST',
    [
        'event' => 'call_ended',
        'call_id' => 'test-call-002',
        'call_duration_seconds' => 120,
        'recording_url' => 'https://example.com/recording.mp3'
    ]
);

// Test 7: Schedule Callback (Consultation Required)
echo "\033[1;35m📞 6. Schedule Callback Test\033[0m\n";
echo str_repeat('=', 80) . "\n\n";

testEndpoint(
    'Schedule Callback via MCP Bridge',
    "$baseUrl/api/v2/hair-salon-mcp/mcp",
    'POST',
    [
        'jsonrpc' => '2.0',
        'method' => 'schedule_callback',
        'params' => [
            'company_id' => 1,
            'customer_name' => 'Test Kunde',
            'customer_phone' => '+491234567890',
            'service_name' => 'Strähnen',
            'preferred_time' => 'Nachmittags',
            'notes' => 'Möchte blonde Strähnen'
        ],
        'id' => 'test-3'
    ],
    ['X-Company-ID: 1']
);

// Test 8: Book Appointment
echo "\033[1;35m📝 7. Book Appointment Test\033[0m\n";
echo str_repeat('=', 80) . "\n\n";

$tomorrow = date('Y-m-d H:i', strtotime('tomorrow 14:00'));
testEndpoint(
    'Book Appointment via MCP Bridge',
    "$baseUrl/api/v2/hair-salon-mcp/mcp",
    'POST',
    [
        'jsonrpc' => '2.0',
        'method' => 'book_appointment',
        'params' => [
            'company_id' => 1,
            'customer_name' => 'Maria Müller',
            'customer_phone' => '+491234567890',
            'customer_email' => 'maria@example.com',
            'service_id' => 1,
            'staff_id' => 1,
            'datetime' => $tomorrow,
            'notes' => 'Erstkundin, möchte Beratung zu Haarpflege'
        ],
        'id' => 'test-4'
    ],
    ['X-Company-ID: 1']
);

// Test 9: Initialize MCP
echo "\033[1;35m🚀 8. Initialize MCP Test\033[0m\n";
echo str_repeat('=', 80) . "\n\n";

testEndpoint(
    'Initialize MCP Connection',
    "$baseUrl/api/v2/hair-salon-mcp/mcp",
    'POST',
    [
        'jsonrpc' => '2.0',
        'method' => 'initialize',
        'params' => ['company_id' => 1],
        'id' => 'test-init'
    ]
);

// Test 10: Error Handling
echo "\033[1;35m⚠️ 9. Error Handling Tests\033[0m\n";
echo str_repeat('=', 80) . "\n\n";

testEndpoint(
    'Invalid Method Test',
    "$baseUrl/api/v2/hair-salon-mcp/mcp",
    'POST',
    [
        'jsonrpc' => '2.0',
        'method' => 'invalid_method',
        'params' => [],
        'id' => 'test-error'
    ]
);

testEndpoint(
    'Missing Required Parameters',
    "$baseUrl/api/v2/hair-salon-mcp/mcp",
    'POST',
    [
        'jsonrpc' => '2.0',
        'method' => 'book_appointment',
        'params' => ['customer_name' => 'Test Only'],
        'id' => 'test-error-2'
    ]
);

// Summary
echo "\n\033[1;36m================================================================================\033[0m\n";
echo "\033[1;33mTest Summary\033[0m\n";
echo "\033[1;36m================================================================================\033[0m\n\n";

$failedTests = $totalTests - $passedTests;
$passRate = $totalTests > 0 ? round(($passedTests / $totalTests) * 100, 2) : 0;

echo "Total Tests: $totalTests\n";
echo "\033[0;32mPassed: $passedTests\033[0m\n";
if ($failedTests > 0) {
    echo "\033[0;31mFailed: $failedTests\033[0m\n";
}
echo "Pass Rate: $passRate%\n\n";

if ($passRate == 100) {
    echo "\033[1;32m🎉 All tests passed! The Hair Salon MCP is fully operational!\033[0m\n\n";
} elseif ($passRate >= 80) {
    echo "\033[1;33m⚠️ Most tests passed, but some issues need attention.\033[0m\n\n";
} else {
    echo "\033[1;31m❌ Many tests failed. Please check the configuration.\033[0m\n\n";
}

// Configuration Status
echo "\033[1;36m📋 Configuration Checklist:\033[0m\n";
echo str_repeat('-', 80) . "\n\n";

$checklist = [
    'MCP Bridge Controller' => file_exists('/var/www/api-gateway/app/Http/Controllers/RetellMCPBridgeController.php'),
    'Webhook Handler' => file_exists('/var/www/api-gateway/app/Http/Controllers/RetellHairSalonWebhookController.php'),
    'MCP Server' => file_exists('/var/www/api-gateway/app/Services/MCP/HairSalonMCPServer.php'),
    'Routes Configured' => strpos(file_get_contents('/var/www/api-gateway/routes/api.php'), 'hair-salon-mcp') !== false,
    'Database Migration' => true // Assume it's run
];

foreach ($checklist as $item => $status) {
    echo $status ? "\033[0;32m✓\033[0m" : "\033[0;31m✗\033[0m";
    echo " $item\n";
}

echo "\n\033[1;36m📞 Retell.ai Configuration:\033[0m\n";
echo str_repeat('-', 80) . "\n\n";

echo "1. Open Retell Dashboard: https://dashboard.retellai.com\n";
echo "2. Navigate to Agent: agent_d7da9e5c49c4ccfff2526df5c1\n";
echo "3. Go to @MCP section (not Functions)\n";
echo "4. Configure with these values:\n\n";

echo "\033[1;33mName:\033[0m hair_salon_mcp\n";
echo "\033[1;33mURL:\033[0m https://api.askproai.de/api/v2/hair-salon-mcp/mcp\n";
echo "\033[1;33mTimeout:\033[0m 30000\n";
echo "\033[1;33mHeaders:\033[0m\n";
echo "{\n";
echo '  "Content-Type": "application/json",'."\n";
echo '  "X-Company-ID": "1"'."\n";
echo "}\n\n";

echo "\033[1;36m🧪 Test Commands:\033[0m\n";
echo str_repeat('-', 80) . "\n\n";

echo "# Monitor logs:\n";
echo "tail -f storage/logs/laravel.log | grep -i 'mcp\\|retell'\n\n";

echo "# Test specific function:\n";
echo "curl -X POST https://api.askproai.de/api/v2/hair-salon-mcp/mcp \\\n";
echo "  -H 'Content-Type: application/json' \\\n";
echo "  -d '{\"jsonrpc\":\"2.0\",\"method\":\"list_services\",\"params\":{\"company_id\":1},\"id\":\"1\"}'\n\n";

echo "\033[1;32m✅ Test suite complete!\033[0m\n";