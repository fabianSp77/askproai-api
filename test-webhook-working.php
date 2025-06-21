<?php

// Test webhook with signature bypass
$webhookUrl = 'https://api.askproai.de/api/test/webhook';

// Create a test payload with booking confirmation
$payload = [
    'event' => 'call_ended',
    'call' => [
        'call_id' => '550e8400-e29b-41d4-a716-' . substr(uniqid(), 0, 12),
        'agent_id' => 'agent_9a8202a740cd3120d96fcfda1e',
        'call_type' => 'inbound',
        'call_status' => 'ended',
        'start_timestamp' => (time() - 120) * 1000,
        'end_timestamp' => time() * 1000,
        'duration_ms' => 120000,
        'from_number' => '+491601234567',
        'to_number' => '+493083793369',
        'transcript' => 'Hallo, ich möchte gerne einen Termin für morgen um 14 Uhr buchen.',
        'transcript_object' => [],
        'cost' => 0.25,
        'disconnection_reason' => 'user_hangup',
        'call_analysis' => [
            'call_summary' => 'Kunde möchte Termin für morgen 14 Uhr',
            'call_successful' => true,
            'custom_analysis_data' => [
                '_name' => 'Max Mustermann',
                '_email' => 'max@example.com',
                '_datum__termin' => '2025-06-20',
                '_uhrzeit__termin' => '14:00',
                '_zusammenfassung__anruf' => 'Terminbuchung für morgen'
            ]
        ],
        'retell_llm_dynamic_variables' => [
            'booking_confirmed' => true,
            'datum' => '2025-06-20',
            'uhrzeit' => '14:00',
            'name' => 'Max Mustermann',
            'email' => 'max@example.com',
            'dienstleistung' => 'Beratungsgespräch',
            'kundenwunsch' => 'Möchte Beratung zu Versicherungen'
        ]
    ]
];

$jsonPayload = json_encode($payload);
$timestamp = time();

echo "Testing webhook with bypass signature verification...\n";
echo "URL: $webhookUrl\n\n";

// Create signature (even though it's bypassed)
$signaturePayload = $timestamp . '.' . $jsonPayload;
$signature = hash_hmac('sha256', $signaturePayload, 'dummy_key');

// Send request
$ch = curl_init($webhookUrl);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonPayload);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Accept: application/json',
    'X-Retell-Signature: ' . $signature,
    'X-Retell-Timestamp: ' . $timestamp
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "Response Code: $httpCode\n";

if ($httpCode == 200 || $httpCode == 201) {
    echo "✅ SUCCESS! Webhook accepted\n";
    $responseData = json_decode($response, true);
    echo "Response: " . json_encode($responseData, JSON_PRETTY_PRINT) . "\n\n";
    
    // Check if call was created
    echo "Checking database for created call...\n";
    exec("mysql -h localhost -u askproai_user -p'lkZ57Dju9EDjrMxn' askproai_db -e \"SELECT id, retell_call_id, extracted_name, extracted_date, extracted_time FROM calls WHERE retell_call_id = '{$payload['call']['call_id']}' LIMIT 1\" 2>&1", $output);
    foreach ($output as $line) {
        echo $line . "\n";
    }
} else {
    echo "❌ FAILED! Response: " . substr($response, 0, 500) . "\n";
}