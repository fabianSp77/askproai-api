<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Call;

echo "\n=== CALL DATA ANALYSIS ===\n\n";

// 1. Get sample calls
$calls = Call::latest()->limit(5)->get();

foreach ($calls as $i => $call) {
    echo "\n--- Call " . ($i + 1) . " (ID: {$call->id}) ---\n";
    echo "Created: {$call->created_at}\n";
    
    // Basic fields
    $fields = [
        'phone_number', 'from_number', 'to_number', 'call_id', 'retell_call_id',
        'duration_sec', 'call_status', 'call_type', 'transcript', 'summary',
        'analysis', 'raw_data', 'branch_id', 'company_id'
    ];
    
    foreach ($fields as $field) {
        $value = $call->$field;
        if (is_array($value) || is_object($value)) {
            echo "$field: " . (empty($value) ? "Empty array/object" : "Has data") . "\n";
        } elseif (is_string($value) && strlen($value) > 50) {
            echo "$field: " . substr($value, 0, 50) . "... (" . strlen($value) . " chars)\n";
        } else {
            echo "$field: " . ($value ?? 'NULL') . "\n";
        }
    }
    
    // Analyze raw_data
    if ($call->raw_data) {
        echo "\n  Analyzing raw_data:\n";
        $rawData = $call->raw_data;
        
        // Try to decode
        if (is_string($rawData)) {
            $decoded = json_decode($rawData, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                echo "  Successfully decoded. Keys: " . implode(', ', array_keys($decoded)) . "\n";
                
                // Show important fields
                $importantFields = ['from', 'to', 'duration', 'call_id', 'transcript', 'summary', 'agent_id'];
                foreach ($importantFields as $key) {
                    if (isset($decoded[$key])) {
                        $val = $decoded[$key];
                        if (is_string($val) && strlen($val) > 100) {
                            echo "  - $key: " . substr($val, 0, 100) . "...\n";
                        } else {
                            echo "  - $key: " . json_encode($val) . "\n";
                        }
                    }
                }
            } else {
                // Try double decode
                $decoded = json_decode(json_decode($rawData), true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    echo "  Double decoded successfully. Keys: " . implode(', ', array_keys($decoded)) . "\n";
                } else {
                    echo "  Could not decode raw_data\n";
                }
            }
        }
    }
}

// 2. Check for missing data patterns
echo "\n\n=== MISSING DATA PATTERNS ===\n";
$totalCalls = Call::count();
$missingFrom = Call::whereNull('from_number')->orWhere('from_number', '')->count();
$missingDuration = Call::whereNull('duration_sec')->orWhere('duration_sec', 0)->count();
$missingTranscript = Call::whereNull('transcript')->orWhere('transcript', '')->count();
$missingAnalysis = Call::whereJsonLength('analysis', 0)->count();

echo "Total calls: $totalCalls\n";
echo "Missing from_number: $missingFrom (" . round($missingFrom/$totalCalls*100) . "%)\n";
echo "Missing duration: $missingDuration (" . round($missingDuration/$totalCalls*100) . "%)\n";
echo "Missing transcript: $missingTranscript (" . round($missingTranscript/$totalCalls*100) . "%)\n";
echo "Missing analysis: $missingAnalysis (" . round($missingAnalysis/$totalCalls*100) . "%)\n";

// 3. Check Retell webhook structure
echo "\n\n=== RETELL WEBHOOK STRUCTURE ===\n";
$latestWebhook = \App\Models\RetellWebhook::latest()->first();
if ($latestWebhook) {
    echo "Latest webhook received: {$latestWebhook->created_at}\n";
    if ($latestWebhook->payload) {
        $payload = is_string($latestWebhook->payload) ? json_decode($latestWebhook->payload, true) : $latestWebhook->payload;
        echo "Payload keys: " . implode(', ', array_keys($payload ?? [])) . "\n";
    }
} else {
    echo "No webhooks found in retell_webhooks table\n";
}

echo "\n=== ANALYSIS COMPLETE ===\n";