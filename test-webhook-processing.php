<?php

require_once __DIR__ . '/vendor/autoload.php';

use Illuminate\Support\Facades\DB;

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== WEBHOOK PROCESSING TEST ===\n\n";

// Check recent webhook events
echo "Recent webhook events:\n";
$webhooks = DB::table('webhook_events')
    ->where('created_at', '>=', now()->subHours(24))
    ->orderBy('created_at', 'desc')
    ->limit(10)
    ->get();

foreach ($webhooks as $webhook) {
    echo sprintf(
        "ID: %d | Type: %s | Status: %s | Created: %s\n",
        $webhook->id,
        $webhook->type,
        $webhook->status,
        $webhook->created_at
    );
    
    $payload = json_decode($webhook->payload, true);
    if ($payload) {
        echo "  Event Type: " . ($payload['event_type'] ?? 'N/A') . "\n";
        echo "  Call ID: " . ($payload['call_id'] ?? 'N/A') . "\n";
        if (isset($payload['webhook_validated'])) {
            echo "  Webhook Validated: " . ($payload['webhook_validated'] ? 'YES' : 'NO') . "\n";
        }
    }
    echo "\n";
}

// Check queue status
echo "\nQueue Status (Redis):\n";
try {
    $redis = app('redis');
    
    // Check various queue keys
    $defaultQueue = $redis->llen('queues:default');
    $webhooksQueue = $redis->llen('queues:webhooks');
    $webhooksHighQueue = $redis->llen('queues:webhooks-high');
    
    echo "Default Queue: $defaultQueue jobs\n";
    echo "Webhooks Queue: $webhooksQueue jobs\n";
    echo "Webhooks High Queue: $webhooksHighQueue jobs\n";
    
    // Check failed jobs
    $failedJobs = DB::table('failed_jobs')->count();
    echo "Failed Jobs: $failedJobs\n";
    
} catch (\Exception $e) {
    echo "Error accessing Redis: " . $e->getMessage() . "\n";
}

// Check Horizon metrics
echo "\nHorizon Metrics:\n";
try {
    $horizonStatus = \Laravel\Horizon\Contracts\MetricsRepository::class;
    $metrics = app($horizonStatus);
    
    $throughput = $metrics->throughput();
    echo "Throughput (last hour): " . array_sum($throughput) . " jobs\n";
    
    $runtime = $metrics->runtime('webhooks');
    if ($runtime) {
        echo "Webhook Queue Runtime: " . round(array_sum($runtime) / count($runtime), 2) . "ms avg\n";
    }
} catch (\Exception $e) {
    echo "Could not fetch Horizon metrics: " . $e->getMessage() . "\n";
}

// Check webhook endpoint configuration
echo "\nWebhook Configuration:\n";
echo "Webhook URL: " . config('app.url') . "/api/retell/webhook\n";
echo "Webhook Secret: " . (env('RETELL_WEBHOOK_SECRET') ? 'SET' : 'NOT SET') . "\n";

// Test webhook signature verification
echo "\nTesting webhook signature verification...\n";
$testPayload = json_encode(['test' => true, 'timestamp' => time()]);
$webhookSecret = env('RETELL_WEBHOOK_SECRET');

if ($webhookSecret) {
    $signature = hash_hmac('sha256', $testPayload, $webhookSecret);
    echo "Test signature generated: " . substr($signature, 0, 20) . "...\n";
    echo "This signature should be sent as 'X-Retell-Signature' header\n";
} else {
    echo "WARNING: No webhook secret configured!\n";
}