<?php
require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== COMPLETE CALL DATA ANALYSIS ===\n";
echo str_repeat("=", 50) . "\n\n";

// Get a call with complete data (longer raw_data)
$call = DB::table('calls')
    ->whereNotNull('raw_data')
    ->where('raw_data', '!=', '')
    ->whereRaw('LENGTH(raw_data) > 5000')
    ->orderBy('created_at', 'desc')
    ->first();

if (!$call) {
    echo "No calls with complete data found!\n";
    exit;
}

echo "Call ID: " . $call->call_id . "\n";
echo "Created: " . $call->created_at . "\n";
echo "Duration: " . $call->duration_sec . " seconds\n";
echo "raw_data size: " . strlen($call->raw_data) . " chars\n\n";

$data = json_decode($call->raw_data, true);

// 1. Call Analysis
echo "=== CALL ANALYSIS ===\n";
if (isset($data['call_analysis'])) {
    $analysis = $data['call_analysis'];
    
    echo "Call Summary: " . ($analysis['call_summary'] ?? 'N/A') . "\n";
    echo "User Sentiment: " . ($analysis['user_sentiment'] ?? 'N/A') . "\n";
    echo "Call Successful: " . (isset($analysis['call_successful']) ? ($analysis['call_successful'] ? 'Yes' : 'No') : 'N/A') . "\n";
    echo "In Voicemail: " . (isset($analysis['in_voicemail']) ? ($analysis['in_voicemail'] ? 'Yes' : 'No') : 'N/A') . "\n";
    
    if (isset($analysis['custom_analysis_data'])) {
        echo "\nCustom Analysis Data:\n";
        foreach ($analysis['custom_analysis_data'] as $key => $value) {
            echo "  - $key: " . (is_bool($value) ? ($value ? 'Yes' : 'No') : $value) . "\n";
        }
    }
} else {
    echo "No call analysis data found\n";
}

// 2. Performance Metrics
echo "\n=== PERFORMANCE METRICS ===\n";
if (isset($data['latency'])) {
    $latency = $data['latency'];
    echo "LLM Latency (p50/p90/p99): " . 
         ($latency['llm']['p50'] ?? 'N/A') . "ms / " .
         ($latency['llm']['p90'] ?? 'N/A') . "ms / " .
         ($latency['llm']['p99'] ?? 'N/A') . "ms\n";
    
    echo "End-to-End Latency (p50/p90/p99): " . 
         ($latency['e2e']['p50'] ?? 'N/A') . "ms / " .
         ($latency['e2e']['p90'] ?? 'N/A') . "ms / " .
         ($latency['e2e']['p99'] ?? 'N/A') . "ms\n";
}

// 3. Cost Breakdown
echo "\n=== COST BREAKDOWN ===\n";
if (isset($data['call_cost'])) {
    $cost = $data['call_cost'];
    echo "Total Cost: $" . number_format($cost['combined_cost'] / 100, 2) . "\n";
    echo "Duration: " . $cost['total_duration_seconds'] . " seconds\n";
    
    if (isset($cost['product_costs'])) {
        echo "\nCost by Service:\n";
        foreach ($cost['product_costs'] as $item) {
            echo "  - " . $item['product'] . ": $" . number_format($item['cost'] / 100, 2) . "\n";
        }
    }
}

// 4. LLM Token Usage
echo "\n=== TOKEN USAGE ===\n";
if (isset($data['llm_token_usage'])) {
    $tokens = $data['llm_token_usage'];
    echo "Average Tokens: " . $tokens['average'] . "\n";
    echo "Number of Requests: " . $tokens['num_requests'] . "\n";
    echo "Total Tokens: " . array_sum($tokens['values']) . "\n";
}

// 5. What fields are currently NOT being saved
echo "\n=== FIELDS NOT BEING EXTRACTED ===\n";
$currentlySaved = [
    'call_id', 'agent_id', 'from_number', 'to_number', 'duration_ms',
    'transcript', 'recording_url', 'disconnection_reason', 'call_status',
    'start_timestamp', 'end_timestamp'
];

$availableButNotSaved = [];
foreach (array_keys($data) as $key) {
    if (!in_array($key, $currentlySaved)) {
        $availableButNotSaved[] = $key;
    }
}

echo "Available in raw_data but not extracted:\n";
foreach ($availableButNotSaved as $field) {
    echo "  - $field\n";
}

// Check what's in the database columns
echo "\n=== DATABASE COLUMN VALUES ===\n";
echo "analysis: " . ($call->analysis ? "Has data (" . strlen($call->analysis) . " chars)" : "Empty") . "\n";
echo "user_sentiment: " . ($call->user_sentiment ?: "Empty") . "\n";
echo "call_successful: " . ($call->call_successful !== null ? $call->call_successful : "NULL") . "\n";
echo "appointment_made: " . ($call->appointment_made !== null ? $call->appointment_made : "NULL") . "\n";
echo "latency_metrics: " . ($call->latency_metrics ? "Has data" : "Empty") . "\n";
echo "cost_breakdown: " . ($call->cost_breakdown ? "Has data" : "Empty") . "\n";