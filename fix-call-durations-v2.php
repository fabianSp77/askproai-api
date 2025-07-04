<?php

/**
 * Fix Call Durations V2
 */

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Call;
use Carbon\Carbon;

echo "\n=== Fix Call Durations V2 ===\n\n";

// First, let's check the raw data from a call
$sampleCall = Call::withoutGlobalScope(\App\Scopes\TenantScope::class)
    ->whereNotNull('raw_data')
    ->whereNull('duration_sec')
    ->first();

if ($sampleCall && $sampleCall->raw_data) {
    $rawData = is_string($sampleCall->raw_data) ? json_decode($sampleCall->raw_data, true) : $sampleCall->raw_data;
    echo "Sample raw data:\n";
    if (isset($rawData['call_analysis'])) {
        echo "  - call_analysis.call_length: " . ($rawData['call_analysis']['call_length'] ?? 'NOT FOUND') . "\n";
    }
    if (isset($rawData['call_length'])) {
        echo "  - call_length: " . $rawData['call_length'] . "\n";
    }
    if (isset($rawData['duration'])) {
        echo "  - duration: " . $rawData['duration'] . "\n";
    }
}

// Update calls with duration from raw_data
$calls = Call::withoutGlobalScope(\App\Scopes\TenantScope::class)
    ->whereNull('duration_sec')
    ->whereNotNull('raw_data')
    ->take(50) // Process in batches
    ->get();

echo "\nProcessing " . $calls->count() . " calls...\n";

$fixed = 0;
$skipped = 0;

foreach ($calls as $call) {
    $rawData = is_string($call->raw_data) ? json_decode($call->raw_data, true) : $call->raw_data;
    
    if (!$rawData) {
        $skipped++;
        continue;
    }
    
    // Try to find duration in various places
    $duration = null;
    
    // Check call_analysis.call_length first (in seconds)
    if (isset($rawData['call_analysis']['call_length'])) {
        $duration = (int)$rawData['call_analysis']['call_length'];
    }
    // Check top-level call_length
    elseif (isset($rawData['call_length'])) {
        $duration = (int)$rawData['call_length'];
    }
    // Check duration field
    elseif (isset($rawData['duration'])) {
        $duration = (int)$rawData['duration'];
    }
    // Calculate from timestamps if available
    elseif ($call->start_timestamp && $call->end_timestamp) {
        $start = Carbon::parse($call->start_timestamp);
        $end = Carbon::parse($call->end_timestamp);
        
        // Make sure end is after start
        if ($end->gt($start)) {
            $duration = $end->diffInSeconds($start);
        }
    }
    
    if ($duration !== null && $duration >= 0) {
        $call->duration_sec = $duration;
        $call->save();
        $fixed++;
    } else {
        $skipped++;
    }
}

echo "\n✅ Fixed $fixed calls, skipped $skipped\n";

// Now check today's metrics
$agentId = 'agent_9a8202a740cd3120d96fcfda1e';
$todaysCallsQuery = Call::withoutGlobalScope(\App\Scopes\TenantScope::class)
    ->where('company_id', 1)
    ->where('retell_agent_id', $agentId)
    ->whereBetween('start_timestamp', [now()->startOfDay(), now()->endOfDay()]);

$callsToday = $todaysCallsQuery->count();
$avgDuration = (clone $todaysCallsQuery)
    ->whereNotNull('duration_sec')
    ->where('duration_sec', '>', 0)
    ->avg('duration_sec') ?? 0;

echo "\nAgent Metrics:\n";
echo "- Calls today: $callsToday\n";
echo "- Average duration: " . gmdate("i:s", $avgDuration) . " ($avgDuration seconds)\n";