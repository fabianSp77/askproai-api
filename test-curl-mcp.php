#!/usr/bin/env php
<?php

echo "Testing MCP via CURL (like Retell would)\n";
echo "=========================================\n\n";

$url = 'https://api.askproai.de/api/v2/hair-salon-mcp/mcp';

// Test 1: Basic list_services
echo "1. Testing list_services:\n";
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Accept: application/json'
]);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
    'jsonrpc' => '2.0',
    'id' => 1,
    'method' => 'list_services',
    'params' => ['company_id' => 1]
]));
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_VERBOSE, true);

$verbose = fopen('php://temp', 'w+');
curl_setopt($ch, CURLOPT_STDERR, $verbose);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

if ($response === false) {
    echo "   ERROR: " . curl_error($ch) . "\n";
} else {
    echo "   HTTP Code: $httpCode\n";
    $data = json_decode($response, true);
    if (isset($data['result']['services'])) {
        echo "   Services: " . count($data['result']['services']) . "\n";
        if (count($data['result']['services']) > 0) {
            echo "   First 3:\n";
            foreach (array_slice($data['result']['services'], 0, 3) as $s) {
                echo "     - {$s['name']} ({$s['price']}€)\n";
            }
        }
    } else {
        echo "   Response: " . $response . "\n";
    }
}

rewind($verbose);
$verboseLog = stream_get_contents($verbose);
echo "\n   Verbose output (last 5 lines):\n";
$lines = explode("\n", $verboseLog);
foreach (array_slice($lines, -5) as $line) {
    if (trim($line)) echo "     $line\n";
}

curl_close($ch);

// Test 2: Check if it's a session/cookie issue
echo "\n2. Testing with cookie jar:\n";
$cookieFile = '/tmp/mcp-cookies.txt';
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Accept: application/json',
    'X-Requested-With: XMLHttpRequest'
]);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
    'jsonrpc' => '2.0',
    'id' => 2,
    'method' => 'list_services',
    'params' => ['company_id' => 1]
]));
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

echo "   HTTP Code: $httpCode\n";
$data = json_decode($response, true);
if (isset($data['result']['services'])) {
    echo "   Services: " . count($data['result']['services']) . "\n";
} else {
    echo "   Raw response: " . substr($response, 0, 200) . "\n";
}

curl_close($ch);

// Test 3: Direct file_get_contents
echo "\n3. Testing with file_get_contents:\n";
$context = stream_context_create([
    'http' => [
        'method' => 'POST',
        'header' => [
            'Content-Type: application/json',
            'Accept: application/json'
        ],
        'content' => json_encode([
            'jsonrpc' => '2.0',
            'id' => 3,
            'method' => 'list_services',
            'params' => ['company_id' => 1]
        ])
    ],
    'ssl' => [
        'verify_peer' => false,
        'verify_peer_name' => false
    ]
]);

$response = @file_get_contents($url, false, $context);
if ($response !== false) {
    $data = json_decode($response, true);
    if (isset($data['result']['services'])) {
        echo "   Services: " . count($data['result']['services']) . "\n";
    } else {
        echo "   Response: " . $response . "\n";
    }
} else {
    echo "   ERROR: Could not fetch\n";
}

echo "\nDONE\n";