<?php

// Test the debug webhook endpoint (no signature required)
$webhookUrl = 'https://api.askproai.de/api/retell/debug-webhook';

// Create a test payload
$payload = [
    'event' => 'call_ended',
    'call' => [
        'call_id' => 'test-' . uniqid(),
        'agent_id' => 'agent_9a8202a740cd3120d96fcfda1e',
        'call_type' => 'inbound',
        'call_status' => 'ended',
        'start_timestamp' => (time() - 120) * 1000,
        'end_timestamp' => time() * 1000,
        'duration_ms' => 120000,
        'from_number' => '+4915234567890',
        'to_number' => '+493083793369',
        'transcript' => 'Test call via debug endpoint',
        'transcript_object' => [],
        'cost' => 0.25,
        'retell_llm_dynamic_variables' => [
            'booking_confirmed' => true,
            'datum' => '2025-06-20',
            'uhrzeit' => '14:00',
            'dienstleistung' => 'Test Service',
            'kundenwunsch' => 'Test booking via debug webhook'
        ]
    ]
];

$jsonPayload = json_encode($payload);

echo "Testing debug webhook endpoint (no signature required)...\n";
echo "URL: $webhookUrl\n\n";

// Send request
$ch = curl_init($webhookUrl);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonPayload);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Accept: application/json'
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "Response Code: $httpCode\n";

if ($httpCode == 200 || $httpCode == 201) {
    echo "✅ SUCCESS! Debug webhook accepted\n";
    echo "Response: " . json_encode(json_decode($response), JSON_PRETTY_PRINT) . "\n";
} else {
    echo "❌ FAILED! Response: " . substr($response, 0, 500) . "\n";
}