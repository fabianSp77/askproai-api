<?php

/**
 * Update Call Durations from Retell API
 */

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Call;
use App\Services\RetellV2Service;
use App\Models\Company;

echo "\n=== Update Call Durations from Retell API ===\n\n";

// Get company
$company = Company::first();
if (!$company || !$company->retell_api_key) {
    echo "❌ No company with Retell API key found\n";
    exit(1);
}

$apiKey = decrypt($company->retell_api_key);
$retellService = new RetellV2Service($apiKey);

// Get recent calls from Retell
echo "Fetching calls from Retell API...\n";
$response = $retellService->listCalls(100);

if (!isset($response['calls'])) {
    echo "❌ No calls returned from API\n";
    exit(1);
}

echo "Found " . count($response['calls']) . " calls from Retell\n\n";

$updated = 0;
$skipped = 0;

foreach ($response['calls'] as $retellCall) {
    $callId = $retellCall['call_id'];
    
    // Find local call
    $localCall = Call::withoutGlobalScope(\App\Scopes\TenantScope::class)
        ->where('call_id', $callId)
        ->orWhere('retell_call_id', $callId)
        ->first();
        
    if (!$localCall) {
        continue;
    }
    
    // Get duration from Retell data
    $duration = null;
    
    // Check call_analysis.call_length (most common)
    if (isset($retellCall['call_analysis']['call_length'])) {
        $duration = (int)$retellCall['call_analysis']['call_length'];
    }
    // Check top-level call_length
    elseif (isset($retellCall['call_length'])) {
        $duration = (int)$retellCall['call_length'];
    }
    
    if ($duration !== null && $duration > 0) {
        $localCall->duration_sec = $duration;
        $localCall->save();
        $updated++;
        
        if ($updated <= 5) {
            echo "Updated call $callId with duration: $duration seconds\n";
        }
    } else {
        $skipped++;
    }
}

echo "\n✅ Updated $updated calls with durations\n";
echo "   Skipped $skipped calls (no duration data)\n";

// Check metrics again
$agentId = 'agent_9a8202a740cd3120d96fcfda1e';
$todaysCallsQuery = Call::withoutGlobalScope(\App\Scopes\TenantScope::class)
    ->where('company_id', 1)
    ->where('retell_agent_id', $agentId)
    ->whereBetween('start_timestamp', [now()->startOfDay(), now()->endOfDay()]);

$callsToday = $todaysCallsQuery->count();
$callsWithDuration = (clone $todaysCallsQuery)
    ->whereNotNull('duration_sec')
    ->where('duration_sec', '>', 0)
    ->count();
    
$avgDuration = (clone $todaysCallsQuery)
    ->whereNotNull('duration_sec')
    ->where('duration_sec', '>', 0)
    ->avg('duration_sec') ?? 0;

echo "\nUpdated Agent Metrics:\n";
echo "- Calls today: $callsToday\n";
echo "- Calls with duration: $callsWithDuration\n";
echo "- Average duration: " . gmdate("i:s", $avgDuration) . " ($avgDuration seconds)\n";