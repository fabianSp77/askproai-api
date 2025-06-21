<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Services\WebhookProcessor;
use Illuminate\Support\Str;

echo "Testing async webhook processing...\n\n";

// Test payload
$payload = [
    'event' => 'call_ended',
    'call_id' => 'test_' . Str::uuid(),
    'call' => [
        'call_id' => 'test_' . Str::uuid(),
        'from_number' => '+49 151 12345678',
        'to_number' => '+49 30 837 93 369',
        'direction' => 'inbound',
        'start_timestamp' => time() * 1000,
        'end_timestamp' => (time() + 180) * 1000,
        'call_duration' => 180,
        'disconnection_reason' => 'user_hangup',
        'agent_id' => config('services.retell.default_agent_id'),
        'retell_llm_dynamic_variables' => [
            'booking_confirmed' => false,
            'datum' => null,
            'uhrzeit' => null,
            'kundenwunsch' => 'Test call'
        ]
    ]
];

$headers = [
    'x-retell-signature' => ['test_signature'],
    'content-type' => ['application/json']
];

$correlationId = Str::uuid()->toString();

try {
    $webhookProcessor = app(WebhookProcessor::class);
    
    echo "Processing webhook with correlation ID: $correlationId\n";
    
    $result = $webhookProcessor->process(
        'retell',
        $payload,
        $headers,
        $correlationId
    );
    
    echo "\nResult:\n";
    print_r($result);
    
    if ($result['queued'] ?? false) {
        echo "\nWebhook was queued for async processing.\n";
        echo "Webhook Event ID: " . ($result['webhook_event_id'] ?? 'N/A') . "\n";
        
        // Check job queue
        $jobs = DB::table('jobs')
            ->where('queue', 'like', '%webhook%')
            ->where('created_at', '>', now()->subMinutes(1))
            ->get();
            
        echo "\nJobs in webhook queue: " . $jobs->count() . "\n";
        
        // Check webhook event status
        if ($result['webhook_event_id']) {
            $event = \App\Models\WebhookEvent::find($result['webhook_event_id']);
            echo "\nWebhook Event Status: " . ($event->status ?? 'Not found') . "\n";
        }
    } else {
        echo "\nWebhook was processed synchronously.\n";
    }
    
} catch (\Exception $e) {
    echo "\nError: " . $e->getMessage() . "\n";
    echo "Trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\nChecking Horizon status...\n";
$horizonStatus = exec('php artisan horizon:status');
echo "Horizon: $horizonStatus\n";

echo "\nChecking Redis connection...\n";
$redis = \Illuminate\Support\Facades\Redis::connection();
try {
    $redis->ping();
    echo "Redis: Connected\n";
} catch (\Exception $e) {
    echo "Redis: Failed - " . $e->getMessage() . "\n";
}

echo "\nDone.\n";