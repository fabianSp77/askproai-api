#!/usr/bin/env php
<?php

/**
 * Add get_current_context Tool to Conversation Flow
 */

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Http;

echo "\n";
echo "═══════════════════════════════════════════════════════════════\n";
echo " Add get_current_context Tool\n";
echo "═══════════════════════════════════════════════════════════════\n";
echo "\n";

$retellApiKey = config('services.retellai.api_key');
$conversationFlowId = 'conversation_flow_a58405e3f67a';
$baseUrl = rtrim(config('services.retellai.base_url', 'https://api.retellai.com'), '/');

// Get current flow
$response = Http::withHeaders([
    'Authorization' => "Bearer {$retellApiKey}",
])->get("{$baseUrl}/get-conversation-flow/{$conversationFlowId}");

$flow = $response->json();
$currentTools = $flow['tools'] ?? [];

echo "📋 Current tools: " . count($currentTools) . "\n\n";

// Check if get_current_context already exists
$hasCurrentContext = false;
foreach ($currentTools as $tool) {
    if (($tool['name'] ?? '') === 'get_current_context') {
        $hasCurrentContext = true;
        break;
    }
}

if ($hasCurrentContext) {
    echo "✅ get_current_context tool already exists\n\n";
} else {
    echo "➕ Adding get_current_context tool...\n\n";

    // Define the new tool
    $newTool = [
        'tool_id' => 'tool-get-current-context',
        'timeout_ms' => 5000,
        'type' => 'custom',
        'name' => 'get_current_context',
        'description' => 'Ruft aktuelles Datum, Uhrzeit und Kontext-Informationen ab. Nutze diese Funktion wenn du das aktuelle Datum oder die Uhrzeit benötigst.',
        'url' => 'https://api.askproai.de/api/webhooks/retell/current-context',
        'parameters' => [
            'type' => 'object',
            'properties' => [
                'call_id' => [
                    'type' => 'string',
                    'description' => 'Die Call ID des aktuellen Anrufs',
                ],
            ],
            'required' => ['call_id'],
        ],
    ];

    // Add to tools array
    $currentTools[] = $newTool;

    // Update conversation flow
    $updateResponse = Http::withHeaders([
        'Authorization' => "Bearer {$retellApiKey}",
        'Content-Type' => 'application/json'
    ])->patch("{$baseUrl}/update-conversation-flow/{$conversationFlowId}", [
        'tools' => $currentTools
    ]);

    if (!$updateResponse->successful()) {
        echo "❌ ERROR: Failed to add tool\n";
        echo "Status: " . $updateResponse->status() . "\n";
        echo "Body: " . $updateResponse->body() . "\n";
        exit(1);
    }

    echo "✅ Tool added successfully!\n\n";
}

// Verify
$verifyResponse = Http::withHeaders([
    'Authorization' => "Bearer {$retellApiKey}",
])->get("{$baseUrl}/get-conversation-flow/{$conversationFlowId}");

$verifyFlow = $verifyResponse->json();
$verifyTools = $verifyFlow['tools'] ?? [];

echo "🔍 Verification:\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

echo "Total tools: " . count($verifyTools) . "\n\n";

$foundTools = [];
foreach ($verifyTools as $tool) {
    $toolName = $tool['name'] ?? 'Unknown';
    $foundTools[] = $toolName;
    echo "✅ {$toolName}\n";
}

$hasGetCurrentContext = in_array('get_current_context', $foundTools);

echo "\n";
if ($hasGetCurrentContext) {
    echo "═══════════════════════════════════════════════════════════════\n";
    echo " ✅ get_current_context Tool Successfully Added!\n";
    echo "═══════════════════════════════════════════════════════════════\n";
    echo "\n";
    echo "Tool Details:\n";
    echo "  Name: get_current_context\n";
    echo "  URL: https://api.askproai.de/api/webhooks/retell/current-context\n";
    echo "  Purpose: Provides current date/time to agent\n";
    echo "  Parameters: call_id (required)\n";
    echo "\n";
    echo "Total Tools: " . count($verifyTools) . "\n";
    echo "\n";
} else {
    echo "❌ Tool not found after update\n";
    exit(1);
}
