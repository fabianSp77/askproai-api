#!/usr/bin/env php
<?php

/**
 * Upload V49 Prompt to Retell Conversation Flow
 */

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Http;

echo "\n";
echo "═══════════════════════════════════════════════════════════════\n";
echo " Upload V49 Prompt to Retell\n";
echo "═══════════════════════════════════════════════════════════════\n";
echo "\n";

$retellApiKey = config('services.retellai.api_key');
$conversationFlowId = 'conversation_flow_a58405e3f67a';
$baseUrl = rtrim(config('services.retellai.base_url', 'https://api.retellai.com'), '/');

// Read V49 prompt
$promptPath = __DIR__ . '/../GLOBAL_PROMPT_V49_OPTIMIZED_2025.md';
if (!file_exists($promptPath)) {
    echo "❌ ERROR: V49 prompt file not found at {$promptPath}\n";
    exit(1);
}

$v49Prompt = file_get_contents($promptPath);
echo "✅ V49 Prompt loaded (" . strlen($v49Prompt) . " characters)\n\n";

// Update conversation flow
echo "📤 Uploading V49 to Retell...\n";

$response = Http::withHeaders([
    'Authorization' => "Bearer {$retellApiKey}",
    'Content-Type' => 'application/json'
])->patch("{$baseUrl}/update-conversation-flow/{$conversationFlowId}", [
    'global_prompt' => $v49Prompt
]);

if (!$response->successful()) {
    echo "❌ ERROR: Failed to update conversation flow\n";
    echo "Status: " . $response->status() . "\n";
    echo "Body: " . $response->body() . "\n";
    exit(1);
}

echo "✅ V49 prompt uploaded successfully!\n\n";

// Verify
echo "🔍 Verifying upload...\n";

$verifyResponse = Http::withHeaders([
    'Authorization' => "Bearer {$retellApiKey}",
])->get("{$baseUrl}/get-conversation-flow/{$conversationFlowId}");

if (!$verifyResponse->successful()) {
    echo "❌ ERROR: Failed to verify\n";
    exit(1);
}

$flow = $verifyResponse->json();
$uploadedPrompt = $flow['global_prompt'] ?? '';

// Check V49 marker
$hasV49Marker = strpos($uploadedPrompt, 'V49 (2025-11-05 HOTFIX)') !== false;
$hasProactive = strpos($uploadedPrompt, 'ZEITFENSTER: Proaktive Vorschläge') !== false;
$hasAntiRepetition = strpos($uploadedPrompt, 'Anti-Repetition & Interruption Handling') !== false;

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";
echo "Verification Results:\n";
echo "  Prompt Length: " . strlen($uploadedPrompt) . " characters\n";
echo "  V49 Marker: " . ($hasV49Marker ? "✅" : "❌") . "\n";
echo "  Proactive Suggestions: " . ($hasProactive ? "✅" : "❌") . "\n";
echo "  Anti-Repetition Rules: " . ($hasAntiRepetition ? "✅" : "❌") . "\n";
echo "\n";

if ($hasV49Marker && $hasProactive && $hasAntiRepetition) {
    echo "═══════════════════════════════════════════════════════════════\n";
    echo " ✅ V49 Upload Complete!\n";
    echo "═══════════════════════════════════════════════════════════════\n";
    echo "\n";
    echo "Flow ID: {$conversationFlowId}\n";
    echo "Status: Ready for agent update\n";
    echo "\n";
} else {
    echo "❌ Verification failed - prompt missing expected sections\n";
    exit(1);
}
