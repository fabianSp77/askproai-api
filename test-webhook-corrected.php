<?php

// Test webhook with correct data format
$webhookUrl = 'https://api.askproai.de/api/retell/webhook';
$apiKey = 'key_6ff998ba48e842092e04a5455d19';

// Generate a valid UUID
$callId = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
    mt_rand(0, 0xffff), mt_rand(0, 0xffff),
    mt_rand(0, 0xffff),
    mt_rand(0, 0x0fff) | 0x4000,
    mt_rand(0, 0x3fff) | 0x8000,
    mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
);

// Create a test payload with valid data
$payload = [
    'event' => 'call_ended',
    'call' => [
        'call_id' => $callId,
        'agent_id' => 'agent_9a8202a740cd3120d96fcfda1e',
        'call_type' => 'inbound',
        'call_status' => 'ended',
        'start_timestamp' => (time() - 300) * 1000, // 5 minutes ago
        'end_timestamp' => time() * 1000,
        'disconnect_reason' => 'user_hangup',
        'duration_ms' => 300000, // 5 minutes
        'from_number' => '+4915234567890',
        'to_number' => '+493083793369',
        'transcript' => 'Hallo, ich möchte einen Termin buchen.',
        'transcript_object' => [],
        'recording_url' => 'https://storage.retellai.com/recordings/' . $callId . '.mp3',
        'public_log_url' => 'https://app.retellai.com/logs/' . $callId,
        'e2e_latency' => null,
        'llm_latency' => null,
        'llm_websocket_network_rtt_latencies' => [],
        'cost' => 0.25,
        'retell_llm_dynamic_variables' => [
            'datum' => '2025-06-22',
            'uhrzeit' => '14:00',
            'name' => 'Max Mustermann',
            'telefon' => '+4915234567890',
            'email' => 'max@example.com',
            'dienstleistung' => 'Haarschnitt',
            'booking_confirmed' => true
        ],
        'call_analysis' => [
            'call_summary' => 'Kunde möchte einen Termin für einen Haarschnitt buchen.',
            'user_sentiment' => 'positive',
            'custom_analysis_data' => [
                '_name' => 'Max Mustermann',
                '_email' => 'max@example.com',
                '_datum__termin' => '2025-06-22',
                '_uhrzeit__termin' => '14:00'
            ]
        ]
    ]
];

// Encode JSON without escaping slashes
$jsonPayload = json_encode($payload, JSON_UNESCAPED_SLASHES);
$timestamp = (string)time();

// Create signature payload as timestamp.body
$signaturePayload = $timestamp . '.' . $jsonPayload;

// Calculate HMAC-SHA256 signature
$signature = hash_hmac('sha256', $signaturePayload, $apiKey);

echo "Testing Retell webhook with valid data...\n";
echo "Webhook URL: $webhookUrl\n";
echo "Call ID: $callId\n";
echo "Timestamp: $timestamp\n";
echo "Signature: $signature\n\n";

// Send the request
$ch = curl_init($webhookUrl);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonPayload);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Accept: application/json',
    'X-Retell-Signature: ' . $signature,
    'X-Retell-Timestamp: ' . $timestamp,
    'User-Agent: Retell-Webhook/1.0'
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

echo "Response Code: $httpCode\n";
if ($curlError) {
    echo "CURL Error: $curlError\n";
}

if ($httpCode == 200 || $httpCode == 201) {
    echo "✅ SUCCESS! Webhook accepted\n";
    $responseData = json_decode($response, true);
    echo "Response: " . json_encode($responseData, JSON_PRETTY_PRINT) . "\n";
} else {
    echo "❌ FAILED!\n";
    echo "Response: " . $response . "\n";
    
    // Try to decode error response
    $errorData = json_decode($response, true);
    if ($errorData) {
        echo "\nError details:\n";
        echo json_encode($errorData, JSON_PRETTY_PRINT) . "\n";
    }
}

// Also test without signature to see difference
echo "\n\n--- Testing without signature (should fail) ---\n";
$ch = curl_init($webhookUrl);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonPayload);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Accept: application/json',
    'User-Agent: Retell-Webhook/1.0'
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "Response Code without signature: $httpCode\n";
if ($httpCode == 401) {
    echo "✅ CORRECT: Webhook rejected without signature\n";
} else {
    echo "⚠️  WARNING: Expected 401, got $httpCode\n";
}