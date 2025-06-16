<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Call;

echo "\n=== FIXING CALL DATA ===\n\n";

// Get calls with raw_data
$calls = Call::whereNotNull('raw_data')->get();
$updated = 0;
$failed = 0;

foreach ($calls as $call) {
    echo "Processing Call ID: {$call->id}...\n";
    
    $rawData = $call->raw_data;
    $decoded = null;
    
    // Try to decode
    if (is_string($rawData)) {
        // First decode
        $firstDecode = json_decode($rawData, true);
        
        if (json_last_error() === JSON_ERROR_NONE) {
            // If it's a string, it's double encoded
            if (is_string($firstDecode)) {
                $decoded = json_decode($firstDecode, true);
            } else {
                $decoded = $firstDecode;
            }
        }
    }
    
    if ($decoded && is_array($decoded)) {
        // Extract data from decoded array
        $updates = [];
        
        // Map fields
        if (isset($decoded['from']) && empty($call->from_number)) {
            $updates['from_number'] = $decoded['from'];
        }
        
        if (isset($decoded['to']) && empty($call->to_number)) {
            $updates['to_number'] = $decoded['to'];
        }
        
        if (isset($decoded['duration']) && empty($call->duration_sec)) {
            $updates['duration_sec'] = $decoded['duration'];
        }
        
        if (isset($decoded['transcript']) && empty($call->transcript)) {
            $updates['transcript'] = $decoded['transcript'];
        }
        
        if (isset($decoded['summary']) && empty($call->summary)) {
            $updates['summary'] = $decoded['summary'];
        }
        
        // Fix raw_data to be properly encoded (not double encoded)
        $updates['raw_data'] = json_encode($decoded);
        
        // Update analysis if we have transcript
        if (!empty($decoded['transcript']) && empty($call->analysis)) {
            $updates['analysis'] = [
                'sentiment' => 'neutral',
                'entities' => [],
                'important_phrases' => []
            ];
        }
        
        if (!empty($updates)) {
            try {
                Call::where('id', $call->id)->update($updates);
                echo "  ✓ Updated with: " . implode(', ', array_keys($updates)) . "\n";
                $updated++;
            } catch (\Exception $e) {
                echo "  ✗ Failed: " . $e->getMessage() . "\n";
                $failed++;
            }
        } else {
            echo "  - No updates needed\n";
        }
    } else {
        echo "  ✗ Could not decode raw_data\n";
        $failed++;
    }
}

echo "\n=== SUMMARY ===\n";
echo "Total processed: " . $calls->count() . "\n";
echo "Updated: $updated\n";
echo "Failed: $failed\n";

// Show sample of fixed data
echo "\n=== SAMPLE FIXED DATA ===\n";
$sample = Call::latest()->first();
if ($sample) {
    echo "Call ID: {$sample->id}\n";
    echo "From: {$sample->from_number}\n";
    echo "To: {$sample->to_number}\n";
    echo "Duration: {$sample->duration_sec}s\n";
    echo "Transcript: " . ($sample->transcript ? substr($sample->transcript, 0, 50) . "..." : "NULL") . "\n";
    echo "Summary: {$sample->summary}\n";
}