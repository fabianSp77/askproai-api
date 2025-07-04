<?php

/**
 * Simple Retell Webhook Signature Test
 */

echo "\n🧪 Testing Retell Webhook Signature Verification\n";
echo "================================================\n\n";

// Read API key from .env
$envFile = __DIR__ . '/.env';
$apiKey = null;

if (file_exists($envFile)) {
    $envContent = file_get_contents($envFile);
    if (preg_match('/^RETELL_TOKEN=(.*)$/m', $envContent, $matches)) {
        $apiKey = trim($matches[1]);
    }
}

if (!$apiKey) {
    die("❌ Error: No RETELL_TOKEN found in .env!\n");
}

echo "✅ API Key found: " . substr($apiKey, 0, 10) . "...\n\n";

// Test payload
$payload = [
    'event' => 'call_ended',
    'call' => [
        'call_id' => 'test_' . uniqid(),
        'call_type' => 'phone_call',
        'from_number' => '+1234567890',
        'to_number' => '+0987654321',
        'direction' => 'inbound',
        'agent_id' => 'test_agent',
        'start_timestamp' => time() * 1000,
        'end_timestamp' => (time() + 120) * 1000,
        'duration' => 120000,
        'disconnection_reason' => 'user_hangup'
    ]
];

$jsonPayload = json_encode($payload);

// Generate valid signature
$signature = hash_hmac('sha256', $jsonPayload, $apiKey);

echo "📝 Generated Signature: " . substr($signature, 0, 40) . "...\n\n";

echo "🚀 Testing webhook endpoint...\n\n";

// Test with valid signature
$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => 'http://localhost/api/retell/webhook',
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => $jsonPayload,
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/json',
        'X-Retell-Signature: ' . $signature
    ],
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HEADER => true,
    CURLOPT_TIMEOUT => 10
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
$headers = substr($response, 0, $headerSize);
$body = substr($response, $headerSize);
curl_close($ch);

echo "Response Code: $httpCode\n";
echo "Response Body: " . substr($body, 0, 200) . "\n\n";

if ($httpCode == 200 || $httpCode == 204) {
    echo "✅ SUCCESS: Webhook accepted the signature!\n";
    echo "   Signature verification is working correctly.\n";
} elseif ($httpCode == 401) {
    echo "❌ FAILED: Signature was rejected (401 Unauthorized)\n";
    echo "   Check that the API key in .env matches the one in the database.\n";
} elseif ($httpCode == 500) {
    echo "⚠️  Server Error (500)\n";
    echo "   Check Laravel logs: tail -f storage/logs/laravel.log\n";
} else {
    echo "❓ Unexpected response code: $httpCode\n";
}

echo "\n📋 Manual Test Command:\n";
echo "======================\n\n";
echo "curl -X POST http://localhost/api/retell/webhook \\\n";
echo "  -H 'Content-Type: application/json' \\\n";
echo "  -H 'X-Retell-Signature: $signature' \\\n";
echo "  -d '$jsonPayload'\n\n";

echo "📝 Check logs for details:\n";
echo "tail -f storage/logs/laravel.log | grep -i retell\n";