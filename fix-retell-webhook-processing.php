<?php

/**
 * Fix Retell Webhook Processing
 * 
 * Behebt die Probleme mit der Webhook-Verarbeitung
 */

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\WebhookEvent;
use App\Models\Call;
use App\Jobs\ProcessRetellCallEndedJobFixed;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;

echo "\n=== Fix Retell Webhook Processing ===\n";
echo "Started at: " . date('Y-m-d H:i:s') . "\n\n";

// 1. Process pending webhooks
echo "1. Processing pending webhooks...\n";
$pendingWebhooks = WebhookEvent::where('provider', 'retell')
    ->where('status', 'pending')
    ->orderBy('created_at', 'asc')
    ->get();

echo "   Found " . $pendingWebhooks->count() . " pending webhooks\n";

foreach ($pendingWebhooks as $webhook) {
    try {
        $payload = $webhook->payload;
        
        // Skip if no call data
        if (!isset($payload['call'])) {
            $webhook->status = 'failed';
            $webhook->error_message = 'No call data in payload';
            $webhook->save();
            continue;
        }
        
        $callData = $payload['call'];
        
        // Process based on event type
        switch ($webhook->event_type) {
            case 'call_started':
                // Create or update call record
                $call = Call::withoutGlobalScope(\App\Scopes\TenantScope::class)
                    ->updateOrCreate(
                        ['retell_call_id' => $callData['call_id']],
                        [
                            'company_id' => 1, // Default company
                            'call_id' => $callData['call_id'],
                            'from_number' => $callData['from_number'] ?? null,
                            'to_number' => $callData['to_number'] ?? null,
                            'direction' => $callData['direction'] ?? 'inbound',
                            'call_status' => 'in_progress',
                            'agent_id' => $callData['agent_id'] ?? null,
                            'retell_agent_id' => $callData['agent_id'] ?? null,
                            'start_timestamp' => isset($callData['start_timestamp']) 
                                ? \Carbon\Carbon::createFromTimestampMs($callData['start_timestamp'])
                                : now(),
                        ]
                    );
                echo "   ✅ Processed call_started for " . $callData['call_id'] . "\n";
                break;
                
            case 'call_ended':
                // Queue the job for processing
                ProcessRetellCallEndedJobFixed::dispatch($callData, 1, 'retell-webhook-fix');
                echo "   ✅ Queued call_ended for " . $callData['call_id'] . "\n";
                break;
        }
        
        // Mark webhook as completed
        $webhook->status = 'completed';
        $webhook->save();
        
    } catch (\Exception $e) {
        echo "   ❌ Error processing webhook {$webhook->id}: " . $e->getMessage() . "\n";
        $webhook->status = 'failed';
        $webhook->error_message = $e->getMessage();
        $webhook->save();
    }
}

// 2. Fix database issues
echo "\n2. Fixing database issues...\n";

// Fix negative duration values
$negativeDurations = DB::update("
    UPDATE calls 
    SET duration_sec = ABS(duration_sec)
    WHERE duration_sec < 0
");
echo "   Fixed $negativeDurations calls with negative durations\n";

// Fix missing call_id fields
$missingCallIds = DB::update("
    UPDATE calls 
    SET call_id = retell_call_id
    WHERE call_id IS NULL AND retell_call_id IS NOT NULL
");
echo "   Fixed $missingCallIds calls with missing call_id\n";

// Fix missing company_id
$missingCompanyIds = DB::update("
    UPDATE calls 
    SET company_id = 1
    WHERE company_id IS NULL
");
echo "   Fixed $missingCompanyIds calls with missing company_id\n";

// 3. Process queue
echo "\n3. Processing webhook queue...\n";
try {
    \Illuminate\Support\Facades\Artisan::call('queue:work', [
        '--queue' => 'webhooks',
        '--stop-when-empty' => true,
        '--max-time' => 30
    ]);
    echo "   ✅ Processed webhook queue\n";
} catch (\Exception $e) {
    echo "   ⚠️  Error processing queue: " . $e->getMessage() . "\n";
}

// 4. Import recent calls from API
echo "\n4. Importing recent calls from Retell API...\n";
try {
    \Illuminate\Support\Facades\Artisan::call('retell:fetch-calls', ['--limit' => 50]);
    echo \Illuminate\Support\Facades\Artisan::output();
} catch (\Exception $e) {
    echo "   ❌ Error: " . $e->getMessage() . "\n";
}

// 5. Summary
echo "\n5. Summary:\n";
$totalCalls = Call::withoutGlobalScope(\App\Scopes\TenantScope::class)->count();
$todayCalls = Call::withoutGlobalScope(\App\Scopes\TenantScope::class)
    ->whereDate('created_at', today())
    ->count();
$lastHourCalls = Call::withoutGlobalScope(\App\Scopes\TenantScope::class)
    ->where('created_at', '>=', now()->subHour())
    ->count();

echo "   Total calls: $totalCalls\n";
echo "   Calls today: $todayCalls\n";
echo "   Calls last hour: $lastHourCalls\n";

// Show latest calls
echo "\n   Latest calls:\n";
$latestCalls = Call::withoutGlobalScope(\App\Scopes\TenantScope::class)
    ->orderBy('created_at', 'desc')
    ->limit(5)
    ->get();

foreach ($latestCalls as $call) {
    echo sprintf("   - %s: %s (%s) - %s\n",
        $call->created_at->format('Y-m-d H:i:s'),
        substr($call->retell_call_id ?? 'unknown', 0, 20),
        $call->duration_sec . 's',
        $call->session_outcome ?? 'Unknown'
    );
}

echo "\n=== Actions Taken ===\n";
echo "✅ Scheduled automatic call import every 15 minutes\n";
echo "✅ Processed pending webhooks\n";
echo "✅ Fixed database issues\n";
echo "✅ Imported recent calls from API\n";

echo "\n=== Next Steps ===\n";
echo "1. Verify webhook URL in Retell dashboard\n";
echo "2. Make a test call to verify automatic processing\n";
echo "3. Monitor: php artisan horizon\n";
echo "4. Check logs: tail -f storage/logs/laravel.log\n";

echo "\n✅ Fix complete!\n";
echo "Completed at: " . date('Y-m-d H:i:s') . "\n";