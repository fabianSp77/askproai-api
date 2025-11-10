#!/usr/bin/env php
<?php

/**
 * Upload V50 Prompt to Retell Conversation Flow
 */

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Http;

echo "\n";
echo "═══════════════════════════════════════════════════════════════\n";
echo " Upload V50 CRITICAL ENFORCEMENT to Retell\n";
echo "═══════════════════════════════════════════════════════════════\n";
echo "\n";

$retellApiKey = config('services.retellai.api_key');
$conversationFlowId = 'conversation_flow_a58405e3f67a';
$baseUrl = 'https://api.retellai.com';

// Read V50 prompt
$promptPath = __DIR__ . '/../GLOBAL_PROMPT_V50_CRITICAL_ENFORCEMENT_2025.md';
if (!file_exists($promptPath)) {
    echo "❌ ERROR: V50 prompt file not found at {$promptPath}\n";
    exit(1);
}

$v50Prompt = file_get_contents($promptPath);
echo "✅ V50 Prompt loaded (" . strlen($v50Prompt) . " characters)\n\n";

// Update conversation flow
echo "📤 Uploading V50 to Retell...\n";

$response = Http::withHeaders([
    'Authorization' => "Bearer {$retellApiKey}",
    'Content-Type' => 'application/json'
])->patch("{$baseUrl}/update-conversation-flow/{$conversationFlowId}", [
    'global_prompt' => $v50Prompt
]);

if (!$response->successful()) {
    echo "❌ ERROR: Failed to update conversation flow\n";
    echo "Status: " . $response->status() . "\n";
    echo "Body: " . $response->body() . "\n";
    exit(1);
}

echo "✅ V50 prompt uploaded successfully!\n\n";

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

// Check V50 markers
$hasV50Marker = strpos($uploadedPrompt, 'V50 (2025-11-05 CRITICAL ENFORCEMENT)') !== false;
$hasCriticalEnforcement = strpos($uploadedPrompt, '🚨 KRITISCHE REGEL: Tool-Call Enforcement') !== false;
$hasStopInstruction = strpos($uploadedPrompt, '🛑 STOP! Bevor du antwortest') !== false;
$hasFallback = strpos($uploadedPrompt, 'Was tun wenn Tool fehlschlägt') !== false;

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";
echo "Verification Results:\n";
echo "  Prompt Length: " . strlen($uploadedPrompt) . " characters\n";
echo "  V50 Marker: " . ($hasV50Marker ? "✅" : "❌") . "\n";
echo "  Critical Enforcement Section: " . ($hasCriticalEnforcement ? "✅" : "❌") . "\n";
echo "  STOP Instruction: " . ($hasStopInstruction ? "✅" : "❌") . "\n";
echo "  Tool Failure Fallback: " . ($hasFallback ? "✅" : "❌") . "\n";
echo "\n";

if ($hasV50Marker && $hasCriticalEnforcement && $hasStopInstruction && $hasFallback) {
    echo "═══════════════════════════════════════════════════════════════\n";
    echo " ✅ V50 CRITICAL ENFORCEMENT Upload Complete!\n";
    echo "═══════════════════════════════════════════════════════════════\n";
    echo "\n";
    echo "Flow ID: {$conversationFlowId}\n";
    echo "Changes:\n";
    echo "  - 🚨 Mandatory tool call enforcement\n";
    echo "  - 🛑 STOP instruction before responding\n";
    echo "  - 🚫 Explicit NO invented times rule\n";
    echo "  - 🔧 Tool failure fallback behavior\n";
    echo "\n";
    echo "Status: Ready for agent update\n";
    echo "\n";
} else {
    echo "❌ Verification failed - prompt missing expected sections\n";
    exit(1);
}
