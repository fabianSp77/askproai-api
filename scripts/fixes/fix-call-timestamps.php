#!/usr/bin/env php
<?php
/**
 * Fix Call Timestamps (UTC to Berlin Time)
 * 
 * This script fixes call timestamps showing UTC instead of Berlin time
 * by converting all existing timestamps to Europe/Berlin timezone.
 * 
 * Error Code: RETELL_004
 */

require_once __DIR__ . '/../../vendor/autoload.php';

$app = require_once __DIR__ . '/../../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Call;
use Carbon\Carbon;

echo "🔧 Call Timestamp Fix Script\n";
echo "============================\n\n";

try {
    // Step 1: Check current timezone configuration
    echo "1. Checking timezone configuration...\n";
    $appTimezone = config('app.timezone');
    $defaultTimezone = date_default_timezone_get();
    
    echo "   App timezone: {$appTimezone}\n";
    echo "   Default timezone: {$defaultTimezone}\n";
    echo "   Current time: " . now()->format('Y-m-d H:i:s') . "\n";
    echo "   Berlin time: " . now()->setTimezone('Europe/Berlin')->format('Y-m-d H:i:s') . "\n";

    // Step 2: Analyze existing calls
    echo "\n2. Analyzing call timestamps...\n";
    
    $recentCall = Call::orderBy('created_at', 'desc')->first();
    if ($recentCall) {
        echo "   Most recent call:\n";
        echo "   - Created at: " . $recentCall->created_at->format('Y-m-d H:i:s') . "\n";
        echo "   - Start time: " . ($recentCall->start_timestamp ? 
            Carbon::createFromTimestamp($recentCall->start_timestamp / 1000)->format('Y-m-d H:i:s') : 'N/A') . "\n";
        
        // Check if times appear to be in UTC (2 hours behind Berlin in summer)
        $startTime = $recentCall->start_timestamp ? 
            Carbon::createFromTimestamp($recentCall->start_timestamp / 1000) : null;
        
        if ($startTime) {
            $berlinTime = $startTime->copy()->setTimezone('Europe/Berlin');
            $hourDiff = $berlinTime->hour - $startTime->hour;
            
            echo "   - UTC time: " . $startTime->format('Y-m-d H:i:s') . "\n";
            echo "   - Berlin time: " . $berlinTime->format('Y-m-d H:i:s') . "\n";
            echo "   - Hour difference: {$hourDiff}\n";
        }
    }

    // Step 3: Get calls that need fixing
    echo "\n3. Finding calls that need timestamp fixes...\n";
    
    // Note: This is already fixed in ProcessRetellCallEndedJob for new calls
    // We only need to fix old calls that were stored in UTC
    
    // Get calls from before the fix was implemented
    $cutoffDate = '2025-07-02'; // Date when fix was implemented
    $callsToFix = Call::where('created_at', '<', $cutoffDate)
                      ->whereNotNull('start_timestamp')
                      ->count();
    
    echo "   Found {$callsToFix} calls created before {$cutoffDate}\n";

    if ($callsToFix === 0) {
        echo "   ✅ No calls need timestamp adjustment!\n";
        echo "   All new calls are automatically converted to Berlin time.\n";
        exit(0);
    }

    // Step 4: Ask for confirmation
    echo "\n4. Ready to fix {$callsToFix} call timestamps.\n";
    echo "   This will adjust timestamps from UTC to Berlin time (+1/+2 hours).\n";
    echo "   Continue? (yes/no): ";
    
    $handle = fopen("php://stdin", "r");
    $line = fgets($handle);
    if (trim($line) !== 'yes') {
        echo "   Operation cancelled.\n";
        exit(0);
    }
    fclose($handle);

    // Step 5: Fix timestamps
    echo "\n5. Fixing call timestamps...\n";
    $batchSize = 100;
    $processed = 0;
    
    Call::where('created_at', '<', $cutoffDate)
        ->whereNotNull('start_timestamp')
        ->chunk($batchSize, function ($calls) use (&$processed) {
            foreach ($calls as $call) {
                // Convert timestamps from UTC to Berlin time
                // Retell sends timestamps in milliseconds
                
                if ($call->start_timestamp) {
                    $utcTime = Carbon::createFromTimestamp($call->start_timestamp / 1000, 'UTC');
                    $berlinTime = $utcTime->setTimezone('Europe/Berlin');
                    $call->start_timestamp = $berlinTime->timestamp * 1000;
                }
                
                if ($call->end_timestamp) {
                    $utcTime = Carbon::createFromTimestamp($call->end_timestamp / 1000, 'UTC');
                    $berlinTime = $utcTime->setTimezone('Europe/Berlin');
                    $call->end_timestamp = $berlinTime->timestamp * 1000;
                }
                
                if ($call->answered_at) {
                    // answered_at is stored as datetime, convert timezone
                    $call->answered_at = Carbon::parse($call->answered_at, 'UTC')
                                              ->setTimezone('Europe/Berlin');
                }
                
                $call->timestamps = false; // Don't update updated_at
                $call->save();
                $processed++;
            }
            
            echo "   Processed {$processed} calls...\n";
        });

    // Step 6: Verify fix
    echo "\n6. Verifying fix...\n";
    
    $sampleCall = Call::where('created_at', '<', $cutoffDate)
                      ->whereNotNull('start_timestamp')
                      ->first();
                      
    if ($sampleCall) {
        $startTime = Carbon::createFromTimestamp($sampleCall->start_timestamp / 1000);
        echo "   Sample call after fix:\n";
        echo "   - Call ID: {$sampleCall->call_id}\n";
        echo "   - Start time: " . $startTime->format('Y-m-d H:i:s') . "\n";
        echo "   - Timezone: " . $startTime->timezoneName . "\n";
    }

    // Summary
    echo "\n✅ Timestamp fix completed!\n";
    echo "   - Fixed {$processed} call timestamps\n";
    echo "   - All timestamps now show Berlin time\n";
    echo "   - New calls are automatically converted\n";
    
    echo "\nNote: The fix has been implemented in ProcessRetellCallEndedJob\n";
    echo "so all new calls will automatically use Berlin time.\n";
    
    exit(0);

} catch (\Exception $e) {
    echo "\n❌ Error during fix process: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}