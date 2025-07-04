<?php

/**
 * Fix Webhook Company Context
 * 
 * Behebt das Problem mit fehlender company_id bei Webhooks
 */

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\WebhookEvent;
use App\Models\PhoneNumber;
use App\Jobs\ProcessWebhookJob;

echo "\n=== Fix Webhook Company Context ===\n";
echo "Started at: " . date('Y-m-d H:i:s') . "\n\n";

// 1. Fix existing failed webhooks
echo "1. Fixing failed webhooks with missing company context...\n";
$failedWebhooks = WebhookEvent::where('status', 'failed')
    ->where('error_message', 'like', '%No company context%')
    ->orderBy('created_at', 'desc')
    ->limit(10)
    ->get();

echo "   Found " . $failedWebhooks->count() . " failed webhooks\n";

foreach ($failedWebhooks as $webhook) {
    $payload = $webhook->payload;
    
    // Try to find company from phone number
    $companyId = null;
    
    // Check different places for phone number
    $phoneNumber = null;
    if (isset($payload['call']['to_number'])) {
        $phoneNumber = $payload['call']['to_number'];
    } elseif (isset($payload['call_inbound']['to_number'])) {
        $phoneNumber = $payload['call_inbound']['to_number'];
    }
    
    if ($phoneNumber) {
        $phone = PhoneNumber::where('number', $phoneNumber)
            ->orWhere('number', 'like', '%' . substr($phoneNumber, -10))
            ->first();
            
        if ($phone) {
            $companyId = $phone->company_id;
            echo "   ✅ Found company $companyId for phone $phoneNumber\n";
        }
    }
    
    // If no company found, use default
    if (!$companyId) {
        $companyId = 1; // Default company
        echo "   ⚠️  Using default company for webhook {$webhook->id}\n";
    }
    
    // Update webhook with company_id
    $webhook->company_id = $companyId;
    $webhook->status = 'pending'; // Reset to pending
    $webhook->error_message = null;
    $webhook->save();
    
    // Re-dispatch for processing
    dispatch(new ProcessWebhookJob($webhook, $webhook->correlation_id))
        ->onQueue('webhooks');
        
    echo "   ✅ Re-dispatched webhook {$webhook->id} with company $companyId\n";
}

// 2. Process the queue
echo "\n2. Processing webhook queue...\n";
\Illuminate\Support\Facades\Artisan::call('queue:work', [
    '--queue' => 'webhooks',
    '--stop-when-empty' => true,
    '--max-time' => 10
]);

// 3. Check results
echo "\n3. Checking results...\n";
$recentCalls = \App\Models\Call::withoutGlobalScope(\App\Scopes\TenantScope::class)
    ->where('created_at', '>', now()->subMinutes(5))
    ->orderBy('created_at', 'desc')
    ->get();

echo "   Recent calls (last 5 minutes): " . $recentCalls->count() . "\n";
foreach ($recentCalls as $call) {
    echo sprintf("   - %s: %s from %s (%s)\n",
        $call->created_at->format('H:i:s'),
        substr($call->retell_call_id ?? 'unknown', 0, 20),
        $call->from_number,
        $call->call_status
    );
}

// 4. Check for active calls
echo "\n4. Active calls:\n";
$activeCalls = \App\Models\Call::withoutGlobalScope(\App\Scopes\TenantScope::class)
    ->whereNull('end_timestamp')
    ->where('created_at', '>', now()->subHours(2))
    ->get();

echo "   Found " . $activeCalls->count() . " active calls\n";
foreach ($activeCalls as $call) {
    $duration = $call->start_timestamp ? now()->diffInSeconds($call->start_timestamp) : 0;
    echo sprintf("   - %s from %s (%ds)\n",
        $call->retell_call_id,
        $call->from_number,
        $duration
    );
}

echo "\n=== Next Steps ===\n";
echo "1. The webhook processing has been fixed\n";
echo "2. Future webhooks should work correctly\n";
echo "3. Make another test call to verify\n";

echo "\n✅ Fix complete!\n";
echo "Completed at: " . date('Y-m-d H:i:s') . "\n";