<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Call;

echo "\n=== ANALYZING RETELL WEBHOOK DATA ===\n";

$calls = Call::whereNotNull('raw_data')->latest()->limit(50)->get();

if ($calls->isEmpty()) {
    echo "No calls with raw data found.\n";
    exit;
}

$fieldFrequency = [];
$hasToNumber = 0;
$hasAgentId = 0;
$hasMetadata = 0;

foreach ($calls as $call) {
    $rawData = $call->raw_data;
    
    // Try to decode JSON
    if (is_string($rawData)) {
        $data = json_decode($rawData, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            echo "Failed to decode JSON for call {$call->id}\n";
            continue;
        }
    } else {
        $data = $rawData;
    }
    
    if (!$data || !is_array($data)) {
        echo "Invalid data for call {$call->id}\n";
        continue;
    }
    
    // Track all fields
    foreach ($data as $key => $value) {
        $fieldFrequency[$key] = ($fieldFrequency[$key] ?? 0) + 1;
    }
    
    // Check specific fields
    if (isset($data['to_number'])) $hasToNumber++;
    if (isset($data['agent_id'])) $hasAgentId++;
    if (isset($data['metadata'])) $hasMetadata++;
}

echo "\n=== FIELD FREQUENCY (from {$calls->count()} calls) ===\n";
arsort($fieldFrequency);
foreach ($fieldFrequency as $field => $count) {
    $percentage = round(($count / $calls->count()) * 100);
    printf("%-30s: %d (%d%%)\n", $field, $count, $percentage);
}

echo "\n=== CRITICAL FIELDS ===\n";
echo "to_number found in: {$hasToNumber}/{$calls->count()} calls\n";
echo "agent_id found in: {$hasAgentId}/{$calls->count()} calls\n";
echo "metadata found in: {$hasMetadata}/{$calls->count()} calls\n";

// Show sample data
echo "\n=== SAMPLE WEBHOOK DATA ===\n";
$sampleCall = $calls->first();
echo "Raw data type: " . gettype($sampleCall->raw_data) . "\n";

// Double decode if needed
$sampleData = $sampleCall->raw_data;
if (is_string($sampleData)) {
    $decoded = json_decode($sampleData, true);
    if (is_string($decoded)) {
        // Double encoded!
        $decoded = json_decode($decoded, true);
    }
    $sampleData = $decoded;
}

echo json_encode($sampleData, JSON_PRETTY_PRINT) . "\n";

// Check if we have to/from in the double-decoded data
if (isset($sampleData['to'])) {
    echo "\nFOUND 'to' field: " . $sampleData['to'] . "\n";
}
if (isset($sampleData['from'])) {
    echo "FOUND 'from' field: " . $sampleData['from'] . "\n";
}

// Check branches with agents
echo "\n=== BRANCHES WITH RETELL AGENTS ===\n";
$branches = \App\Models\Branch::whereNotNull('retell_agent_id')->get();
foreach ($branches as $branch) {
    echo "Branch: {$branch->name} (ID: {$branch->id})\n";
    echo "  Agent ID: {$branch->retell_agent_id}\n";
    echo "  Phone: {$branch->phone_number}\n\n";
}