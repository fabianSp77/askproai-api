#!/usr/bin/env php
<?php
/**
 * Test what Retell expects from MCP initialize
 */

echo "Testing MCP initialize response that Retell expects...\n\n";

// Test our MCP endpoint
$url = "https://api.askproai.de/api/v2/hair-salon-mcp/mcp";

// Standard MCP initialize request
$initRequest = [
    "jsonrpc" => "2.0",
    "method" => "initialize",
    "params" => [
        "protocolVersion" => "2024-11-05",
        "capabilities" => []
    ],
    "id" => "init-test"
];

echo "Sending initialize request to: $url\n";
echo "Request: " . json_encode($initRequest, JSON_PRETTY_PRINT) . "\n\n";

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($initRequest));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Accept: application/json'
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_VERBOSE, true);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Status: $httpCode\n";
echo "Response: " . json_encode(json_decode($response), JSON_PRETTY_PRINT) . "\n\n";

if ($httpCode == 200) {
    $data = json_decode($response, true);
    if (isset($data['result']['tools'])) {
        echo "✅ MCP endpoint is working!\n";
        echo "Available tools:\n";
        foreach ($data['result']['tools'] as $tool) {
            echo "  - " . $tool['name'] . ": " . $tool['description'] . "\n";
        }
    } else {
        echo "⚠️  Response missing tools\n";
    }
} else {
    echo "❌ MCP endpoint not responding correctly\n";
}

echo "\n\nWhat Retell needs:\n";
echo "1. MCP URL in agent config\n";
echo "2. Server must respond to 'initialize' method\n";
echo "3. Response must include available tools\n";
echo "4. Retell will then call tools as needed during conversation\n";