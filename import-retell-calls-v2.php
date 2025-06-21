<?php

require_once 'vendor/autoload.php';

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use App\Models\Call;
use App\Models\Company;

// Initialize Laravel application
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$apiKey = config('services.retell.api_key');
$baseUrl = rtrim(config('services.retell.base', 'https://api.retellai.com'), '/');

echo "Importing Retell Calls...\n";
echo "API Base URL: $baseUrl\n";
echo "API Key: " . substr($apiKey, 0, 10) . "...\n\n";

// Get the first company
$company = Company::first();
if (!$company) {
    die("No company found in database!\n");
}

echo "Using company: {$company->name} (ID: {$company->id})\n\n";

// Try to fetch calls using v2 API with POST method
echo "Fetching calls from Retell API...\n";

try {
    // The Retell v2 API uses POST for list operations
    $response = Http::withHeaders([
        'Authorization' => 'Bearer ' . $apiKey,
        'Content-Type' => 'application/json',
        'Accept' => 'application/json'
    ])->post($baseUrl . '/v2/list-calls', [
        'limit' => 50,
        'sort_order' => 'descending'
    ]);
    
    echo "Response status: " . $response->status() . "\n";
    
    if ($response->successful()) {
        $data = $response->json();
        $calls = $data['calls'] ?? $data;
        
        if (!is_array($calls)) {
            echo "Unexpected response format\n";
            echo "Response: " . json_encode($data) . "\n";
            exit(1);
        }
        
        echo "Found " . count($calls) . " calls\n\n";
        
        $imported = 0;
        $skipped = 0;
        
        foreach ($calls as $callData) {
            $callId = $callData['call_id'] ?? null;
            if (!$callId) {
                echo "Skipping call without ID\n";
                continue;
            }
            
            // Check if call already exists
            if (Call::where('call_id', $callId)->orWhere('retell_call_id', $callId)->exists()) {
                echo "Call $callId already exists - skipping\n";
                $skipped++;
                continue;
            }
            
            // Map the data to our Call model
            $callRecord = [
                'company_id' => $company->id,
                'call_id' => $callId,
                'retell_call_id' => $callId,
                'agent_id' => $callData['agent_id'] ?? null,
                'from_number' => $callData['from_number'] ?? null,
                'to_number' => $callData['to_number'] ?? null,
                'direction' => $callData['call_type'] ?? 'inbound',
                'status' => $callData['call_status'] ?? 'completed',
                'start_timestamp' => isset($callData['start_timestamp']) 
                    ? \Carbon\Carbon::createFromTimestampMs($callData['start_timestamp']) 
                    : null,
                'end_timestamp' => isset($callData['end_timestamp']) 
                    ? \Carbon\Carbon::createFromTimestampMs($callData['end_timestamp']) 
                    : null,
                'duration_sec' => isset($callData['duration_ms']) 
                    ? round($callData['duration_ms'] / 1000) 
                    : null,
                'cost' => isset($callData['cost']) ? $callData['cost'] / 100 : 0, // Convert cents to euros
                'transcript' => $callData['transcript'] ?? null,
                'summary' => $callData['call_analysis']['call_summary'] ?? null,
                'sentiment' => $callData['call_analysis']['sentiment'] ?? null,
                'intent' => $callData['call_analysis']['intent'] ?? null,
                'call_successful' => $callData['call_analysis']['call_successful'] ?? null,
                'appointment_requested' => $callData['call_analysis']['appointment_requested'] ?? false,
                'audio_url' => $callData['recording_url'] ?? null,
                'public_log_url' => $callData['public_log_url'] ?? null,
                'transcript_object' => $callData['transcript_object'] ?? null,
                'analysis' => $callData['call_analysis'] ?? null,
            ];
            
            // Extract customer info if available
            if (isset($callData['call_analysis']['extracted_info'])) {
                $extracted = $callData['call_analysis']['extracted_info'];
                $callRecord['extracted_name'] = $extracted['name'] ?? null;
                $callRecord['extracted_email'] = $extracted['email'] ?? null;
                $callRecord['extracted_date'] = $extracted['date'] ?? null;
                $callRecord['extracted_time'] = $extracted['time'] ?? null;
                $callRecord['extracted_service'] = $extracted['service'] ?? null;
            }
            
            try {
                $call = Call::create($callRecord);
                echo "✅ Imported call: $callId\n";
                echo "   From: {$call->from_number}\n";
                echo "   To: {$call->to_number}\n";
                echo "   Date: " . ($call->start_timestamp ? $call->start_timestamp->format('Y-m-d H:i:s') : 'N/A') . "\n";
                echo "   Duration: {$call->duration_sec} seconds\n";
                if ($call->summary) {
                    echo "   Summary: " . substr($call->summary, 0, 50) . "...\n";
                }
                echo "\n";
                $imported++;
            } catch (\Exception $e) {
                echo "❌ Failed to import call $callId: " . $e->getMessage() . "\n";
            }
        }
        
        echo "\nImport complete!\n";
        echo "Imported: $imported calls\n";
        echo "Skipped: $skipped calls (already exist)\n";
        
    } else {
        echo "Failed to fetch calls from Retell API\n";
        echo "Response: " . $response->body() . "\n";
    }
    
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}