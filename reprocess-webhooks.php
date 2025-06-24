<?php

use App\Models\WebhookEvent;
use App\Services\WebhookProcessor;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=================================\n";
echo "Reprocess Pending Webhooks\n";
echo "=================================\n\n";

// Get pending webhooks
$pendingWebhooks = WebhookEvent::where('status', 'pending')
    ->where('provider', 'retell')
    ->orderBy('created_at', 'asc')
    ->get();

echo "Found " . count($pendingWebhooks) . " pending Retell webhooks\n\n";

if (count($pendingWebhooks) === 0) {
    echo "No pending webhooks to process.\n";
    exit(0);
}

$webhookProcessor = app(WebhookProcessor::class);
$processed = 0;
$failed = 0;

// Disable async processing for this script
config(['services.webhook.async.retell' => false]);

foreach ($pendingWebhooks as $webhook) {
    echo "Processing webhook ID: {$webhook->id}\n";
    
    try {
        // Call the retry method which will reprocess the webhook
        $result = $webhookProcessor->retry($webhook->id);
        
        if ($result['success']) {
            $processed++;
            echo "  ✅ Processed successfully\n";
        } else {
            $failed++;
            echo "  ❌ Failed: " . ($result['message'] ?? 'Unknown error') . "\n";
        }
        
    } catch (\Exception $e) {
        $failed++;
        echo "  ❌ Failed: " . $e->getMessage() . "\n";
        
        // Log the error
        Log::error('Failed to reprocess webhook', [
            'webhook_id' => $webhook->id,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
    }
    
    echo "\n";
}

echo "=================================\n";
echo "Summary\n";
echo "=================================\n";
echo "✅ Processed: {$processed}\n";
echo "❌ Failed: {$failed}\n";
echo "\nDone.\n";