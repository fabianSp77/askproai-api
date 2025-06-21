#!/usr/bin/env php
<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

$response = $kernel->handle(
    $request = Illuminate\Http\Request::create('/api/health', 'GET')
);

echo "Health Check Response:\n";
echo $response->getContent() . "\n\n";

// Test MCP services
try {
    echo "Testing MCP Services:\n";
    
    // Test MCPOrchestrator
    $orchestrator = $app->make(\App\Services\MCP\MCPOrchestrator::class);
    echo "✓ MCPOrchestrator loaded\n";
    
    // Test MCPContextResolver
    $contextResolver = $app->make(\App\Services\MCP\MCPContextResolver::class);
    echo "✓ MCPContextResolver loaded\n";
    
    // Test MCPBookingOrchestrator
    $bookingOrchestrator = $app->make(\App\Services\MCP\MCPBookingOrchestrator::class);
    echo "✓ MCPBookingOrchestrator loaded\n";
    
    // Test ApiRateLimiter
    $rateLimiter = $app->make(\App\Services\RateLimiter\ApiRateLimiter::class);
    echo "✓ ApiRateLimiter loaded\n";
    
    // Test controller
    $controller = $app->make(\App\Http\Controllers\RetellWebhookMCPController::class);
    echo "✓ RetellWebhookMCPController loaded\n";
    
    echo "\nAll MCP services loaded successfully!\n";
    
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}

$kernel->terminate($request, $response);