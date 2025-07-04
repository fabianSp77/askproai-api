<?php

/**
 * Cleanup Stuck Calls
 * 
 * Bereinigt alte Anrufe die fälschlicherweise als "in_progress" markiert sind
 */

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Call;
use Carbon\Carbon;

echo "\n=== Cleanup Stuck Calls ===\n";
echo "Started at: " . date('Y-m-d H:i:s') . "\n\n";

// Define reasonable call duration limit (e.g., 2 hours)
$maxCallDurationMinutes = 120;
$cutoffTime = now()->subMinutes($maxCallDurationMinutes);

// Find stuck calls
echo "1. Finding stuck calls (older than $maxCallDurationMinutes minutes)...\n";
$stuckCalls = Call::withoutGlobalScope(\App\Scopes\TenantScope::class)
    ->where(function($query) use ($cutoffTime) {
        $query->where('call_status', 'in_progress')
              ->where('start_timestamp', '<', $cutoffTime);
    })
    ->orWhere(function($query) use ($cutoffTime) {
        $query->whereNull('end_timestamp')
              ->where('start_timestamp', '<', $cutoffTime);
    })
    ->get();

echo "   Found " . $stuckCalls->count() . " stuck calls\n\n";

if ($stuckCalls->isEmpty()) {
    echo "   ✅ No stuck calls found!\n";
} else {
    echo "2. Processing stuck calls...\n";
    
    foreach ($stuckCalls as $call) {
        $age = $call->start_timestamp ? now()->diffInMinutes($call->start_timestamp) : 999;
        
        echo sprintf("   - Call %s (age: %d minutes)\n", 
            substr($call->retell_call_id ?? $call->call_id ?? 'unknown', 0, 20),
            $age
        );
        
        // Update the call
        $updates = [
            'call_status' => 'completed',
            'session_outcome' => 'System Timeout',
            'disconnection_reason' => 'system_timeout',
            'notes' => 'Auto-closed by cleanup script after ' . $age . ' minutes'
        ];
        
        // Set end timestamp if missing
        if (!$call->end_timestamp && $call->start_timestamp) {
            // Estimate end time based on duration or add default duration
            if ($call->duration_sec > 0) {
                $updates['end_timestamp'] = $call->start_timestamp->copy()->addSeconds($call->duration_sec);
            } else {
                // Default to 1 minute duration for stuck calls
                $updates['end_timestamp'] = $call->start_timestamp->copy()->addMinute();
                $updates['duration_sec'] = 60;
            }
        }
        
        // Apply updates
        foreach ($updates as $field => $value) {
            $call->$field = $value;
        }
        
        $call->save();
        echo "     ✅ Marked as completed\n";
    }
}

// Check for test calls
echo "\n3. Checking for test calls...\n";
$testCalls = Call::withoutGlobalScope(\App\Scopes\TenantScope::class)
    ->where(function($query) {
        $query->where('retell_call_id', 'like', 'test_%')
              ->orWhere('call_id', 'like', 'test_%')
              ->orWhere('metadata->source', 'test_script');
    })
    ->count();

echo "   Found $testCalls test calls in database\n";

// Show current status
echo "\n4. Current call status:\n";
$statusCounts = Call::withoutGlobalScope(\App\Scopes\TenantScope::class)
    ->selectRaw('call_status, COUNT(*) as count')
    ->groupBy('call_status')
    ->get();

foreach ($statusCounts as $status) {
    echo sprintf("   - %s: %d calls\n", 
        $status->call_status ?: 'Unknown',
        $status->count
    );
}

// Check for any remaining in-progress calls
echo "\n5. Remaining in-progress calls:\n";
$remainingInProgress = Call::withoutGlobalScope(\App\Scopes\TenantScope::class)
    ->where('call_status', 'in_progress')
    ->orWhereNull('end_timestamp')
    ->count();

if ($remainingInProgress > 0) {
    echo "   ⚠️  Still have $remainingInProgress in-progress calls\n";
    
    // Show details
    $remaining = Call::withoutGlobalScope(\App\Scopes\TenantScope::class)
        ->where('call_status', 'in_progress')
        ->orWhereNull('end_timestamp')
        ->orderBy('start_timestamp', 'desc')
        ->limit(5)
        ->get();
        
    foreach ($remaining as $call) {
        $age = $call->start_timestamp ? now()->diffInMinutes($call->start_timestamp) : 0;
        echo sprintf("   - %s (age: %d min) from %s\n",
            substr($call->retell_call_id ?? 'unknown', 0, 20),
            $age,
            $call->from_number ?? 'unknown'
        );
    }
} else {
    echo "   ✅ No in-progress calls remaining\n";
}

echo "\n=== Cleanup Complete ===\n";
echo "Completed at: " . date('Y-m-d H:i:s') . "\n";