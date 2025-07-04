<?php

/**
 * Fix Call Durations
 */

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Call;
use Carbon\Carbon;

echo "\n=== Fix Call Durations ===\n\n";

// Get all calls with duration = NULL but have start and end timestamps
$callsToFix = Call::withoutGlobalScope(\App\Scopes\TenantScope::class)
    ->whereNull('duration_sec')
    ->whereNotNull('start_timestamp')
    ->whereNotNull('end_timestamp')
    ->get();

echo "Found " . $callsToFix->count() . " calls with missing durations\n";

$fixed = 0;
foreach ($callsToFix as $call) {
    $start = Carbon::parse($call->start_timestamp);
    $end = Carbon::parse($call->end_timestamp);
    
    $durationSeconds = $end->diffInSeconds($start);
    
    $call->duration_sec = $durationSeconds;
    $call->save();
    
    $fixed++;
    
    if ($fixed % 10 == 0) {
        echo "Fixed $fixed calls...\n";
    }
}

echo "\n✅ Fixed $fixed calls with missing durations\n";

// Now check if we have calls with durations
$callsWithDuration = Call::withoutGlobalScope(\App\Scopes\TenantScope::class)
    ->whereNotNull('duration_sec')
    ->where('duration_sec', '>', 0)
    ->count();

echo "Total calls with durations: $callsWithDuration\n";

// Check today's calls
$todayCallsWithDuration = Call::withoutGlobalScope(\App\Scopes\TenantScope::class)
    ->whereBetween('start_timestamp', [now()->startOfDay(), now()->endOfDay()])
    ->whereNotNull('duration_sec')
    ->where('duration_sec', '>', 0)
    ->count();

echo "Today's calls with durations: $todayCallsWithDuration\n";