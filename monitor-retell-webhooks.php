<?php

/**
 * Monitor Retell Webhooks
 * 
 * Prüft warum Webhooks nicht verarbeitet werden
 */

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\WebhookEvent;
use App\Models\Call;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

echo "\n=== Retell Webhook Monitoring ===\n";
echo "Started at: " . date('Y-m-d H:i:s') . "\n\n";

// 1. Check webhook events
echo "1. Recent webhook events:\n";
$recentWebhooks = WebhookEvent::where('provider', 'retell')
    ->orderBy('created_at', 'desc')
    ->limit(10)
    ->get();

if ($recentWebhooks->isEmpty()) {
    echo "   ⚠️  No recent Retell webhooks found!\n";
    echo "   This means webhooks are not reaching the server.\n";
} else {
    foreach ($recentWebhooks as $webhook) {
        echo sprintf("   - %s: %s (%s) - %s\n",
            $webhook->created_at->format('Y-m-d H:i:s'),
            $webhook->event_type,
            $webhook->status,
            $webhook->error_message ?? 'OK'
        );
    }
}

// 2. Check failed jobs
echo "\n2. Failed webhook jobs:\n";
$failedJobs = DB::table('failed_jobs')
    ->where('payload', 'like', '%retell%')
    ->orderBy('failed_at', 'desc')
    ->limit(5)
    ->get();

if ($failedJobs->isEmpty()) {
    echo "   ✅ No failed Retell jobs\n";
} else {
    foreach ($failedJobs as $job) {
        $payload = json_decode($job->payload, true);
        echo sprintf("   - %s: %s - %s\n",
            $job->failed_at,
            $payload['displayName'] ?? 'Unknown',
            substr($job->exception, 0, 100)
        );
    }
}

// 3. Check webhook URL configuration
echo "\n3. Webhook configuration:\n";
$webhookUrl = config('app.url') . '/api/retell/webhook';
$apiKey = config('services.retell.api_key');

echo "   Webhook URL: $webhookUrl\n";
echo "   API Key: " . (empty($apiKey) ? '❌ NOT SET' : '✅ Set') . "\n";

// 4. Test Retell API connection
echo "\n4. Testing Retell API connection:\n";
try {
    $response = Http::withHeaders([
        'Authorization' => 'Bearer ' . $apiKey,
    ])->post('https://api.retellai.com/v2/list-calls', [
        'limit' => 1,
        'sort_order' => 'descending'
    ]);
    
    if ($response->successful()) {
        $data = $response->json();
        $callCount = count($data['results'] ?? []);
        echo "   ✅ API connection successful\n";
        echo "   Found $callCount recent calls in Retell\n";
        
        if ($callCount > 0) {
            $latestCall = $data['results'][0];
            echo "   Latest call: " . $latestCall['call_id'] . " at " . 
                 date('Y-m-d H:i:s', $latestCall['start_timestamp'] / 1000) . "\n";
            
            // Check if this call exists in our database
            $exists = Call::withoutGlobalScope(\App\Scopes\TenantScope::class)
                ->where('retell_call_id', $latestCall['call_id'])
                ->exists();
                
            if (!$exists) {
                echo "   ⚠️  This call is NOT in our database!\n";
            } else {
                echo "   ✅ This call is in our database\n";
            }
        }
    } else {
        echo "   ❌ API connection failed: " . $response->status() . "\n";
        echo "   " . $response->body() . "\n";
    }
} catch (\Exception $e) {
    echo "   ❌ Error: " . $e->getMessage() . "\n";
}

// 5. Check queue configuration
echo "\n5. Queue configuration:\n";
$defaultQueue = config('queue.default');
$webhookQueue = config('queue.connections.redis.queue', 'default');
echo "   Default queue: $defaultQueue\n";
echo "   Webhook queue: webhooks (should be processed by Horizon)\n";

// 6. Recent calls in database
echo "\n6. Recent calls in database:\n";
$recentCalls = Call::withoutGlobalScope(\App\Scopes\TenantScope::class)
    ->orderBy('created_at', 'desc')
    ->limit(5)
    ->get();

if ($recentCalls->isEmpty()) {
    echo "   ⚠️  No calls in database\n";
} else {
    foreach ($recentCalls as $call) {
        echo sprintf("   - %s: %s (%s) - %s\n",
            $call->created_at->format('Y-m-d H:i:s'),
            substr($call->retell_call_id ?? $call->call_id ?? 'unknown', 0, 20),
            $call->duration_sec . 's',
            $call->session_outcome ?? 'Unknown'
        );
    }
}

// 7. Check webhook route
echo "\n7. Checking webhook route:\n";
try {
    $routes = \Illuminate\Support\Facades\Route::getRoutes();
    $found = false;
    foreach ($routes as $route) {
        if ($route->uri() === 'api/retell/webhook') {
            $found = true;
            echo "   ✅ Route found: " . $route->methods()[0] . " " . $route->uri() . "\n";
            echo "   Controller: " . $route->getActionName() . "\n";
            echo "   Middleware: " . implode(', ', $route->gatherMiddleware()) . "\n";
            break;
        }
    }
    if (!$found) {
        echo "   ❌ Webhook route not found!\n";
    }
} catch (\Exception $e) {
    echo "   Error checking routes: " . $e->getMessage() . "\n";
}

echo "\n=== Recommendations ===\n";
echo "1. Check Retell Dashboard:\n";
echo "   - Go to https://dashboard.retellai.com/\n";
echo "   - Navigate to Settings > Webhooks\n";
echo "   - Add webhook URL: $webhookUrl\n";
echo "   - Enable events: call_started, call_ended, call_analyzed\n";
echo "\n2. If webhooks are configured but not arriving:\n";
echo "   - Check firewall/security settings\n";
echo "   - Verify SSL certificate is valid\n";
echo "   - Check server logs: tail -f storage/logs/laravel.log\n";
echo "\n3. For immediate fix:\n";
echo "   - Run: php artisan retell:fetch-calls --limit=50\n";
echo "   - This will import recent calls manually\n";

echo "\n✅ Monitoring complete!\n";
echo "Completed at: " . date('Y-m-d H:i:s') . "\n";