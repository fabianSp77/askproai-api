<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\WebhookEvent;
use App\Models\Call;
use App\Scopes\TenantScope;
use Illuminate\Support\Facades\DB;

echo "=== Checking VERY Latest Webhooks ===\n\n";
echo "Current Time: " . now() . "\n\n";

// Get the absolute latest webhooks
echo "1. Latest 10 Webhook Events (no time filter):\n";
echo str_repeat("=", 100) . "\n";

$latestWebhooks = WebhookEvent::withoutGlobalScope(TenantScope::class)
    ->where('provider', 'retell')
    ->orderBy('created_at', 'desc')
    ->limit(10)
    ->get();

foreach ($latestWebhooks as $webhook) {
    echo "\nWebhook ID: {$webhook->id}\n";
    echo "Type: {$webhook->event_type} | Status: {$webhook->status}\n";
    echo "Created: {$webhook->created_at} (" . $webhook->created_at->diffForHumans() . ")\n";
    
    $payload = is_string($webhook->payload) ? json_decode($webhook->payload, true) : $webhook->payload;
    
    if (isset($payload['call'])) {
        $call = $payload['call'];
        echo "Call ID: " . ($call['call_id'] ?? 'N/A') . "\n";
        echo "From: " . ($call['from_number'] ?? 'N/A') . "\n";
        echo "To: " . ($call['to_number'] ?? 'N/A') . "\n";
        
        // Check for appointment info
        if (isset($call['call_analysis']['custom_analysis_data']['appointment_made'])) {
            echo "✅ Appointment Made: " . ($call['call_analysis']['custom_analysis_data']['appointment_made'] ? 'YES' : 'NO') . "\n";
            if (isset($call['call_analysis']['custom_analysis_data']['appointment_date_time'])) {
                echo "   Time: " . $call['call_analysis']['custom_analysis_data']['appointment_date_time'] . "\n";
            }
        }
        
        // Check transcript for 16 Uhr mention
        if (isset($call['transcript'])) {
            if (stripos($call['transcript'], '16 uhr') !== false || stripos($call['transcript'], '16:00') !== false) {
                echo "📞 TRANSCRIPT MENTIONS 16 UHR!\n";
                echo "   " . substr($call['transcript'], 0, 200) . "...\n";
            }
        }
    }
    
    echo str_repeat("-", 50) . "\n";
}

// Check the latest calls
echo "\n2. Latest 10 Calls:\n";
echo str_repeat("=", 100) . "\n";

$latestCalls = Call::withoutGlobalScope(TenantScope::class)
    ->orderBy('created_at', 'desc')
    ->limit(10)
    ->get();

foreach ($latestCalls as $call) {
    echo "\nCall ID: {$call->id} | Retell ID: {$call->retell_call_id}\n";
    echo "Created: {$call->created_at} (" . $call->created_at->diffForHumans() . ")\n";
    echo "From: {$call->from_number} | To: {$call->to_number}\n";
    echo "Status: {$call->status}\n";
    echo "Has Appointment: " . ($call->appointment_id ? "YES (ID: {$call->appointment_id})" : "NO") . "\n";
    
    // Check transcript
    if ($call->transcript && (stripos($call->transcript, '16 uhr') !== false || stripos($call->transcript, '16:00') !== false)) {
        echo "📞 TRANSCRIPT MENTIONS 16 UHR!\n";
    }
    
    echo str_repeat("-", 50) . "\n";
}

// Raw database query to double-check
echo "\n3. Raw Database Check (last 5 entries):\n";
echo str_repeat("=", 100) . "\n";

$rawWebhooks = DB::select("
    SELECT id, event_type, status, created_at, 
           JSON_EXTRACT(payload, '$.call.call_id') as call_id,
           JSON_EXTRACT(payload, '$.call.from_number') as from_number
    FROM webhook_events 
    WHERE provider = 'retell' 
    ORDER BY id DESC 
    LIMIT 5
");

foreach ($rawWebhooks as $webhook) {
    echo "ID: {$webhook->id} | Type: {$webhook->event_type} | Created: {$webhook->created_at}\n";
    echo "Call ID: " . trim($webhook->call_id ?? 'null', '"') . " | From: " . trim($webhook->from_number ?? 'null', '"') . "\n";
    echo str_repeat("-", 30) . "\n";
}

echo "\n=== Check Complete ===\n";