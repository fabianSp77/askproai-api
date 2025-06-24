<?php

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\WebhookEvent;
use App\Models\Call;
use Carbon\Carbon;

echo "=== Checking Recent Webhook Activity ===\n\n";

// Check last 30 minutes of webhook events
$recentWebhooks = WebhookEvent::withoutGlobalScopes()
    ->where('created_at', '>', Carbon::now()->subMinutes(30))
    ->orderBy('created_at', 'desc')
    ->get();

echo "Webhook Events (last 30 minutes): " . $recentWebhooks->count() . "\n\n";

foreach ($recentWebhooks as $webhook) {
    echo "Time: " . $webhook->created_at . "\n";
    echo "Provider: " . $webhook->provider . "\n";
    echo "Event Type: " . $webhook->event_type . "\n";
    echo "Status: " . $webhook->status . "\n";
    
    $payload = json_decode($webhook->payload, true);
    if (isset($payload['call'])) {
        echo "Call ID: " . ($payload['call']['call_id'] ?? 'N/A') . "\n";
        echo "From: " . ($payload['call']['from_number'] ?? 'N/A') . "\n";
        echo "To: " . ($payload['call']['to_number'] ?? 'N/A') . "\n";
    }
    echo "---\n\n";
}

// Check recent calls
echo "\n=== Recent Calls (last 30 minutes) ===\n\n";

$recentCalls = Call::withoutGlobalScopes()
    ->where('created_at', '>', Carbon::now()->subMinutes(30))
    ->orderBy('created_at', 'desc')
    ->get();

echo "Calls found: " . $recentCalls->count() . "\n\n";

foreach ($recentCalls as $call) {
    echo "Time: " . $call->created_at . "\n";
    echo "ID: " . $call->id . "\n";
    echo "Retell ID: " . $call->retell_call_id . "\n";
    echo "From: " . $call->from_number . "\n";
    echo "To: " . $call->to_number . "\n";
    echo "Status: " . $call->call_status . "\n";
    echo "Duration: " . $call->duration_seconds . " seconds\n";
    
    if ($call->retell_llm_dynamic_variables) {
        echo "Dynamic Variables: " . json_encode($call->retell_llm_dynamic_variables) . "\n";
    }
    echo "---\n\n";
}

// Check MCP metrics
echo "\n=== MCP Metrics (last hour) ===\n\n";

use App\Models\McpMetric;

$mcpMetrics = McpMetric::withoutGlobalScopes()
    ->where('created_at', '>', Carbon::now()->subHour())
    ->orderBy('created_at', 'desc')
    ->limit(10)
    ->get();

echo "MCP Metrics: " . $mcpMetrics->count() . "\n\n";

foreach ($mcpMetrics as $metric) {
    echo "Time: " . $metric->created_at . "\n";
    echo "Server: " . $metric->server . "\n";
    echo "Tool: " . $metric->tool . "\n";
    echo "Status: " . $metric->status . "\n";
    echo "Duration: " . $metric->duration_ms . " ms\n";
    echo "---\n\n";
}