<?php

// Create a test appointment through the webhook flow

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Http\Controllers\RetellEnhancedWebhookController;
use Illuminate\Http\Request;

echo "=== Creating Test Appointment ===\n\n";

// Simulate a webhook with booking data
$webhookData = [
    'event' => 'call_ended',
    'call' => [
        'call_id' => 'test-appointment-' . time(),
        'agent_id' => 'agent_9a8202a740cd3120d96fc27bb40b2c',
        'from_number' => '+491234567890',
        'to_number' => '+493083793369',
        'direction' => 'inbound',
        'duration_ms' => 180000, // 3 minutes
        'cost' => 0.30,
        'status' => 'ended',
        'transcript' => 'Kunde: Hallo, ich möchte einen Termin für einen Haarschnitt buchen. Agent: Gerne, wann hätten Sie Zeit? Kunde: Am Dienstag um 14 Uhr wäre gut. Agent: Perfekt, ich habe Sie für Dienstag, 25. Juni um 14 Uhr eingetragen.',
        'summary' => 'Kunde bucht Haarschnitt-Termin für Dienstag 14 Uhr',
        'end_timestamp' => time() * 1000,
        'start_timestamp' => (time() - 180) * 1000,
        'call_analysis' => [
            'custom_analysis_data' => [
                '_name' => 'Max Mustermann',
                '_datum__termin' => '2025-06-25',
                '_uhrzeit__termin' => '14:00'
            ]
        ],
        'retell_llm_dynamic_variables' => [
            'booking_confirmed' => 'true',
            'datum' => '2025-06-25',
            'uhrzeit' => '14:00',
            'name' => 'Max Mustermann',
            'service' => 'Haarschnitt'
        ]
    ]
];

// Create request
$request = Request::create('/api/retell/enhanced-webhook', 'POST', $webhookData);
$request->headers->set('Content-Type', 'application/json');

// Add signature for the request
$webhookSecret = 'key_6ff998ba48e842092e04a5455d19';
$timestamp = (string)time();
$jsonPayload = json_encode($webhookData);
$signaturePayload = $timestamp . '.' . $jsonPayload;
$signature = hash_hmac('sha256', $signaturePayload, $webhookSecret);

$request->headers->set('X-Retell-Signature', $signature);
$request->headers->set('X-Retell-Timestamp', $timestamp);

// Process webhook
$controller = new RetellEnhancedWebhookController(new \App\Services\PhoneNumberResolver());

echo "Processing webhook...\n";

try {
    $response = $controller->handle($request);
    $responseData = json_decode($response->getContent(), true);
    
    if ($response->getStatusCode() === 200) {
        echo "✅ Webhook processed successfully!\n";
        print_r($responseData);
        
        // Check if appointment was created
        if (isset($responseData['appointment_created']) && $responseData['appointment_created']) {
            echo "\n✅ APPOINTMENT CREATED!\n";
        } else {
            echo "\n❌ Appointment was NOT created\n";
            echo "Checking why...\n";
            
            // Get the call to see what data was extracted
            if (isset($responseData['call_id'])) {
                $call = \App\Models\Call::withoutGlobalScope(\App\Scopes\TenantScope::class)
                    ->find($responseData['call_id']);
                
                if ($call) {
                    echo "\nCall data:\n";
                    echo "  - Extracted Date: " . ($call->extracted_date ?: 'NONE') . "\n";
                    echo "  - Extracted Time: " . ($call->extracted_time ?: 'NONE') . "\n";
                    echo "  - Dynamic Vars: " . $call->retell_dynamic_variables . "\n";
                }
            }
        }
    } else {
        echo "❌ Webhook processing failed!\n";
        echo "Response: " . $response->getContent() . "\n";
    }
} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
}