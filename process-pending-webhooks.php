<?php

require_once __DIR__ . '/vendor/autoload.php';

use App\Models\WebhookEvent;
use App\Jobs\ProcessRetellWebhookJob;
use Illuminate\Support\Facades\Queue;

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== PROCESSING PENDING WEBHOOKS ===\n\n";

try {
    // 1. Check Horizon status
    echo "1. CHECKING HORIZON STATUS:\n";
    $horizonStatus = `php artisan horizon:status 2>&1`;
    echo $horizonStatus . "\n\n";
    
    // 2. Get pending webhooks for our test number
    echo "2. PENDING WEBHOOKS FOR TEST NUMBER:\n";
    $pendingWebhooks = WebhookEvent::where('status', 'pending')
        ->where('provider', 'retell')
        ->where(function($query) {
            $query->whereJsonContains('payload->call->to_number', '+493083793369')
                  ->orWhereJsonContains('payload->call->to_number', '+49 30 837 93 369');
        })
        ->orderBy('created_at', 'desc')
        ->get();
    
    echo "Found " . $pendingWebhooks->count() . " pending webhooks for +493083793369\n\n";
    
    // Process them automatically
    foreach ($pendingWebhooks as $webhook) {
        $payload = is_string($webhook->payload) ? json_decode($webhook->payload, true) : $webhook->payload;
        
        echo "Processing Webhook ID: " . $webhook->id . "\n";
        echo "  - Event: " . $webhook->event_type . "\n";
        echo "  - Call ID: " . ($payload['call']['call_id'] ?? $payload['call_id'] ?? 'N/A') . "\n";
        
        try {
            // Process synchronously
            $job = new ProcessRetellWebhookJob($webhook, $webhook->correlation_id ?? \Str::uuid());
            $job->handle();
            
            echo "  ✓ Processed successfully\n\n";
            
        } catch (\Exception $e) {
            echo "  ✗ Error: " . $e->getMessage() . "\n\n";
        }
    }
    
    // 3. Check if Horizon is running
    echo "\n3. HORIZON CHECK:\n";
    $horizonRunning = `ps aux  < /dev/null |  grep "horizon" | grep -v grep | wc -l`;
    if (trim($horizonRunning) > 0) {
        echo "✓ Horizon is running\n";
    } else {
        echo "✗ Horizon is NOT running\n";
        echo "Starting Horizon...\n";
        exec('nohup php artisan horizon > /dev/null 2>&1 &');
        sleep(2);
        echo "Horizon started in background\n";
    }
    
} catch (\Exception $e) {
    echo "\n❌ ERROR: " . $e->getMessage() . "\n";
}
