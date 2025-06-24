<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== Checking Webhook Status ===\n\n";

// Check if retell_webhooks table exists
echo "1. Checking if retell_webhooks table exists:\n";
try {
    $tableExists = DB::select("SHOW TABLES LIKE 'retell_webhooks'");
    if (empty($tableExists)) {
        echo "   ❌ Table 'retell_webhooks' does not exist!\n";
        echo "   This table is needed for MCP webhook processing.\n";
        echo "   Run: php artisan migrate\n";
    } else {
        echo "   ✅ Table 'retell_webhooks' exists\n";
        
        // Get recent webhooks
        $webhooks = DB::table('retell_webhooks')
            ->select('id', 'call_id', 'event_type', 'processed', 'created_at')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();
            
        if ($webhooks->isEmpty()) {
            echo "   No webhooks recorded yet\n";
        } else {
            echo "   Recent webhooks:\n";
            foreach ($webhooks as $webhook) {
                echo sprintf("   - [%s] %s - %s (Processed: %s)\n",
                    $webhook->created_at,
                    $webhook->event_type,
                    substr($webhook->call_id, 0, 20) . '...',
                    $webhook->processed ? 'Yes' : 'No'
                );
            }
        }
    }
} catch (\Exception $e) {
    echo "   Error: " . $e->getMessage() . "\n";
}

// Check recent calls
echo "\n2. Recent calls in system:\n";
try {
    $calls = DB::table('calls')
        ->select('id', 'call_id', 'created_at')
        ->orderBy('created_at', 'desc')
        ->limit(5)
        ->get();
        
    if ($calls->isEmpty()) {
        echo "   No calls found\n";
    } else {
        foreach ($calls as $call) {
            echo sprintf("   - [%s] %s\n",
                $call->created_at,
                substr($call->call_id, 0, 30) . '...'
            );
        }
    }
} catch (\Exception $e) {
    echo "   Error: " . $e->getMessage() . "\n";
}

// Check MCP webhook controller
echo "\n3. MCP Webhook Controller Status:\n";
$controllerPath = app_path('Http/Controllers/Api/MCPWebhookController.php');
if (file_exists($controllerPath)) {
    echo "   ✅ MCPWebhookController exists\n";
} else {
    echo "   ❌ MCPWebhookController not found at expected location\n";
}

// Check route
echo "\n4. Route Configuration:\n";
try {
    $route = app('router')->getRoutes()->getByName('mcp.webhook.retell');
    if ($route) {
        echo "   ✅ Route 'mcp.webhook.retell' is registered\n";
        echo "   URI: " . $route->uri() . "\n";
        echo "   Action: " . $route->getActionName() . "\n";
    } else {
        echo "   ❌ Route 'mcp.webhook.retell' not found\n";
    }
} catch (\Exception $e) {
    echo "   Error checking route: " . $e->getMessage() . "\n";
}

echo "\nDone!\n";