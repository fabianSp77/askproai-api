<?php

// Test webhook with IP whitelist
$webhookUrl = 'https://api.askproai.de/api/retell/webhook';

// Test payload
$payload = [
    'event' => 'call_ended',
    'call' => [
        'call_id' => '8fe67ef8-3cd7-37cc-4e6b-96be08d' . rand(10000, 99999),
        'agent_id' => 'agent_9a8202a740cd3120d96fcfda1e',
        'call_type' => 'inbound',
        'call_status' => 'ended',
        'start_timestamp' => (time() - 120) * 1000,
        'end_timestamp' => time() * 1000,
        'duration_ms' => 120000,
        'from_number' => '+4915234567890',
        'to_number' => '+493083793369',
        'transcript' => 'Test webhook to verify processing',
        'call_analysis' => [
            'call_summary' => 'Manual test webhook',
            'call_successful' => true
        ]
    ]
];

$jsonPayload = json_encode($payload);

echo "Testing webhook endpoint...\n";
echo "URL: $webhookUrl\n\n";

// Send without signature (relying on IP whitelist)
$ch = curl_init($webhookUrl);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonPayload);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Accept: application/json',
    'X-Forwarded-For: 152.53.228.178', // Simulate Retell IP
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "Response Code: $httpCode\n";
echo "Response: " . substr($response, 0, 500) . "\n";

if ($httpCode == 200) {
    echo "\n✅ Webhook accepted! Check the database for the new call record.\n";
} else {
    echo "\n❌ Webhook failed.\n";
}