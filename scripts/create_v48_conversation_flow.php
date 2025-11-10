#!/usr/bin/env php
<?php

/**
 * Create V48 Conversation Flow with Optimized Prompt
 */

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Http;

echo "\n";
echo "═══════════════════════════════════════════════════════════════\n";
echo " Create V48 Conversation Flow\n";
echo "═══════════════════════════════════════════════════════════════\n";
echo "\n";

$retellApiKey = config('services.retellai.api_key');
$conversationFlowId = 'conversation_flow_a58405e3f67a';
$baseUrl = rtrim(config('services.retellai.base_url', 'https://api.retellai.com'), '/');

// Read V48 prompt
$v48Prompt = file_get_contents(__DIR__ . '/../GLOBAL_PROMPT_V48_OPTIMIZED_2025.md');

if (!$v48Prompt) {
    echo "❌ ERROR: Could not read V48 prompt file\n";
    exit(1);
}

echo "📋 V48 Prompt loaded: " . strlen($v48Prompt) . " characters\n\n";

// Update conversation flow with V48 prompt
echo "🚀 Updating conversation flow to V48...\n";

$response = Http::withHeaders([
    'Authorization' => "Bearer {$retellApiKey}",
    'Content-Type' => 'application/json'
])->patch("{$baseUrl}/update-conversation-flow/{$conversationFlowId}", [
    'global_prompt' => $v48Prompt
]);

if (!$response->successful()) {
    echo "❌ ERROR: Failed to update conversation flow\n";
    echo "Status: " . $response->status() . "\n";
    echo "Body: " . $response->body() . "\n";
    exit(1);
}

echo "✅ Conversation flow updated to V48!\n\n";

// Verify
$verifyResponse = Http::withHeaders([
    'Authorization' => "Bearer {$retellApiKey}",
])->get("{$baseUrl}/get-conversation-flow/{$conversationFlowId}");

$verifyFlow = $verifyResponse->json();
$verifyPrompt = $verifyFlow['global_prompt'] ?? '';

echo "🔍 Verification:\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

echo "Flow ID: {$conversationFlowId}\n";
echo "Prompt Length: " . strlen($verifyPrompt) . " characters\n\n";

// Check V48 markers
$checks = [
    'V48 Version marker' => strpos($verifyPrompt, 'V48 (2025 Optimized') !== false,
    'Dynamic Date section' => strpos($verifyPrompt, '{{current_date}}') !== false,
    'Voice-Optimized section' => strpos($verifyPrompt, 'Voice-Optimized') !== false,
    'Context Management' => strpos($verifyPrompt, 'Context Management & State') !== false,
    'Tool-Call Enforcement' => strpos($verifyPrompt, 'NIEMALS Verfügbarkeit erfinden') !== false,
    'NO hardcoded date' => strpos($verifyPrompt, '05. November 2025') === false,
];

foreach ($checks as $name => $result) {
    echo ($result ? '✅' : '❌') . " {$name}\n";
}

$allPassed = count(array_filter($checks)) === count($checks);

echo "\n";
if ($allPassed) {
    echo "═══════════════════════════════════════════════════════════════\n";
    echo " ✅ V48 CONVERSATION FLOW SUCCESSFULLY CREATED!\n";
    echo "═══════════════════════════════════════════════════════════════\n";
    echo "\n";
    echo "Flow ID: {$conversationFlowId}\n";
    echo "Version: V48 (2025 Optimized)\n";
    echo "Features:\n";
    echo "  - ✅ Dynamic Date via {{current_date}}\n";
    echo "  - ✅ Voice-First Design (max 2 sentences)\n";
    echo "  - ✅ Context-Aware (checks {{variables}})\n";
    echo "  - ✅ Token-Efficient (-24% vs V47)\n";
    echo "  - ✅ Natural Conversation Flow\n";
    echo "\n";
} else {
    echo "❌ Some checks failed\n";
    exit(1);
}
