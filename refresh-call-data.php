<?php
require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Call;
use App\Scopes\TenantScope;
use Illuminate\Support\Facades\DB;

echo "=== REFRESHING CALL DATA ===\n";
echo str_repeat("=", 50) . "\n\n";

// Get the raw data from database
$rawCall = DB::table('calls')
    ->where('call_id', 'call_7a8ce8dec263e9e967279cfa532')
    ->first();

if (!$rawCall) {
    echo "❌ Call not found in database\n";
    exit;
}

echo "1. RAW DATA CHECK\n";
echo str_repeat("-", 40) . "\n";

// Check which fields have JSON data
$jsonFields = [
    'latency_metrics',
    'cost_breakdown', 
    'llm_usage',
    'custom_analysis_data'
];

foreach ($jsonFields as $field) {
    if ($rawCall->$field) {
        $decoded = json_decode($rawCall->$field, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            echo "✅ $field: Valid JSON (" . strlen($rawCall->$field) . " chars)\n";
        } else {
            echo "❌ $field: Invalid JSON - " . json_last_error_msg() . "\n";
        }
    } else {
        echo "❌ $field: Empty/NULL\n";
    }
}

echo "\n2. FORCING DATA REFRESH\n";
echo str_repeat("-", 40) . "\n";

// Get the call through Eloquent and force refresh
$call = Call::withoutGlobalScope(TenantScope::class)
    ->withoutGlobalScope(\App\Models\Scopes\CompanyScope::class)
    ->where('call_id', 'call_7a8ce8dec263e9e967279cfa532')
    ->first();

// Manually set the JSON fields from raw data
$updates = [];
foreach ($jsonFields as $field) {
    if ($rawCall->$field && is_string($rawCall->$field)) {
        $decoded = json_decode($rawCall->$field, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            $call->$field = $decoded;
            $updates[] = $field;
        }
    }
}

if (!empty($updates)) {
    $call->save();
    echo "✅ Updated fields: " . implode(', ', $updates) . "\n";
} else {
    echo "❌ No fields needed updating\n";
}

echo "\n3. VERIFICATION AFTER REFRESH\n";
echo str_repeat("-", 40) . "\n";

// Re-fetch the call
$call = Call::withoutGlobalScope(TenantScope::class)
    ->withoutGlobalScope(\App\Models\Scopes\CompanyScope::class)
    ->where('call_id', 'call_7a8ce8dec263e9e967279cfa532')
    ->first();

foreach ($jsonFields as $field) {
    $value = $call->$field;
    if (is_array($value) && !empty($value)) {
        echo "✅ $field: Array with " . count($value) . " keys\n";
    } else {
        echo "❌ $field: Empty/Not an array\n";
    }
}

echo "\n✅ Data refresh completed\n";