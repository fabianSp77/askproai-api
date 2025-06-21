<?php

// Test webhook with debug mode active
$webhookUrl = 'https://api.askproai.de/api/retell/webhook';

// Create a realistic test payload with valid UUID
$callId = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
    mt_rand(0, 0xffff), mt_rand(0, 0xffff),
    mt_rand(0, 0xffff),
    mt_rand(0, 0x0fff) | 0x4000,
    mt_rand(0, 0x3fff) | 0x8000,
    mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
);
$payload = [
    'event' => 'call_ended',
    'call' => [
        'call_id' => $callId,
        'agent_id' => 'agent_9a8202a740cd3120d96fcfda1e',
        'call_type' => 'inbound',
        'call_status' => 'ended',
        'start_timestamp' => (time() - 180) * 1000, // 3 minutes ago
        'end_timestamp' => time() * 1000,
        'duration_ms' => 180000, // 3 minutes
        'from_number' => '+4915234567890',
        'to_number' => '+493083793369',
        'transcript' => 'Test webhook nach Aktivierung der Events',
        'call_analysis' => [
            'call_summary' => 'Test call to verify webhook processing',
            'call_successful' => true,
            'custom_analysis_data' => [
                '_name' => 'Test User',
                '_datum__termin' => date('Ymd', strtotime('+1 day')),
                '_uhrzeit__termin' => 14,
                '_zusammenfassung__anruf' => 'Test webhook verification'
            ]
        ],
        'disconnect_reason' => 'user_hangup',
        'cost' => 0.50
    ]
];

$jsonPayload = json_encode($payload);

echo "Sending test webhook...\n";
echo "URL: $webhookUrl\n";
echo "Call ID: $callId\n\n";

// Send with minimal headers (APP_DEBUG is true, so signature check is skipped)
$ch = curl_init($webhookUrl);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonPayload);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Accept: application/json',
    'X-Test-Webhook: true'
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "Response Code: $httpCode\n";

if ($httpCode == 200) {
    echo "✅ SUCCESS! Webhook accepted.\n";
    
    $responseData = json_decode($response, true);
    if (isset($responseData['success']) && $responseData['success']) {
        echo "Message: " . ($responseData['message'] ?? 'Processed') . "\n";
        if (isset($responseData['correlation_id'])) {
            echo "Correlation ID: " . $responseData['correlation_id'] . "\n";
        }
    }
    
    echo "\nChecking database for the test call...\n";
    
    // Check if call was saved
    sleep(2); // Give it time to process
    
    require_once 'vendor/autoload.php';
    $app = require_once 'bootstrap/app.php';
    $kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
    $kernel->bootstrap();
    
    $call = \App\Models\Call::where('call_id', $callId)->first();
    if ($call) {
        echo "✅ Call found in database!\n";
        echo "   ID: " . $call->id . "\n";
        echo "   Status: " . $call->status . "\n";
        echo "   From: " . $call->from_number . "\n";
    } else {
        echo "❌ Call not found in database yet.\n";
    }
} else {
    echo "❌ FAILED! Response: " . substr($response, 0, 500) . "\n";
}