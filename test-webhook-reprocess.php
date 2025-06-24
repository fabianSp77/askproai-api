<?php

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->bootstrap();

// Get the last call_analyzed webhook
$webhook = \App\Models\WebhookEvent::where('event', 'call_analyzed')
    ->where('source', 'mcp')
    ->orderBy('created_at', 'desc')
    ->first();

if ($webhook) {
    echo "Reprocessing webhook ID: {$webhook->id}\n";
    
    // Get the payload
    $payload = $webhook->payload;
    
    // Create a new request with the payload
    $request = new \Illuminate\Http\Request();
    $request->merge($payload);
    
    // Add correlation ID
    $request->headers->set('x-correlation-id', \Illuminate\Support\Str::uuid()->toString());
    
    // Process through MCP webhook controller
    $controller = app(\App\Http\Controllers\MCPWebhookController::class);
    $result = $controller->handleRetellWebhook($request);
    
    echo "Result: " . json_encode($result->getData(), JSON_PRETTY_PRINT) . "\n";
} else {
    echo "No call_analyzed webhook found\n";
}