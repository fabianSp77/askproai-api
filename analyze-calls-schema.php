<?php
require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

echo "=== CALLS TABLE SCHEMA ANALYSIS ===\n";
echo str_repeat("=", 50) . "\n\n";

// Get all columns with their types
$columns = Schema::getColumnListing('calls');
echo "Total columns: " . count($columns) . "\n\n";

// Group by data type
$textColumns = [];
$jsonColumns = [];
$numericColumns = [];
$dateColumns = [];
$otherColumns = [];

foreach ($columns as $column) {
    $type = Schema::getColumnType('calls', $column);
    
    if (strpos($type, 'text') !== false || strpos($type, 'varchar') !== false || strpos($type, 'string') !== false) {
        $textColumns[] = "$column ($type)";
    } elseif ($type === 'json') {
        $jsonColumns[] = $column;
    } elseif (strpos($type, 'int') !== false || strpos($type, 'decimal') !== false || strpos($type, 'float') !== false) {
        $numericColumns[] = "$column ($type)";
    } elseif (strpos($type, 'date') !== false || strpos($type, 'time') !== false) {
        $dateColumns[] = "$column ($type)";
    } else {
        $otherColumns[] = "$column ($type)";
    }
}

echo "TEXT COLUMNS (" . count($textColumns) . "):\n";
foreach ($textColumns as $col) {
    echo "  - $col\n";
}

echo "\nJSON COLUMNS (" . count($jsonColumns) . "):\n";
foreach ($jsonColumns as $col) {
    echo "  - $col\n";
}

echo "\nNUMERIC COLUMNS (" . count($numericColumns) . "):\n";
foreach ($numericColumns as $col) {
    echo "  - $col\n";
}

echo "\nDATE/TIME COLUMNS (" . count($dateColumns) . "):\n";
foreach ($dateColumns as $col) {
    echo "  - $col\n";
}

if (!empty($otherColumns)) {
    echo "\nOTHER COLUMNS (" . count($otherColumns) . "):\n";
    foreach ($otherColumns as $col) {
        echo "  - $col\n";
    }
}

// Check what data we have in JSON columns
echo "\n\n=== JSON COLUMN CONTENT ANALYSIS ===\n";
echo str_repeat("=", 50) . "\n";

$sampleCall = DB::table('calls')
    ->whereNotNull('raw_data')
    ->orderBy('created_at', 'desc')
    ->first();

if ($sampleCall) {
    foreach ($jsonColumns as $jsonCol) {
        if (!empty($sampleCall->$jsonCol)) {
            $data = json_decode($sampleCall->$jsonCol, true);
            echo "\n$jsonCol structure:\n";
            if (is_array($data)) {
                printStructure($data, 1);
            }
        }
    }
}

// Check for missing Retell fields
echo "\n\n=== MISSING RETELL FIELDS ===\n";
echo str_repeat("=", 50) . "\n";

$retellFields = [
    'call_analysis' => 'JSON - Complete call analysis from Retell',
    'call_successful' => 'BOOLEAN - Whether call achieved objective',
    'user_sentiment' => 'STRING - Positive/Negative/Neutral',
    'urgency_level' => 'STRING - Call urgency classification',
    'no_show_count' => 'INTEGER - Previous no-shows',
    'reschedule_count' => 'INTEGER - Number of reschedules',
    'first_visit' => 'BOOLEAN - Is first visit',
    'appointment_made' => 'BOOLEAN - Was appointment booked',
    'reason_for_visit' => 'STRING - Why customer called',
    'insurance_type' => 'STRING - Type of insurance',
    'insurance_company' => 'STRING - Insurance provider',
    'latency_metrics' => 'JSON - Performance metrics',
    'cost_breakdown' => 'JSON - Detailed cost by service',
    'llm_token_usage' => 'JSON - Token usage stats',
    'transcript_object' => 'JSON - Structured transcript with timestamps',
    'custom_analysis_data' => 'JSON - All custom analysis fields'
];

$existingColumns = array_map('strtolower', $columns);

foreach ($retellFields as $field => $description) {
    if (!in_array(strtolower($field), $existingColumns)) {
        echo "❌ Missing: $field - $description\n";
    } else {
        echo "✅ Exists: $field\n";
    }
}

function printStructure($data, $indent = 0) {
    $prefix = str_repeat("  ", $indent);
    foreach ($data as $key => $value) {
        if (is_array($value)) {
            echo $prefix . "- $key: [" . (isset($value[0]) ? "array" : "object") . "]\n";
            if ($indent < 3) { // Limit depth
                printStructure(isset($value[0]) ? $value[0] : $value, $indent + 1);
            }
        } else {
            $type = gettype($value);
            $sample = is_string($value) ? substr($value, 0, 30) . (strlen($value) > 30 ? "..." : "") : $value;
            echo $prefix . "- $key: $type" . ($sample ? " (e.g. '$sample')" : "") . "\n";
        }
    }
}