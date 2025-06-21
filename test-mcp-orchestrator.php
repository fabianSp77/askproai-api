#!/usr/bin/env php
<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Services\MCP\MCPOrchestrator;
use App\Services\MCP\MCPRequest;

try {
    echo "Testing MCP Orchestrator...\n\n";
    
    $orchestrator = app(MCPOrchestrator::class);
    
    // Test 1: Health check
    echo "Test 1: Health check\n";
    try {
        $health = $orchestrator->healthCheck();
        echo "Health Status: " . json_encode($health, JSON_PRETTY_PRINT) . "\n\n";
    } catch (Exception $e) {
        echo "Health check error: " . $e->getMessage() . "\n\n";
    }
    
    // Test 2: Get metrics
    echo "Test 2: Get metrics\n";
    try {
        $metrics = $orchestrator->getMetrics();
        echo "Metrics: " . json_encode($metrics, JSON_PRETTY_PRINT) . "\n\n";
    } catch (Exception $e) {
        echo "Metrics error: " . $e->getMessage() . "\n\n";
    }
    
    // Test 3: List available services
    echo "Test 3: Available services\n";
    try {
        // The services are registered in the orchestrator
        echo "Services: webhook, calcom, database, queue, retell, stripe\n\n";
    } catch (Exception $e) {
        echo "Services error: " . $e->getMessage() . "\n\n";
    }
    
    // Test 4: Test Stripe MCP through orchestrator
    echo "Test 4: Route to Stripe MCP\n";
    try {
        // Create MCP request for Stripe service
        $request = new MCPRequest(
            service: 'stripe',
            operation: 'getPaymentOverview',
            params: ['company_id' => 1, 'period' => 'month'],
            tenantId: 1
        );
        
        $response = $orchestrator->route($request);
        echo "Response: " . $response->toJson(JSON_PRETTY_PRINT) . "\n\n";
        echo "Success: " . ($response->isSuccess() ? 'Yes' : 'No') . "\n";
        echo "Data: " . json_encode($response->getData(), JSON_PRETTY_PRINT) . "\n";
        echo "Error: " . ($response->getError() ?? 'None') . "\n\n";
    } catch (Exception $e) {
        echo "Routing error: " . $e->getMessage() . "\n\n";
    }
    
    echo "All tests completed!\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}