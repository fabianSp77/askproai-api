<?php
require_once __DIR__ . '/vendor/autoload.php';

use App\Models\Call;
use App\Helpers\RetellDataExtractor;
use Illuminate\Support\Facades\Log;

$app = require __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "\n=== Testing Call Update Process ===\n\n";

// Find an ongoing call
$ongoingCall = Call::withoutGlobalScope(\App\Scopes\TenantScope::class)
    ->where('status', 'ongoing')
    ->orderBy('created_at', 'desc')
    ->first();

if ($ongoingCall) {
    echo "Found ongoing call: {$ongoingCall->call_id}\n";
    echo "Created: {$ongoingCall->created_at}\n";
    echo "Updated: {$ongoingCall->updated_at}\n";
    echo "Duration: {$ongoingCall->duration_sec} seconds\n";
    echo "Has transcript: " . ($ongoingCall->transcript ? 'Yes' : 'No') . "\n";
    echo "Has dynamic variables: " . ($ongoingCall->retell_dynamic_variables ? 'Yes' : 'No') . "\n";
    
    if ($ongoingCall->retell_dynamic_variables) {
        echo "Dynamic variables: " . json_encode($ongoingCall->retell_dynamic_variables) . "\n";
    }
    
    echo "\n--- Metadata ---\n";
    if ($ongoingCall->metadata) {
        echo json_encode($ongoingCall->metadata, JSON_PRETTY_PRINT) . "\n";
    } else {
        echo "No metadata\n";
    }
} else {
    echo "No ongoing calls found\n";
}

// Now let's check an ended call
echo "\n\n=== Checking Ended Call ===\n\n";

$endedCall = Call::withoutGlobalScope(\App\Scopes\TenantScope::class)
    ->where('status', 'ended')
    ->where('retell_dynamic_variables', '!=', 'null')
    ->orderBy('updated_at', 'desc')
    ->first();

if ($endedCall) {
    echo "Found ended call: {$endedCall->call_id}\n";
    echo "Created: {$endedCall->created_at}\n";
    echo "Updated: {$endedCall->updated_at}\n";
    echo "Duration: {$endedCall->duration_sec} seconds\n";
    echo "Has transcript: " . ($endedCall->transcript ? 'Yes' : 'No') . "\n";
    echo "Has dynamic variables: " . ($endedCall->retell_dynamic_variables ? 'Yes' : 'No') . "\n";
    
    if ($endedCall->retell_dynamic_variables) {
        echo "Dynamic variables: " . json_encode($endedCall->retell_dynamic_variables) . "\n";
    }
    
    echo "\n--- Call Analysis ---\n";
    if ($endedCall->analysis) {
        echo json_encode($endedCall->analysis, JSON_PRETTY_PRINT) . "\n";
    } else {
        echo "No analysis data\n";
    }
    
    echo "\n--- Customer Data Extraction ---\n";
    echo "Extracted name: " . ($endedCall->extracted_name ?: 'None') . "\n";
    echo "Extracted email: " . ($endedCall->extracted_email ?: 'None') . "\n";
    echo "Appointment made: " . ($endedCall->appointment_made ? 'Yes' : 'No') . "\n";
    echo "Appointment requested: " . ($endedCall->appointment_requested ? 'Yes' : 'No') . "\n";
}

// Test the data extraction process
echo "\n\n=== Testing Data Extraction ===\n\n";

// Simulate a call_ended webhook payload
$sampleWebhookData = [
    'event' => 'call_ended',
    'call_id' => 'test_call_123',
    'from_number' => '+491234567890',
    'to_number' => '+498976543210',
    'duration_ms' => 120000,
    'start_timestamp' => time() * 1000 - 120000,
    'end_timestamp' => time() * 1000,
    'transcript' => 'Hallo, ich möchte gerne einen Termin für morgen um 15 Uhr buchen.',
    'call_analysis' => [
        'call_summary' => 'Customer wants to book appointment for tomorrow at 3 PM',
        'user_sentiment' => 'positive',
        'call_successful' => true,
        'custom_analysis_data' => [
            'patient_full_name' => 'Max Mustermann',
            'appointment_date' => '2025-07-06',
            'appointment_time' => '15:00',
            'service_requested' => 'Beratung',
            'phone_number' => '+491234567890'
        ]
    ],
    'retell_llm_dynamic_variables' => [
        'datum' => '2025-07-06',
        'uhrzeit' => '15:00',
        'name' => 'Max Mustermann',
        'telefonnummer' => '+491234567890',
        'dienstleistung' => 'Beratung'
    ]
];

$extractedData = RetellDataExtractor::extractCallData($sampleWebhookData);

echo "Extracted fields:\n";
echo "- Name: " . ($extractedData['extracted_name'] ?: 'None') . "\n";
echo "- Date: " . ($extractedData['datum_termin'] ?: 'None') . "\n";
echo "- Time: " . ($extractedData['uhrzeit_termin'] ?: 'None') . "\n";
echo "- Service: " . ($extractedData['dienstleistung'] ?: 'None') . "\n";
echo "- Appointment requested: " . ($extractedData['appointment_requested'] ? 'Yes' : 'No') . "\n";
echo "- Dynamic variables: " . json_encode($extractedData['retell_dynamic_variables'] ?? []) . "\n";

echo "\n=== Test Complete ===\n";