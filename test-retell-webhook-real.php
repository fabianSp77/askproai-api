<?php

// Test Retell webhook with real call data
// Try enhanced webhook endpoint
$webhookUrl = 'https://api.askproai.de/api/retell/enhanced-webhook';
$webhookSecret = 'key_6ff998ba48e842092e04a5455d19';

// Real call data structure from Retell
$payload = [
    'event' => 'call_ended',
    'call' => [
        'call_id' => 'appointment-test-' . uniqid(), // Unique ID
        'agent_id' => 'agent_9a8202a740cd3120d96fc27bb40b2c',
        'from_number' => '+491234567890',
        'to_number' => '+493083793369',
        'direction' => 'inbound',
        'duration_seconds' => 120,
        'cost' => 0.25,
        'status' => 'ended',
        'transcript' => 'Kunde: Hallo, ich möchte einen Termin buchen. Agent: Gerne, wann hätten Sie Zeit?',
        'summary' => 'Kunde möchte Termin buchen',
        'end_at' => time() * 1000,
        'start_timestamp' => (time() - 120) * 1000,
        'metadata' => [
            'customer_name' => 'Test Kunde',
            'requested_date' => '2025-06-25',
            'requested_time' => '14:00',
            'service' => 'Haarschnitt'
        ],
        'call_analysis' => [
            'custom_analysis_data' => [
                '_name' => 'Test Kunde MCP',
                '_datum__termin' => '2025-06-25',
                '_uhrzeit__termin' => '14:00'
            ]
        ],
        'retell_llm_dynamic_variables' => [
            'booking_confirmed' => 'true',
            'datum' => '2025-06-25',
            'uhrzeit' => '14:00',
            'name' => 'Test Kunde MCP',
            'dienstleistung' => 'Haarschnitt'
        ]
    ]
];

$jsonPayload = json_encode($payload);
$timestamp = (string)time();

// Create signature (Method 1: timestamp.payload)
$signaturePayload = $timestamp . '.' . $jsonPayload;
$signature = hash_hmac('sha256', $signaturePayload, $webhookSecret);

echo "=== Testing Retell Webhook with Real Data ===\n\n";
echo "Webhook URL: $webhookUrl\n";
echo "Call ID: " . $payload['call']['call_id'] . "\n";
echo "Timestamp: $timestamp\n";
echo "Signature: " . substr($signature, 0, 20) . "...\n\n";

// Send request
$ch = curl_init($webhookUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonPayload);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'X-Retell-Signature: ' . $signature,
    'X-Retell-Timestamp: ' . $timestamp,
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "Response Code: $httpCode\n";
echo "Response: $response\n\n";

if ($httpCode === 200 || $httpCode === 201) {
    echo "✅ SUCCESS! Webhook processed successfully\n";
    
    // Check if call was saved
    echo "\nChecking database for saved call...\n";
    $db = new PDO('mysql:host=127.0.0.1;dbname=askproai_db', 'askproai_user', 'lkZ57Dju9EDjrMxn');
    $stmt = $db->prepare("SELECT * FROM calls WHERE retell_call_id = ? ORDER BY created_at DESC LIMIT 1");
    $stmt->execute([$payload['call']['call_id']]);
    $call = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($call) {
        echo "✅ Call found in database!\n";
        echo "  - ID: " . $call['id'] . "\n";
        echo "  - Company ID: " . $call['company_id'] . "\n";
        echo "  - Branch ID: " . $call['branch_id'] . "\n";
        echo "  - Duration: " . $call['duration_seconds'] . " seconds\n";
    } else {
        echo "❌ Call not found in database\n";
    }
} else {
    echo "❌ FAILED! \n";
    
    // Try to decode error
    $decoded = json_decode($response, true);
    if ($decoded) {
        echo "Error details:\n";
        print_r($decoded);
    }
}