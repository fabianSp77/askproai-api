<?php

// Test the fixed webhook endpoint
$webhookUrl = 'https://api.askproai.de/api/retell/debug-webhook';

// Simulate a call_ended webhook
$testData = [
    'event' => 'call_ended',
    'call' => [
        'call_id' => 'test_call_' . time(),
        'agent_id' => 'agent_9a8202a740cd3120d96fcfda1e',
        'from_number' => '+491604366218',
        'to_number' => '+493083793369',
        'call_type' => 'inbound',
        'call_status' => 'ended',
        'start_timestamp' => (time() - 60) * 1000, // 1 minute ago
        'end_timestamp' => time() * 1000,
        'duration_ms' => 60000,
        'cost' => 150, // $1.50 in cents
        'transcript' => 'Test transcript: Customer called to book an appointment.',
        'call_analysis' => [
            'call_summary' => 'Customer requested appointment for next week',
            'sentiment' => 'positive',
            'custom_analysis_data' => [
                '_name' => 'Test Customer',
                '_email' => 'test@example.com',
                '_datum__termin' => '2025-06-26',
                '_uhrzeit__termin' => '14:00'
            ]
        ],
        'recording_url' => 'https://example.com/recording.mp3',
        'public_log_url' => 'https://example.com/log'
    ]
];

echo "Testing webhook endpoint: $webhookUrl\n\n";

$ch = curl_init($webhookUrl);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($testData));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Accept: application/json'
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Status Code: $httpCode\n";
echo "Response: " . $response . "\n\n";

if ($httpCode === 200) {
    echo "✅ SUCCESS! Webhook processed correctly.\n";
    echo "The endpoint now returns 200 even if there are processing errors.\n";
    echo "This should allow Retell to send subsequent webhooks.\n";
} else {
    echo "❌ FAILED! Got status code $httpCode\n";
    echo "This will prevent Retell from sending call_ended webhooks.\n";
}

echo "\nNow you can make a new test call and the call_ended webhook should be received!\n";