<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);
$kernel->terminate($request, $response);

echo "=== RETELL WEBHOOK ISSUE ANALYSIS ===\n\n";

// 1. Check Retell agent configuration
echo "1. CHECKING RETELL AGENT CONFIGURATION:\n";
$company = \App\Models\Company::first();
if ($company) {
    $retellAgent = \App\Models\RetellAgent::withoutGlobalScopes()->where('company_id', $company->id)->first();
    if ($retellAgent) {
        echo "✓ Retell agent found for company: " . $company->name . "\n";
        echo "  Agent ID: " . $retellAgent->agent_id . "\n";
        
        // Check custom functions
        $config = is_string($retellAgent->configuration) 
            ? json_decode($retellAgent->configuration, true) 
            : $retellAgent->configuration;
        if (isset($config['functions'])) {
            echo "  Custom functions configured: " . count($config['functions']) . "\n";
            foreach ($config['functions'] as $func) {
                echo "    - " . $func['name'] . " (endpoint: " . ($func['url'] ?? 'NOT SET') . ")\n";
            }
        } else {
            echo "  ⚠️  No custom functions configured\n";
        }
    } else {
        echo "✗ No Retell agent found for company\n";
    }
}

// 2. Check if webhook URL is properly configured
echo "\n2. WEBHOOK CONFIGURATION:\n";
echo "Expected webhook URL: https://api.askproai.de/api/retell/function-call\n";

// 3. Check recent errors in error log
echo "\n3. RECENT ERROR LOGS:\n";
$errorLog = storage_path('logs/laravel.log');
$errors = shell_exec("grep -i 'error' $errorLog | grep -i 'retell' | tail -5");
echo $errors ?: "No recent Retell errors found\n";

// 4. Check middleware configuration
echo "\n4. MIDDLEWARE CHECK:\n";
$kernel = app(\App\Http\Kernel::class);
$middlewareGroups = $kernel->getMiddlewareGroups();
if (isset($middlewareGroups['api'])) {
    echo "API middleware group includes:\n";
    foreach ($middlewareGroups['api'] as $middleware) {
        echo "  - " . $middleware . "\n";
    }
}

// 5. Test signature verification
echo "\n5. SIGNATURE VERIFICATION TEST:\n";
try {
    $webhookProcessor = app(\App\Services\WebhookProcessor::class);
    echo "✓ WebhookProcessor service available\n";
    
    // Check if Retell webhook secret is configured
    $secret = config('services.retell.webhook_secret');
    if ($secret) {
        echo "✓ Retell webhook secret is configured\n";
    } else {
        echo "✗ Retell webhook secret is NOT configured\n";
    }
} catch (\Exception $e) {
    echo "✗ Error loading WebhookProcessor: " . $e->getMessage() . "\n";
}

// 6. Check if the endpoint is reachable
echo "\n6. ENDPOINT REACHABILITY:\n";
$route = app('router')->getRoutes()->match(
    app('request')->create('/api/retell/function-call', 'POST')
);
if ($route) {
    echo "✓ Route /api/retell/function-call is registered\n";
    echo "  Controller: " . $route->getActionName() . "\n";
    echo "  Middleware: " . implode(', ', $route->gatherMiddleware()) . "\n";
} else {
    echo "✗ Route not found\n";
}

// 7. Check recent webhook events
echo "\n7. RECENT WEBHOOK ACTIVITY:\n";
$recentWebhooks = \App\Models\WebhookEvent::withoutGlobalScopes()
    ->where('provider', 'retell')
    ->where('created_at', '>=', now()->subHours(24))
    ->orderBy('created_at', 'desc')
    ->limit(5)
    ->get();

foreach ($recentWebhooks as $webhook) {
    $payload = is_string($webhook->payload) 
        ? json_decode($webhook->payload, true) 
        : $webhook->payload;
    echo sprintf(
        "[%s] Type: %s, Status: %s\n",
        $webhook->created_at,
        $webhook->event_type,
        $webhook->status
    );
    if (isset($payload['function_name'])) {
        echo "  Function: " . $payload['function_name'] . "\n";
    }
}

echo "\n=== RECOMMENDATIONS ===\n";
echo "1. Ensure the Retell agent has the correct webhook URL configured\n";
echo "2. Check if the webhook secret is properly set in both Retell and .env\n";
echo "3. Verify that the agent's custom functions are pointing to the correct endpoint\n";
echo "4. Make sure Horizon is running to process webhook jobs\n";