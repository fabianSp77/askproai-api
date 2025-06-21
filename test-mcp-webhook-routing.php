#!/usr/bin/env php
<?php

/**
 * Test script to verify all webhooks are routing through MCP
 */

require_once __DIR__ . '/vendor/autoload.php';

use Illuminate\Support\Facades\Http;

echo "=== Testing MCP Webhook Routing ===\n\n";

// Test endpoints
$baseUrl = 'http://localhost';
$testEndpoints = [
    'Retell Main' => '/api/retell/webhook',
    'Retell Optimized' => '/api/retell/optimized-webhook',
    'Retell Debug' => '/api/retell/debug-webhook',
    'Retell Enhanced' => '/api/retell/enhanced-webhook',
    'Retell MCP Direct' => '/api/retell/mcp-webhook',
    'Retell Unified' => '/api/webhooks/retell',
    'Cal.com Main' => '/api/calcom/webhook',
    'Cal.com Unified' => '/api/webhooks/calcom',
    'Stripe Unified' => '/api/webhooks/stripe'
];

// Sample webhook payloads
$retellPayload = [
    'event' => 'call_ended',
    'call' => [
        'call_id' => 'test-' . uniqid(),
        'from_number' => '+491234567890',
        'to_number' => '+498901234567',
        'direction' => 'inbound',
        'duration' => 120,
        'start_timestamp' => time() * 1000,
        'end_timestamp' => (time() + 120) * 1000
    ]
];

$calcomPayload = [
    'triggerEvent' => 'BOOKING_CREATED',
    'payload' => [
        'uid' => 'test-' . uniqid(),
        'title' => 'Test Booking',
        'startTime' => date('Y-m-d H:i:s'),
        'endTime' => date('Y-m-d H:i:s', strtotime('+30 minutes'))
    ]
];

$stripePayload = [
    'id' => 'evt_test_' . uniqid(),
    'type' => 'payment_intent.succeeded',
    'data' => [
        'object' => [
            'amount' => 2000,
            'currency' => 'eur'
        ]
    ]
];

// Function to test endpoint
function testEndpoint($name, $url, $payload) {
    echo "Testing: $name\n";
    echo "URL: $url\n";
    
    try {
        // Check route existence
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'http://localhost' . $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Accept: application/json'
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_MAXREDIRS, 5);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            echo "❌ CURL Error: $error\n";
        } else {
            echo "Response Code: $httpCode\n";
            
            // Check if response indicates MCP usage
            $responseData = json_decode($response, true);
            
            // Look for signs of MCP/UnifiedWebhookController
            if (strpos($response, 'correlation_id') !== false) {
                echo "✅ Uses MCP (has correlation_id)\n";
            } elseif (strpos($response, 'webhook_event_id') !== false) {
                echo "✅ Uses WebhookProcessor (has webhook_event_id)\n";
            } elseif ($httpCode === 422 && strpos($response, 'signature') !== false) {
                echo "⚠️  Signature verification failed (expected in test)\n";
            } else {
                echo "❓ Cannot determine if using MCP\n";
                echo "Response: " . substr($response, 0, 200) . "...\n";
            }
        }
        
    } catch (Exception $e) {
        echo "❌ Exception: " . $e->getMessage() . "\n";
    }
    
    echo str_repeat('-', 60) . "\n\n";
}

// Test each endpoint
foreach ($testEndpoints as $name => $url) {
    // Determine payload based on endpoint
    if (strpos($url, 'retell') !== false) {
        $payload = $retellPayload;
    } elseif (strpos($url, 'calcom') !== false) {
        $payload = $calcomPayload;
    } elseif (strpos($url, 'stripe') !== false) {
        $payload = $stripePayload;
    } else {
        $payload = $retellPayload; // default
    }
    
    testEndpoint($name, $url, $payload);
}

// Check logs for MCP migration messages
echo "\n=== Checking Recent Logs for MCP Migration ===\n";
$logFile = __DIR__ . '/storage/logs/laravel.log';
if (file_exists($logFile)) {
    $logs = file_get_contents($logFile);
    $lines = explode("\n", $logs);
    $recentLogs = array_slice($lines, -50);
    
    $mcpMigrationLogs = array_filter($recentLogs, function($line) {
        return strpos($line, '[MCP Migration]') !== false;
    });
    
    if (count($mcpMigrationLogs) > 0) {
        echo "Found " . count($mcpMigrationLogs) . " MCP migration log entries:\n";
        foreach (array_slice($mcpMigrationLogs, -5) as $log) {
            echo "  - " . substr($log, 0, 150) . "...\n";
        }
    } else {
        echo "No MCP migration logs found in recent entries.\n";
    }
}

echo "\n=== Summary ===\n";
echo "All webhook endpoints should now route through MCP-based controllers.\n";
echo "Check the logs above to verify proper routing.\n";
echo "Look for correlation_id or webhook_event_id in responses.\n";