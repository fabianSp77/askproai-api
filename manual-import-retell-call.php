<?php

// Manually import the call data from Retell
require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Call;
use App\Models\Company;
use App\Models\Customer;
use Carbon\Carbon;

// Set company context
$company = Company::first();
if ($company) {
    app()->bind('current_company_id', function () use ($company) {
        return $company->id;
    });
}

$apiKey = 'key_6ff998ba48e842092e04a5455d19';
$baseUrl = 'https://api.retellai.com';
$callId = 'call_ab6d2dfdd5ebb507c6c5a2f127c';

echo "Manually importing call data from Retell...\n\n";

// Get call from API
$ch = curl_init($baseUrl . '/v2/get-call/' . $callId);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $apiKey,
    'Accept: application/json'
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode != 200) {
    die("Failed to get call data. HTTP Code: $httpCode\n");
}

$callData = json_decode($response, true);

// Update existing call record
$call = Call::where('retell_call_id', $callId)->first();

if ($call) {
    echo "Updating existing call record (ID: {$call->id})...\n";
    
    $call->duration_sec = round($callData['duration_ms'] / 1000);
    $call->transcript = $callData['transcript'];
    $call->transcript_object = $callData['transcript_object'];
    $call->transcript_with_tools = $callData['transcript_with_tool_calls'];
    // $call->status = 'completed'; // Column doesn't exist
    
    // Extract summary and analysis if available
    if (isset($callData['call_analysis'])) {
        $call->summary = $callData['call_analysis']['call_summary'] ?? null;
        $call->sentiment = $callData['call_analysis']['sentiment'] ?? null;
        $call->analysis = $callData['call_analysis'];
        
        // Extract custom data
        if (isset($callData['call_analysis']['custom_analysis_data'])) {
            $customData = $callData['call_analysis']['custom_analysis_data'];
            $call->extracted_name = $customData['customer_name'] ?? null;
            $call->extracted_email = $customData['customer_email'] ?? null;
            $call->extracted_date = $customData['appointment_date'] ?? null;
            $call->extracted_time = $customData['appointment_time'] ?? null;
        }
    }
    
    $call->save();
    
    echo "\n✅ Call updated successfully!\n";
    echo "Transcript: " . substr($call->transcript, 0, 200) . "...\n";
    echo "Duration: {$call->duration_sec} seconds\n";
    echo "Summary: " . ($call->summary ?: 'N/A') . "\n";
    
} else {
    echo "Call not found in database. Creating new record...\n";
    
    // Create new call if not exists
    $call = Call::create([
        'company_id' => $company->id,
        'call_id' => $callId,
        'retell_call_id' => $callId,
        'agent_id' => $callData['agent_id'] ?? null,
        'from_number' => '+491604366218', // From your test
        'to_number' => '+493083793369',
        'direction' => 'inbound',
        'status' => 'completed',
        'start_timestamp' => Carbon::createFromTimestampMs($callData['start_timestamp']),
        'end_timestamp' => Carbon::createFromTimestampMs($callData['end_timestamp']),
        'duration_sec' => round($callData['duration_ms'] / 1000),
        'transcript' => $callData['transcript'],
        'transcript_object' => $callData['transcript_object'],
        'analysis' => $callData['call_analysis'] ?? null,
        'summary' => $callData['call_analysis']['call_summary'] ?? null,
        'sentiment' => $callData['call_analysis']['sentiment'] ?? null,
    ]);
    
    echo "\n✅ New call created with ID: {$call->id}\n";
}

echo "\nDone!\n";