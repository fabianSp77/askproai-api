<?php
require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== RAW DATA STRUCTURE ===\n";
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

echo "Call ID: " . $call->call_id . "\n";
echo "Created: " . $call->created_at . "\n\n";

$rawData = json_decode($call->raw_data, true);

if (!$rawData) {
    echo "Failed to decode raw_data\n";
    // Show first 500 chars of raw data
    echo "Raw content (first 500 chars):\n";
    echo substr($call->raw_data, 0, 500) . "\n";
    exit;
}

// Show top-level keys
echo "TOP-LEVEL KEYS:\n";
foreach (array_keys($rawData) as $key) {
    $type = is_array($rawData[$key]) ? "array/object" : gettype($rawData[$key]);
    echo "  - $key ($type)\n";
}

// Pretty print the entire structure (limited depth)
echo "\n\nFULL STRUCTURE:\n";
echo json_encode($rawData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

// Also check a call from Retell API
echo "\n\n=== CHECKING RETELL API FOR COMPARISON ===\n";

$apiKey = config('services.retell.api_key');
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'https://api.retellai.com/v2/get-call/' . $call->retell_call_id);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $apiKey,
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode === 200) {
    $apiData = json_decode($response, true);
    echo "RETELL API RESPONSE KEYS:\n";
    foreach (array_keys($apiData) as $key) {
        $type = is_array($apiData[$key]) ? "array/object" : gettype($apiData[$key]);
        echo "  - $key ($type)\n";
    }
    
    // Check for call_analysis
    if (isset($apiData['call_analysis'])) {
        echo "\nCALL_ANALYSIS FOUND IN API RESPONSE!\n";
        echo json_encode($apiData['call_analysis'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }
} else {
    echo "Failed to fetch from Retell API: HTTP $httpCode\n";
}