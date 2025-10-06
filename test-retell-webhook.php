<?php
// Test Retell Webhook Connection
// Run: php test-retell-webhook.php

echo "🧪 RETELL WEBHOOK TEST SCRIPT\n";
echo "============================\n\n";

// Test webhook endpoint
$webhookUrl = 'https://api.askproai.de/api/webhooks/retell';

echo "Testing webhook URL: $webhookUrl\n\n";

// Create test payload similar to Retell
$testPayload = [
    'event' => 'test_webhook',
    'event_type' => 'test',
    'call_id' => 'test_' . time(),
    'timestamp' => time(),
    'test' => true,
    'message' => 'Manual test from server at ' . date('Y-m-d H:i:s')
];

// Test local connection first
echo "1. Testing local connection...\n";
$ch = curl_init($webhookUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($testPayload));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'X-Test-Webhook: true'
]);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // For testing only

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

if ($error) {
    echo "❌ CURL Error: $error\n";
} else {
    echo "HTTP Status: $httpCode\n";
    echo "Response: $response\n";

    if ($httpCode === 200 || $httpCode === 204) {
        echo "✅ Webhook endpoint is reachable!\n";
    } elseif ($httpCode === 401 || $httpCode === 403) {
        echo "⚠️ Authentication issue - Retell signature validation might be blocking\n";
    } elseif ($httpCode === 404) {
        echo "❌ Webhook endpoint not found - URL might be wrong\n";
    } else {
        echo "❌ Unexpected response code: $httpCode\n";
    }
}

echo "\n2. Checking recent webhook logs...\n";

// Check if any webhooks were logged
require_once '/var/www/api-gateway/vendor/autoload.php';
$app = require_once '/var/www/api-gateway/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

use App\Models\WebhookEvent;
use Carbon\Carbon;

$recent = WebhookEvent::where('created_at', '>=', Carbon::now()->subMinutes(5))
    ->orderBy('created_at', 'desc')
    ->first();

if ($recent) {
    echo "✅ Found recent webhook: " . $recent->created_at . "\n";
} else {
    echo "❌ No recent webhooks in database\n";
}

echo "\n📝 NEXT STEPS:\n";
echo "=============\n";
echo "1. Check Retell Dashboard webhook configuration\n";
echo "2. Use the Test Webhook button in Retell\n";
echo "3. Check firewall/security rules\n";
echo "4. Verify SSL certificate is valid\n";