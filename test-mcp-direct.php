<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

// Test direct MCP service usage
try {
    // Get the WebhookMCPServer instance
    $webhookMCP = $app->make(\App\Services\MCP\WebhookMCPServer::class);
    
    echo "=== Testing MCP Services Directly ===\n\n";
    
    // Test webhook data
    $webhookData = [
        'event' => 'call_ended',
        'call' => [
            'call_id' => 'direct-test-' . uniqid(),
            'agent_id' => 'agent_9a8202a740cd3120d96fc27bb40b2c',
            'from_number' => '+491234567890',
            'to_number' => '+493083793369',
            'direction' => 'inbound',
            'duration_ms' => 180000,
            'cost' => 0.30,
            'status' => 'ended',
            'transcript' => 'Test transcript',
            'summary' => 'Test summary',
            'end_timestamp' => time() * 1000,
            'start_timestamp' => (time() - 180) * 1000,
            'call_analysis' => [
                'custom_analysis_data' => [
                    '_name' => 'Direct Test Customer',
                    '_datum__termin' => '2025-06-27',
                    '_uhrzeit__termin' => '15:00',
                    '_email' => 'direct-test@example.com'
                ]
            ],
            'retell_llm_dynamic_variables' => [
                'booking_confirmed' => 'true',
                'datum' => '2025-06-27',
                'uhrzeit' => '15:00',
                'name' => 'Direct Test Customer',
                'dienstleistung' => 'Haarschnitt'
            ]
        ]
    ];
    
    echo "Processing webhook...\n";
    
    // Process the webhook
    $result = $webhookMCP->processRetellWebhook($webhookData);
    
    echo "\nResult:\n";
    echo json_encode($result, JSON_PRETTY_PRINT) . "\n";
    
} catch (\Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "Trace:\n" . $e->getTraceAsString() . "\n";
}