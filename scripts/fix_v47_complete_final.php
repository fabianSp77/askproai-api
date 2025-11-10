#!/usr/bin/env php
<?php

/**
 * Fix V47 - Kompletter Fix ALLER Preise entfernen
 */

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Http;

echo "\n";
echo "═══════════════════════════════════════════════════════════════\n";
echo " V47 Complete Final Fix - Alle Preise entfernen\n";
echo "═══════════════════════════════════════════════════════════════\n";
echo "\n";

$retellApiKey = config('services.retellai.api_key');
$conversationFlowId = 'conversation_flow_a58405e3f67a';
$baseUrl = rtrim(config('services.retellai.base_url', 'https://api.retellai.com'), '/');

// Get current prompt
$response = Http::withHeaders([
    'Authorization' => "Bearer {$retellApiKey}",
])->get("{$baseUrl}/get-conversation-flow/{$conversationFlowId}");

$flow = $response->json();
$currentPrompt = $flow['global_prompt'] ?? '';

echo "📋 Current prompt length: " . strlen($currentPrompt) . " characters\n\n";

// Start with current prompt
$updatedPrompt = $currentPrompt;

// FIX: Remove ALL occurrences of prices in dialog examples
$replacements = [
    // Dialog example with concrete prices
    [
        'old' => 'Agent: "Gerne! Möchten Sie einen Herrenhaarschnitt (32€, 55 Min) oder
        Damenhaarschnitt (45€, 45 Min)?"',
        'new' => 'Agent: "Gerne! Möchten Sie einen Herrenhaarschnitt oder Damenhaarschnitt?"'
    ],
    // Alternative patterns
    [
        'old' => 'Herrenhaarschnitt (32€, 55 Min)',
        'new' => 'Herrenhaarschnitt'
    ],
    [
        'old' => 'Damenhaarschnitt (45€, 45 Min)',
        'new' => 'Damenhaarschnitt'
    ],
    [
        'old' => 'Herrenhaarschnitt (25€, 30 Min)',
        'new' => 'Herrenhaarschnitt'
    ],
    [
        'old' => 'Damenhaarschnitt (35€, 45 Min)',
        'new' => 'Damenhaarschnitt'
    ],
];

$changesApplied = 0;

foreach ($replacements as $replacement) {
    if (strpos($updatedPrompt, $replacement['old']) !== false) {
        $updatedPrompt = str_replace($replacement['old'], $replacement['new'], $updatedPrompt);
        $changesApplied++;
        echo "✅ Ersetzt: " . substr($replacement['old'], 0, 50) . "...\n";
    }
}

echo "\n";
echo "📊 Änderungen: {$changesApplied} replacements\n";
echo "📏 New length: " . strlen($updatedPrompt) . " characters\n\n";

// Update conversation flow
echo "🚀 Updating conversation flow...\n";

$response = Http::withHeaders([
    'Authorization' => "Bearer {$retellApiKey}",
    'Content-Type' => 'application/json'
])->patch("{$baseUrl}/update-conversation-flow/{$conversationFlowId}", [
    'global_prompt' => $updatedPrompt
]);

if (!$response->successful()) {
    echo "❌ ERROR: Failed to update\n";
    echo $response->body() . "\n";
    exit(1);
}

echo "✅ Updated!\n\n";

// Verify
echo "🔍 Verification:\n\n";

$verifyResponse = Http::withHeaders([
    'Authorization' => "Bearer {$retellApiKey}",
])->get("{$baseUrl}/get-conversation-flow/{$conversationFlowId}");

$verifyFlow = $verifyResponse->json();
$verifyPrompt = $verifyFlow['global_prompt'] ?? '';

$checks = [
    'Keine (32€, 55 Min)' => strpos($verifyPrompt, '(32€, 55 Min)') === false,
    'Keine (45€, 45 Min)' => strpos($verifyPrompt, '(45€, 45 Min)') === false,
    'Keine (25€, 30 Min)' => strpos($verifyPrompt, '(25€, 30 Min)') === false,
    'Keine (35€, 45 Min)' => strpos($verifyPrompt, '(35€, 45 Min)') === false,
    'Notice vorhanden' => strpos($verifyPrompt, 'Preise und Dauer NUR auf explizite Nachfrage') !== false,
    'Tool Enforcement vorhanden' => strpos($verifyPrompt, 'DU MUSST check_availability CALLEN') !== false,
    'Platzhalter [Zeit1]' => strpos($verifyPrompt, '[Zeit1]') !== false,
];

foreach ($checks as $name => $result) {
    echo ($result ? '✅' : '❌') . " {$name}\n";
}

$allPassed = count(array_filter($checks)) === count($checks);

echo "\n";
if ($allPassed) {
    echo "═══════════════════════════════════════════════════════════════\n";
    echo " ✅ SUCCESS - ALLE V47 FIXES VOLLSTÄNDIG ANGEWENDET!\n";
    echo "═══════════════════════════════════════════════════════════════\n";
    echo "\n";
    echo "📋 Final Prompt Length: " . strlen($verifyPrompt) . " characters\n";
    echo "\n";
} else {
    echo "❌ Some checks failed\n";
    exit(1);
}
