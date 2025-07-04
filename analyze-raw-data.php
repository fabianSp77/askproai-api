<?php
require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== RETELL RAW DATA ANALYSIS ===\n";
echo str_repeat("=", 50) . "\n\n";

// Get the most recent call with raw_data
$call = DB::table('calls')
    ->whereNotNull('raw_data')
    ->where('raw_data', '!=', '')
    ->orderBy('created_at', 'desc')
    ->first();

if (!$call) {
    echo "No calls with raw_data found!\n";
    exit;
}

echo "Analyzing call: " . $call->call_id . "\n";
echo "Created: " . $call->created_at . "\n\n";

$rawData = json_decode($call->raw_data, true);

if (!$rawData) {
    echo "Failed to decode raw_data\n";
    exit;
}

// Function to analyze structure
function analyzeStructure($data, $path = '') {
    $structure = [];
    
    foreach ($data as $key => $value) {
        $currentPath = $path ? "$path.$key" : $key;
        
        if (is_array($value)) {
            if (isset($value[0])) {
                // It's an array
                $structure[$currentPath] = [
                    'type' => 'array',
                    'count' => count($value),
                    'sample' => analyzeStructure($value[0], $currentPath . '[0]')
                ];
            } else {
                // It's an object
                $structure[$currentPath] = [
                    'type' => 'object',
                    'children' => analyzeStructure($value, $currentPath)
                ];
            }
        } else {
            $type = gettype($value);
            $structure[$currentPath] = [
                'type' => $type,
                'value' => $value
            ];
        }
    }
    
    return $structure;
}

// Key fields to extract
$keyFields = [
    'call_analysis' => null,
    'call_analysis.call_summary' => null,
    'call_analysis.user_sentiment' => null,
    'call_analysis.call_successful' => null,
    'call_analysis.custom_analysis_data' => null,
    'latency' => null,
    'call_cost' => null,
    'llm_token_usage' => null,
    'transcript_object' => null,
    'transcript_with_tool_calls' => null,
    'agent_name' => null,
    'public_log_url' => null
];

// Extract key fields
echo "=== KEY FIELDS FROM RAW DATA ===\n";
echo str_repeat("-", 50) . "\n";

foreach (array_keys($keyFields) as $field) {
    $value = getNestedValue($rawData, $field);
    if ($value !== null) {
        echo "\n$field:\n";
        if (is_array($value)) {
            echo json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
        } else {
            echo "  Value: $value\n";
        }
    } else {
        echo "\n$field: NOT FOUND\n";
    }
}

// Check what's in analysis column
echo "\n\n=== CURRENT ANALYSIS COLUMN ===\n";
echo str_repeat("-", 50) . "\n";

if ($call->analysis) {
    $analysis = json_decode($call->analysis, true);
    if ($analysis) {
        echo json_encode($analysis, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
    } else {
        echo "Failed to decode analysis column\n";
    }
} else {
    echo "Analysis column is empty\n";
}

// Summary of available data
echo "\n\n=== DATA AVAILABILITY SUMMARY ===\n";
echo str_repeat("-", 50) . "\n";

$checks = [
    'Has call_analysis' => isset($rawData['call_analysis']),
    'Has user sentiment' => isset($rawData['call_analysis']['user_sentiment']),
    'Has call summary' => isset($rawData['call_analysis']['call_summary']),
    'Has custom analysis' => isset($rawData['call_analysis']['custom_analysis_data']),
    'Has latency metrics' => isset($rawData['latency']),
    'Has cost breakdown' => isset($rawData['call_cost']),
    'Has token usage' => isset($rawData['llm_token_usage']),
    'Has transcript object' => isset($rawData['transcript_object']),
    'Has public log URL' => isset($rawData['public_log_url'])
];

foreach ($checks as $check => $result) {
    echo $check . ": " . ($result ? "✅ YES" : "❌ NO") . "\n";
}

function getNestedValue($data, $path) {
    $keys = explode('.', $path);
    $value = $data;
    
    foreach ($keys as $key) {
        if (is_array($value) && isset($value[$key])) {
            $value = $value[$key];
        } else {
            return null;
        }
    }
    
    return $value;
}