<?php

// Test webhook connectivity from local server
$webhookUrl = 'https://api.askproai.de/api/retell/debug-webhook';

// Simple test payload
$testData = [
    'event' => 'test',
    'timestamp' => time(),
    'test' => true
];

echo "Testing webhook connectivity to: $webhookUrl\n";
echo "From local server...\n\n";

// Test 1: Local curl
$ch = curl_init($webhookUrl);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($testData));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Accept: application/json'
]);
curl_setopt($ch, CURLOPT_VERBOSE, true);
curl_setopt($ch, CURLOPT_STDERR, fopen('php://stdout', 'w'));

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

echo "\n\nResult:\n";
echo "HTTP Status: $httpCode\n";
if ($error) {
    echo "Error: $error\n";
}
echo "Response: $response\n\n";

// Test 2: Check if we can receive external connections
echo "Testing if our server can receive external webhooks...\n";
echo "Current server IP: " . gethostbyname(gethostname()) . "\n";

// Check nginx access log for recent POST requests
echo "\nRecent POST requests to our webhook endpoints:\n";
$logs = `tail -n 100 /var/log/nginx/access.log | grep -E "POST.*retell" | tail -10`;
echo $logs ?: "No recent webhook requests found.\n";

echo "\nDone!\n";