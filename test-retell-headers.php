#!/usr/bin/env php
<?php
/**
 * Test what headers Retell might expect
 */

echo "Testing different header combinations for Retell MCP\n";
echo "====================================================\n\n";

$url = 'https://api.askproai.de/api/v2/hair-salon-mcp/mcp';

// Test 1: With Retell-specific headers
echo "1. Testing with Retell headers:\n";
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Accept: application/json',
    'X-Retell-Agent-Id: test-agent',
    'X-Retell-Call-Id: test-call-123',
    'Authorization: Bearer test-token'
]);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
    'jsonrpc' => '2.0',
    'id' => 'retell-header-test',
    'method' => 'list_services',
    'params' => ['company_id' => 1]
]));
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "   HTTP Code: $httpCode\n";
$data = json_decode($response, true);
if (isset($data['result']['services'])) {
    echo "   ✅ Services returned: " . count($data['result']['services']) . "\n";
} else {
    echo "   Response: " . substr($response, 0, 100) . "\n";
}

// Test 2: Test if Retell needs a specific format
echo "\n2. Testing without jsonrpc wrapper (plain JSON):\n";
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Accept: application/json'
]);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
    'tool' => 'list_services',
    'arguments' => ['company_id' => 1]
]));
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "   HTTP Code: $httpCode\n";
echo "   Response: " . substr($response, 0, 200) . "\n";

// Test 3: Test OpenAI function calling format
echo "\n3. Testing OpenAI function format:\n";
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Accept: application/json'
]);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
    'function' => 'list_services',
    'parameters' => ['company_id' => 1]
]));
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "   HTTP Code: $httpCode\n";
echo "   Response: " . substr($response, 0, 200) . "\n";

echo "\n✅ All tests complete\n";