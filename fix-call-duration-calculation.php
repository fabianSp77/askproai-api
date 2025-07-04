<?php

/**
 * Fix Call Duration Calculation
 * Korrigiert die falsch berechneten Dauern in den Anrufen
 */

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Call;

echo "\n=== Fix Call Duration Calculation ===\n\n";

// Get calls with wrong duration
$calls = Call::withoutGlobalScope(\App\Scopes\TenantScope::class)
    ->whereNotNull('start_timestamp')
    ->whereNotNull('end_timestamp')
    ->get();

$fixed = 0;
$errors = 0;

foreach ($calls as $call) {
    try {
        // Calculate correct duration
        $start = new \Carbon\Carbon($call->start_timestamp);
        $end = new \Carbon\Carbon($call->end_timestamp);
        
        // Always use absolute value for duration
        $correctDuration = abs($end->diffInSeconds($start));
        
        // Check if duration is wrong
        if ($call->duration_sec != $correctDuration) {
            echo "Call {$call->call_id}:\n";
            echo "  Wrong duration: {$call->duration_sec}s\n";
            echo "  Correct duration: {$correctDuration}s\n";
            echo "  Start: {$call->start_timestamp}\n";
            echo "  End: {$call->end_timestamp}\n";
            
            // Update duration
            $call->duration_sec = $correctDuration;
            $call->save();
            
            echo "  ✅ Fixed\n\n";
            $fixed++;
        }
    } catch (\Exception $e) {
        echo "❌ Error fixing call {$call->id}: " . $e->getMessage() . "\n";
        $errors++;
    }
}

echo "\n=== Summary ===\n";
echo "Fixed: $fixed calls\n";
echo "Errors: $errors\n";

// Verify specific call
$testCall = Call::withoutGlobalScope(\App\Scopes\TenantScope::class)
    ->where('call_id', 'call_e2c7629e547c22f066eebac60f9')
    ->first();

if ($testCall) {
    echo "\nTest Call Verification:\n";
    echo "Call ID: {$testCall->call_id}\n";
    echo "Duration: {$testCall->duration_sec}s\n";
    echo "Should be: 54s (according to Retell dashboard)\n";
}

echo "\n✅ Complete\n";