<?php
require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Call;
use App\Scopes\TenantScope;

echo "=== VERIFYING UI DATA DISPLAY ===\n";
echo str_repeat("=", 50) . "\n\n";

// Get fresh call instance
$call = Call::withoutGlobalScope(TenantScope::class)
    ->withoutGlobalScope(\App\Models\Scopes\CompanyScope::class)
    ->where('call_id', 'call_7a8ce8dec263e9e967279cfa532')
    ->first();

if (!$call) {
    echo "❌ Call not found\n";
    exit;
}

echo "1. BASIC INFORMATION\n";
echo str_repeat("-", 40) . "\n";
echo "Call ID: " . $call->call_id . "\n";
echo "Status: " . $call->call_status . "\n";
echo "Duration: " . $call->duration_sec . " seconds\n";
echo "From: " . $call->from_number . "\n";
echo "To: " . $call->to_number . "\n";
echo "Agent: " . ($call->agent_name ?? 'N/A') . "\n";
echo "\n";

echo "2. CALL ANALYSIS\n";
echo str_repeat("-", 40) . "\n";
echo "User Sentiment: " . ($call->user_sentiment ?? 'N/A') . "\n";
echo "Call Successful: " . ($call->call_successful ? '✅ Yes' : '❌ No') . "\n";
echo "Customer Name: " . ($call->extracted_name ?? 'N/A') . "\n";
echo "Appointment Made: " . ($call->appointment_made ? '✅ Yes' : '❌ No') . "\n";
echo "Reason for Visit: " . ($call->reason_for_visit ?? 'N/A') . "\n";
echo "Urgency Level: " . ($call->urgency_level ?? 'N/A') . "\n";
echo "\n";

echo "3. PERFORMANCE METRICS\n";
echo str_repeat("-", 40) . "\n";
echo "Cost: $" . number_format($call->cost, 2) . "\n";
echo "End-to-End Latency: " . ($call->end_to_end_latency ?? 'N/A') . " ms\n";

// Check if metrics are available as arrays
if (is_array($call->latency_metrics) && !empty($call->latency_metrics)) {
    echo "Latency Metrics: ✅ Available\n";
    if (isset($call->latency_metrics['llm']['p99'])) {
        echo "  - LLM P99: " . $call->latency_metrics['llm']['p99'] . " ms\n";
    }
} else {
    echo "Latency Metrics: ❌ Not available\n";
}

if (is_array($call->cost_breakdown) && !empty($call->cost_breakdown)) {
    echo "Cost Breakdown: ✅ Available\n";
} else {
    echo "Cost Breakdown: ❌ Not available\n";
}
echo "\n";

echo "4. DATA COMPLETENESS CHECK\n";
echo str_repeat("-", 40) . "\n";

$fieldsToCheck = [
    'analysis' => 'Analysis Data',
    'latency_metrics' => 'Latency Metrics',
    'cost_breakdown' => 'Cost Breakdown',
    'llm_usage' => 'LLM Usage',
    'custom_analysis_data' => 'Custom Analysis Data',
    'transcript_object' => 'Transcript Object',
    'retell_dynamic_variables' => 'Dynamic Variables',
    'public_log_url' => 'Public Log URL'
];

$available = 0;
$total = count($fieldsToCheck);

foreach ($fieldsToCheck as $field => $label) {
    $value = $call->$field;
    $hasData = false;
    
    if (is_array($value) && !empty($value)) {
        $hasData = true;
    } elseif (is_string($value) && strlen($value) > 0) {
        $hasData = true;
    }
    
    if ($hasData) {
        echo "✅ $label: Available";
        if (is_array($value)) {
            echo " (array with " . count($value) . " keys)";
        } elseif (is_string($value)) {
            echo " (" . strlen($value) . " chars)";
        }
        echo "\n";
        $available++;
    } else {
        echo "❌ $label: Missing\n";
    }
}

echo "\n";
echo str_repeat("=", 50) . "\n";
echo "SUMMARY: $available / $total fields have data (" . round(($available/$total) * 100) . "%)\n";

// Test if UI would show this data
echo "\n5. UI DISPLAY SIMULATION\n";
echo str_repeat("-", 40) . "\n";

// Simulate what the Filament infolist would show
if ($call->analysis && is_array($call->analysis)) {
    echo "Call Analysis Section: ✅ Would display\n";
    if (isset($call->analysis['call_summary'])) {
        echo "  - Summary: " . substr($call->analysis['call_summary'], 0, 50) . "...\n";
    }
} else {
    echo "Call Analysis Section: ❌ Would be hidden\n";
}

if ($call->custom_analysis_data && is_array($call->custom_analysis_data)) {
    echo "Customer Info Section: ✅ Would display\n";
} else {
    echo "Customer Info Section: ❌ Would be hidden\n";
}

if ($call->latency_metrics || $call->cost_breakdown) {
    echo "Performance Section: ✅ Would display\n";
} else {
    echo "Performance Section: ❌ Would be hidden\n";
}

echo "\n✅ Script completed successfully\n";