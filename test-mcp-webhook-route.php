<?php

// Test MCP webhook route directly without signature
$baseUrl = 'https://api.askproai.de';

echo "=== Testing MCP Webhook Route ===\n\n";

// Test 1: Simple GET request to check if route exists
echo "1. Testing if route exists (GET)...\n";
$ch = curl_init($baseUrl . '/api/mcp/retell/webhook');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "   Response Code: $httpCode\n";
if ($httpCode == 405) {
    echo "   ✅ Route exists (method not allowed for GET)\n";
} else {
    echo "   Response: " . substr($response, 0, 200) . "\n";
}

// Test 2: POST without signature
echo "\n2. Testing POST without signature...\n";
$testPayload = [
    'event' => 'call_ended',
    'call_id' => 'test-' . uniqid(),
    'timestamp' => time()
];

$ch = curl_init($baseUrl . '/api/mcp/retell/webhook');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($testPayload));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Accept: application/json'
]);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "   Response Code: $httpCode\n";
if ($httpCode == 401) {
    echo "   ✅ Signature verification is active (good)\n";
} else {
    echo "   Response: " . substr($response, 0, 500) . "\n";
}

// Test 3: Check alternative path
echo "\n3. Testing alternative MCP path (/api/mcp/retell/events)...\n";
$ch = curl_init($baseUrl . '/api/mcp/retell/events');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($testPayload));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Accept: application/json'
]);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "   Response Code: $httpCode\n";

// Test 4: Check if api-mcp.php routes are loaded
echo "\n4. Checking route configuration...\n";
$routeListUrl = $baseUrl . '/api/route-list'; // This might not exist

// Instead, let's check a known working endpoint
$ch = curl_init($baseUrl . '/api/health');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "   Health check response: $httpCode\n";

echo "\n=== Debugging Steps ===\n";
echo "1. Check if api-mcp.php routes are loaded in RouteServiceProvider\n";
echo "2. Clear route cache: php artisan route:clear\n";
echo "3. Check Laravel logs: tail -f storage/logs/laravel.log\n";
echo "4. Verify RetellWebhookMCPController exists and has no syntax errors\n";
echo "5. Check if required MCP services are registered in AppServiceProvider\n";

echo "\nDone!\n";