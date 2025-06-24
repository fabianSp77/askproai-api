<?php

use App\Models\WebhookEvent;
use App\Jobs\ProcessRetellWebhookJob;
use Illuminate\Support\Facades\Log;

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=================================\n";
echo "Process Webhooks Using Jobs\n";
echo "=================================\n\n";

// Get pending webhooks
$pendingWebhooks = WebhookEvent::where('status', 'pending')
    ->where('provider', 'retell')
    ->whereIn('event_type', ['call_ended', 'call_analyzed'])
    ->orderBy('created_at', 'asc')
    ->limit(5) // Process 5 at a time
    ->get();

echo "Found " . count($pendingWebhooks) . " pending call webhooks\n\n";

if (count($pendingWebhooks) === 0) {
    echo "No pending webhooks to process.\n";
    exit(0);
}

$processed = 0;

foreach ($pendingWebhooks as $webhook) {
    echo "Dispatching webhook ID: {$webhook->id} (Event: {$webhook->event_type})\n";
    
    try {
        // Create job instance
        $job = new ProcessRetellWebhookJob(
            $webhook,
            $webhook->correlation_id ?? \Str::uuid()->toString()
        );
        
        // Process synchronously for testing
        $job->handle(
            app(\App\Services\WebhookProcessor::class),
            app(\App\Services\Webhook\EnhancedWebhookDeduplicationService::class)
        );
        
        $processed++;
        echo "  ✅ Processed successfully\n\n";
        
    } catch (\Exception $e) {
        echo "  ❌ Failed: " . $e->getMessage() . "\n\n";
        
        Log::error('Failed to process webhook via job', [
            'webhook_id' => $webhook->id,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
    }
}

echo "=================================\n";
echo "Summary\n";
echo "=================================\n";
echo "✅ Processed: {$processed}\n";
echo "\nDone.\n";