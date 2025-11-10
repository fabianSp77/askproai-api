#!/usr/bin/env php
<?php

/**
 * Fix V47 Dialog Example - Remove concrete time examples
 */

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Http;
use Carbon\Carbon;

echo "\n";
echo "═══════════════════════════════════════════════════════════════\n";
echo " Fix V47 Dialog Example\n";
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

// Fix the dialog example
$oldDialogExample = <<<'EOD'
Agent: "Ja! Für Damenhaarschnitt haben wir heute noch um 14:00, 16:30 und 18:00 Uhr frei.
        Welche Zeit würde Ihnen am besten passen?"
User: "16:30 passt"
Agent: [bucht 16:30]
Agent: "Perfekt! Ihr Termin für Damenhaarschnitt heute um 16:30 ist gebucht."
EOD;

$newDialogExample = <<<'EOD'
Agent: "Ja! Für Damenhaarschnitt haben wir heute noch um [Zeit1], [Zeit2] und [Zeit3] Uhr frei.
        Welche Zeit würde Ihnen am besten passen?"
User: "[Zeit2] passt"
Agent: [bucht gewählte Zeit]
Agent: "Perfekt! Ihr Termin für Damenhaarschnitt heute um [Zeit2] ist gebucht."
EOD;

if (strpos($currentPrompt, $oldDialogExample) !== false) {
    $updatedPrompt = str_replace($oldDialogExample, $newDialogExample, $currentPrompt);
    echo "✅ Dialog-Beispiel aktualisiert (Zeiten → Platzhalter)\n\n";
} else {
    echo "⚠️  Dialog-Beispiel nicht gefunden - versuche alternative Suche\n\n";

    // Try line by line replacement
    $updatedPrompt = str_replace(
        'Agent: "Ja! Für Damenhaarschnitt haben wir heute noch um 14:00, 16:30 und 18:00 Uhr frei.',
        'Agent: "Ja! Für Damenhaarschnitt haben wir heute noch um [Zeit1], [Zeit2] und [Zeit3] Uhr frei.',
        $currentPrompt
    );

    $updatedPrompt = str_replace(
        'User: "16:30 passt"',
        'User: "[Zeit2] passt"',
        $updatedPrompt
    );

    $updatedPrompt = str_replace(
        'Agent: [bucht 16:30]',
        'Agent: [bucht gewählte Zeit]',
        $updatedPrompt
    );

    $updatedPrompt = str_replace(
        'Agent: "Perfekt! Ihr Termin für Damenhaarschnitt heute um 16:30 ist gebucht."',
        'Agent: "Perfekt! Ihr Termin für Damenhaarschnitt heute um [Zeit2] ist gebucht."',
        $updatedPrompt
    );

    echo "✅ Dialog-Beispiel via alternative Suche aktualisiert\n\n";
}

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
    exit(1);
}

echo "✅ Updated!\n\n";

// Verify
$verifyResponse = Http::withHeaders([
    'Authorization' => "Bearer {$retellApiKey}",
])->get("{$baseUrl}/get-conversation-flow/{$conversationFlowId}");

$verifyFlow = $verifyResponse->json();
$verifyPrompt = $verifyFlow['global_prompt'] ?? '';

echo "🔍 Final Verification:\n\n";

$checks = [
    'Keine 14:00 in Dialogen' => substr_count($verifyPrompt, '14:00') <= 1, // 1 is ok (in FALSCHES Beispiel)
    'Keine 16:30 in Dialogen' => substr_count($verifyPrompt, '16:30') <= 1,
    'Keine 18:00 in Dialogen' => substr_count($verifyPrompt, '18:00') <= 1,
    'Platzhalter [Zeit1] vorhanden' => strpos($verifyPrompt, '[Zeit1]') !== false,
    'Platzhalter [Zeit2] vorhanden' => strpos($verifyPrompt, '[Zeit2]') !== false,
];

foreach ($checks as $name => $result) {
    echo ($result ? '✅' : '❌') . " {$name}\n";
}

$allPassed = count(array_filter($checks)) === count($checks);

echo "\n";
if ($allPassed) {
    echo "═══════════════════════════════════════════════════════════════\n";
    echo " ✅ SUCCESS - Alle Dialog-Beispiele bereinigt!\n";
    echo "═══════════════════════════════════════════════════════════════\n";
    echo "\n";
    echo "📋 Prompt Length: " . strlen($verifyPrompt) . " characters\n";
    echo "\n";
    echo "🎯 Änderungen:\n";
    echo "   - Dialog-Beispiel: Konkrete Zeiten → Platzhalter\n";
    echo "   - Falsches Beispiel mit 14:00, 16:30, 18:00 bleibt (zeigt was NICHT tun)\n";
    echo "\n";
} else {
    echo "❌ Some checks failed\n";
    exit(1);
}
