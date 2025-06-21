<?php

/**
 * Retell MCP Integration Example
 * 
 * This example shows how to integrate with the new MCP-based Retell webhook controller
 */

// Example 1: Testing the MCP webhook endpoint
function testMCPWebhook() {
    $webhookUrl = 'https://api.askproai.de/api/mcp/retell/webhook';
    
    // Example call_ended webhook payload
    $payload = [
        'event' => 'call_ended',
        'call' => [
            'call_id' => '550e8400-e29b-41d4-a716-446655440000',
            'agent_id' => 'agent_xxxxxx',
            'call_type' => 'inbound',
            'call_status' => 'ended',
            'from_number' => '+4930123456789',
            'to_number' => '+4930987654321',
            'start_timestamp' => time() - 300,
            'end_timestamp' => time(),
            'duration' => 300,
            'transcript' => 'Kunde: Hallo, ich möchte einen Termin buchen...',
            'call_summary' => 'Kunde möchte Haarschnitt-Termin am 25.12. um 14 Uhr',
            'retell_llm_dynamic_variables' => [
                'datum' => '2024-12-25',
                'uhrzeit' => '14:00',
                'name' => 'Max Mustermann',
                'telefon' => '+4930123456789',
                'email' => 'max@example.com',
                'dienstleistung' => 'Haarschnitt',
                'mitarbeiter' => 'Anna',
                'notizen' => 'Erster Besuch, möchte kurzen Schnitt',
                'filiale' => 'Hauptfiliale'
            ]
        ]
    ];
    
    // Calculate signature (example - use actual Retell secret)
    $signature = hash_hmac('sha256', json_encode($payload), 'your-retell-webhook-secret');
    
    // Make request
    $ch = curl_init($webhookUrl);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'x-retell-signature: ' . $signature
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    echo "Response Code: $httpCode\n";
    echo "Response: $response\n";
}

// Example 2: Testing inbound call with availability check
function testInboundCallWithAvailability() {
    $webhookUrl = 'https://api.askproai.de/api/mcp/retell/webhook';
    
    $payload = [
        'event' => 'call_inbound',
        'call_inbound' => [
            'from_number' => '+4930123456789',
            'to_number' => '+4930987654321', // Must match a phone number in your system
            'direction' => 'inbound',
            'call_status' => 'ongoing'
        ],
        'call_id' => '550e8400-e29b-41d4-a716-446655440001',
        'dynamic_variables' => [
            'check_availability' => true,
            'requested_date' => '2024-12-25',
            'requested_time' => '14:00',
            'event_type_id' => 12345,
            'customer_preferences' => 'nachmittags, donnerstags bevorzugt'
        ]
    ];
    
    $signature = hash_hmac('sha256', json_encode($payload), 'your-retell-webhook-secret');
    
    $ch = curl_init($webhookUrl);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'x-retell-signature: ' . $signature
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    $response = curl_exec($ch);
    $responseData = json_decode($response, true);
    
    echo "Available Slots: " . ($responseData['response']['dynamic_variables']['available_slots'] ?? 'None') . "\n";
    echo "Slots Count: " . ($responseData['response']['dynamic_variables']['slots_count'] ?? 0) . "\n";
}

// Example 3: Using the MCP controller directly in Laravel code
function useMCPControllerDirectly() {
    // Get instances from container
    $mcpOrchestrator = app(\App\Services\MCP\MCPOrchestrator::class);
    $contextResolver = app(\App\Services\MCP\MCPContextResolver::class);
    $bookingOrchestrator = app(\App\Services\MCP\MCPBookingOrchestrator::class);
    
    // Example: Resolve context for a phone number
    $phoneNumber = '+4930123456789';
    $context = $contextResolver->resolveFromPhone($phoneNumber);
    
    if ($context['success']) {
        echo "Company: " . $context['company']['name'] . "\n";
        echo "Branch: " . $context['branch']['name'] . "\n";
        echo "Services: " . implode(', ', array_column($context['services'], 'name')) . "\n";
    }
    
    // Example: Check availability through MCP
    $mcpRequest = new \App\Services\MCP\MCPRequest(
        service: 'calcom',
        operation: 'checkAvailability',
        params: [
            'event_type_id' => $context['branch']['calcom_event_type_id'],
            'date' => '2024-12-25',
            'timezone' => 'Europe/Berlin'
        ],
        tenantId: $context['company']['id']
    );
    
    $mcpResponse = $mcpOrchestrator->route($mcpRequest);
    
    if ($mcpResponse->isSuccess()) {
        $availabilityData = $mcpResponse->getData();
        echo "Available slots: " . count($availabilityData['slots'] ?? []) . "\n";
    }
}

// Example 4: Monitoring MCP health
function checkMCPHealth() {
    $mcpOrchestrator = app(\App\Services\MCP\MCPOrchestrator::class);
    
    $health = $mcpOrchestrator->healthCheck();
    
    echo "MCP Health Status: " . $health['status'] . "\n";
    echo "Services:\n";
    foreach ($health['services'] as $service => $status) {
        echo "  - $service: $status\n";
    }
    
    echo "\nMetrics:\n";
    echo "  - Total Requests: " . $health['metrics']['total_requests'] . "\n";
    echo "  - Error Rate: " . $health['metrics']['error_rate'] . "%\n";
    echo "  - Avg Latency: " . $health['metrics']['avg_latency_ms'] . "ms\n";
}

// Example 5: Gradual migration using both endpoints
function gradualMigration() {
    // Enable migration mode in config/features.php
    config(['features.mcp_migration_mode' => true]);
    
    // Old endpoint will now forward to MCP controller
    $oldEndpoint = 'https://api.askproai.de/api/retell/webhook';
    $newEndpoint = 'https://api.askproai.de/api/mcp/retell/webhook';
    
    // You can use either endpoint during migration
    // Monitor logs to track usage:
    // grep "Legacy route used" storage/logs/laravel.log
}

// Run examples
if (php_sapi_name() === 'cli') {
    echo "=== Retell MCP Integration Examples ===\n\n";
    
    echo "1. Testing MCP Webhook:\n";
    testMCPWebhook();
    
    echo "\n2. Testing Inbound Call:\n";
    testInboundCallWithAvailability();
    
    echo "\n3. Direct MCP Usage:\n";
    useMCPControllerDirectly();
    
    echo "\n4. MCP Health Check:\n";
    checkMCPHealth();
}