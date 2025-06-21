<?php

echo "Testing complete Retell webhook scenario...\n\n";

// Test 1: Simple call without appointment
echo "=== TEST 1: Simple call (no appointment) ===\n";
$payload1 = [
    'event' => 'call_ended',
    'call' => [
        'call_id' => sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        ),
        'agent_id' => 'agent_9a8202a740cd3120d96fcfda1e',
        'call_type' => 'inbound',
        'call_status' => 'ended',
        'start_timestamp' => (time() - 120) * 1000,
        'end_timestamp' => time() * 1000,
        'duration_ms' => 120000,
        'from_number' => '+4915234567890',
        'to_number' => '+493083793369',
        'transcript' => 'Hallo, ich möchte nur Informationen über Ihre Öffnungszeiten.',
        'call_analysis' => [
            'call_summary' => 'Kunde fragte nach Öffnungszeiten.',
            'user_sentiment' => 'positive'
        ]
    ]
];

$response1 = sendWebhook($payload1);
echo "Response: " . json_encode($response1, JSON_PRETTY_PRINT) . "\n\n";

// Test 2: Call with appointment booking
echo "=== TEST 2: Call with appointment booking ===\n";
$payload2 = [
    'event' => 'call_ended',
    'call' => [
        'call_id' => sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        ),
        'agent_id' => 'agent_9a8202a740cd3120d96fcfda1e',
        'call_type' => 'inbound',
        'call_status' => 'ended',
        'start_timestamp' => (time() - 300) * 1000,
        'end_timestamp' => time() * 1000,
        'duration_ms' => 300000,
        'from_number' => '+4915234567891',
        'to_number' => '+493083793369',
        'transcript' => 'Ich möchte einen Termin für einen Haarschnitt buchen. Am Samstag um 14 Uhr wäre gut.',
        'recording_url' => 'https://storage.retellai.com/test-recording.mp3',
        'public_log_url' => 'https://app.retellai.com/test-log',
        'retell_llm_dynamic_variables' => [
            'datum' => '2025-06-21',
            'uhrzeit' => '14:00',
            'name' => 'Maria Schmidt',
            'telefon' => '+4915234567891',
            'email' => 'maria.schmidt@example.com',
            'dienstleistung' => 'Haarschnitt',
            'filiale' => 'Berlin Mitte',
            'booking_confirmed' => true,
            'kundenwunsch' => 'Kurzhaarschnitt mit modernem Styling'
        ],
        'call_analysis' => [
            'call_summary' => 'Kundin buchte Termin für Haarschnitt am Samstag 14 Uhr.',
            'user_sentiment' => 'positive',
            'custom_analysis_data' => [
                '_name' => 'Maria Schmidt',
                '_email' => 'maria.schmidt@example.com',
                '_datum__termin' => '2025-06-21',
                '_uhrzeit__termin' => '14:00'
            ]
        ]
    ]
];

$response2 = sendWebhook($payload2);
echo "Response: " . json_encode($response2, JSON_PRETTY_PRINT) . "\n\n";

// Test 3: Different phone number (different branch)
echo "=== TEST 3: Call to different branch ===\n";
$payload3 = [
    'event' => 'call_ended',
    'call' => [
        'call_id' => sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        ),
        'agent_id' => 'agent_different_branch',
        'call_type' => 'inbound',
        'call_status' => 'ended',
        'start_timestamp' => (time() - 180) * 1000,
        'end_timestamp' => time() * 1000,
        'duration_ms' => 180000,
        'from_number' => '+4915234567892',
        'to_number' => '+493012345678', // Different branch number
        'transcript' => 'Ich hätte gerne einen Termin in Ihrer Filiale.',
        'call_analysis' => [
            'call_summary' => 'Kunde möchte Termin buchen.',
            'user_sentiment' => 'neutral'
        ]
    ]
];

$response3 = sendWebhook($payload3);
echo "Response: " . json_encode($response3, JSON_PRETTY_PRINT) . "\n\n";

// Check database
echo "=== DATABASE CHECK ===\n";
$mysqli = new mysqli('127.0.0.1', 'askproai_user', 'lkZ57Dju9EDjrMxn', 'askproai_db');

if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

// Check calls
$result = $mysqli->query("SELECT id, retell_call_id, from_number, extracted_name, created_at FROM calls ORDER BY id DESC LIMIT 3");
echo "Recent calls:\n";
while ($row = $result->fetch_assoc()) {
    echo sprintf("- Call %d: %s from %s (Name: %s) at %s\n", 
        $row['id'], 
        $row['retell_call_id'], 
        $row['from_number'],
        $row['extracted_name'] ?? 'N/A',
        $row['created_at']
    );
}

// Check appointments if any were created
$result = $mysqli->query("SELECT id, customer_id, starts_at, status FROM appointments WHERE created_at > DATE_SUB(NOW(), INTERVAL 5 MINUTE)");
echo "\nRecent appointments:\n";
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        echo sprintf("- Appointment %d: Customer %d at %s (Status: %s)\n", 
            $row['id'], 
            $row['customer_id'], 
            $row['starts_at'],
            $row['status']
        );
    }
} else {
    echo "No appointments created in the last 5 minutes.\n";
}

$mysqli->close();

function sendWebhook($payload) {
    $ch = curl_init('https://api.askproai.de/api/retell/webhook');
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Accept: application/json',
        'X-Retell-Signature: test-signature',
        'X-Retell-Timestamp: ' . time()
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode == 200) {
        return json_decode($response, true);
    } else {
        return ['error' => "HTTP $httpCode", 'response' => $response];
    }
}