<?php

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Call;
use Carbon\Carbon;

echo "=== Checking recent calls ===\n\n";

// Get calls from last hour
$recentCalls = Call::withoutGlobalScopes()
    ->where('created_at', '>', Carbon::now()->subHour())
    ->orderBy('created_at', 'desc')
    ->get();

if ($recentCalls->isEmpty()) {
    echo "No calls found in the last hour.\n";
} else {
    echo "Found " . $recentCalls->count() . " calls in the last hour:\n\n";
    
    foreach ($recentCalls as $call) {
        echo "Call ID: " . $call->id . "\n";
        echo "From: " . $call->from_number . "\n";
        echo "To: " . $call->to_number . "\n";
        echo "Status: " . $call->call_status . "\n";
        echo "Retell ID: " . $call->retell_call_id . "\n";
        echo "Created: " . $call->created_at . "\n";
        echo "Dynamic Vars: " . json_encode($call->retell_llm_dynamic_variables) . "\n";
        echo "---\n\n";
    }
}

// Check webhook events too
echo "\n=== Checking recent webhook events ===\n\n";

use App\Models\WebhookEvent;

$recentWebhooks = WebhookEvent::withoutGlobalScopes()
    ->where('created_at', '>', Carbon::now()->subMinutes(30))
    ->orderBy('created_at', 'desc')
    ->get();

if ($recentWebhooks->isEmpty()) {
    echo "No webhook events found in the last 30 minutes.\n";
} else {
    echo "Found " . $recentWebhooks->count() . " webhook events:\n\n";
    
    foreach ($recentWebhooks as $webhook) {
        echo "Event Type: " . $webhook->event_type . "\n";
        echo "Provider: " . $webhook->provider . "\n";
        echo "Status: " . $webhook->status . "\n";
        echo "Created: " . $webhook->created_at . "\n";
        
        $payload = json_decode($webhook->payload, true);
        if (isset($payload['call'])) {
            echo "Call From: " . ($payload['call']['from_number'] ?? 'N/A') . "\n";
            echo "Call To: " . ($payload['call']['to_number'] ?? 'N/A') . "\n";
        }
        echo "---\n\n";
    }
}