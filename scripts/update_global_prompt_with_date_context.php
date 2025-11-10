#!/usr/bin/env php
<?php

/**
 * Update Global Prompt with Date Context
 *
 * ISSUE: Year Bug - Agent using 2023 instead of 2025
 * ROOT CAUSE: No date context in global prompt
 * FIX: Add explicit date context at top of global prompt
 *
 * CREATED: 2025-11-05
 * PRIORITY: P0-CRITICAL
 */

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Http;
use Carbon\Carbon;

echo "\n";
echo "═══════════════════════════════════════════════════════════════\n";
echo " Update Global Prompt with Date Context\n";
echo " Flow ID: conversation_flow_a58405e3f67a\n";
echo " Date: " . Carbon::now('Europe/Berlin')->format('Y-m-d H:i:s') . "\n";
echo "═══════════════════════════════════════════════════════════════\n";
echo "\n";

// Configuration
$retellApiKey = config('services.retellai.api_key');
$conversationFlowId = 'conversation_flow_a58405e3f67a';
$baseUrl = rtrim(config('services.retellai.base_url', 'https://api.retellai.com'), '/');

if (!$retellApiKey) {
    echo "❌ ERROR: Retell API key not configured\n";
    exit(1);
}

// Get current Berlin time
$now = Carbon::now('Europe/Berlin');
$dateGerman = $now->locale('de')->isoFormat('dddd, DD. MMMM YYYY');

echo "📅 Current Date Context:\n";
echo "   - Full Date: {$dateGerman}\n";
echo "   - ISO Date: {$now->format('Y-m-d')}\n";
echo "   - Year: {$now->format('Y')}\n";
echo "\n";

// STEP 1: Fetch current conversation flow
echo "🔍 Step 1: Fetching current conversation flow...\n";

$response = Http::withHeaders([
    'Authorization' => "Bearer {$retellApiKey}",
    'Content-Type' => 'application/json'
])->get("{$baseUrl}/get-conversation-flow/{$conversationFlowId}");

if (!$response->successful()) {
    echo "❌ ERROR: Failed to fetch conversation flow (HTTP {$response->status()})\n";
    echo "Response: {$response->body()}\n";
    exit(1);
}

$conversationFlow = $response->json();
$currentPrompt = $conversationFlow['global_prompt'] ?? '';

echo "✅ Conversation flow fetched\n";
echo "📋 Current Version: " . ($conversationFlow['version'] ?? 'unknown') . "\n";
echo "📋 Current Prompt Length: " . strlen($currentPrompt) . " characters\n";
echo "\n";

// STEP 2: Check if date context already exists
echo "🔍 Step 2: Checking for existing date context...\n";

$hasDateContext = strpos($currentPrompt, '⚠️ KRITISCH: Aktuelles Datum') !== false;

if ($hasDateContext) {
    echo "⚠️  Date context already exists in prompt\n";
    echo "🔄 Will replace with updated date context\n";

    // Remove old date context section
    $pattern = '/## ⚠️ KRITISCH: Aktuelles Datum.*?(?=##|$)/s';
    $currentPrompt = preg_replace($pattern, '', $currentPrompt);
    echo "✅ Removed old date context\n";
} else {
    echo "📝 No existing date context found - will add new section\n";
}
echo "\n";

// STEP 3: Prepare date context section
echo "📝 Step 3: Preparing date context section...\n";

$dateContextSection = <<<EOD

## ⚠️ KRITISCH: Aktuelles Datum ({$now->format('Y-m-d')})

**HEUTE IST: {$dateGerman}**

**WICHTIG FÜR BUCHUNGEN:**
- Aktuelles Jahr: **{$now->format('Y')}** (NICHT 2023 oder 2024!)
- Heute: {$now->format('d.m.Y')} ({$now->locale('de')->dayName})
- Morgen: {$now->copy()->addDay()->format('d.m.Y')} ({$now->copy()->addDay()->locale('de')->dayName})
- Übermorgen: {$now->copy()->addDays(2)->format('d.m.Y')} ({$now->copy()->addDays(2)->locale('de')->dayName})

**REGELN FÜR DATUMSVERARBEITUNG:**
1. ✅ IMMER Jahr **{$now->format('Y')}** verwenden für neue Termine
2. ✅ Relative Zeitangaben ("Freitag", "nächste Woche") auf Basis von HEUTE ({$now->format('d.m.Y')})
3. ✅ Bei unklaren Datumsangaben: Jahr {$now->format('Y')} annehmen
4. ❌ NIEMALS Jahr 2023 oder 2024 verwenden!
5. ❌ NIEMALS Termine in der Vergangenheit buchen

**BEISPIELE:**
- Kunde sagt: "Freitag um 17 Uhr"
  - ✅ RICHTIG: "08.11.{$now->format('Y')} 17:00" (nächster Freitag)
  - ❌ FALSCH: "08.11.2023 17:00"

- Kunde sagt: "10. November um 17 Uhr"
  - ✅ RICHTIG: "10.11.{$now->format('Y')} 17:00"
  - ❌ FALSCH: "10.11.2023 17:00"

- Kunde sagt: "Nächste Woche Montag"
  - ✅ RICHTIG: Berechne nächsten Montag ab HEUTE ({$now->format('d.m.Y')}) → Jahr {$now->format('Y')}
  - ❌ FALSCH: Irgendein Datum in 2023

EOD;

// STEP 4: Insert date context after "Deine Rolle" section
echo "🔧 Step 4: Inserting date context into prompt...\n";

// Find the position after "Deine Rolle" section (after first ## section)
$lines = explode("\n", $currentPrompt);
$insertPosition = 0;

for ($i = 0; $i < count($lines); $i++) {
    // Find the line with "Sprich freundlich und natürlich auf Deutsch."
    if (strpos($lines[$i], 'Sprich freundlich und natürlich auf Deutsch.') !== false) {
        $insertPosition = $i + 1;
        break;
    }
}

if ($insertPosition === 0) {
    echo "❌ ERROR: Could not find insertion point in prompt\n";
    exit(1);
}

// Insert date context after "Deine Rolle" section
array_splice($lines, $insertPosition, 0, explode("\n", $dateContextSection));
$updatedPrompt = implode("\n", $lines);

echo "✅ Date context inserted at line {$insertPosition}\n";
echo "📋 New Prompt Length: " . strlen($updatedPrompt) . " characters\n";
echo "📊 Difference: " . (strlen($updatedPrompt) - strlen($currentPrompt)) . " characters added\n";
echo "\n";

// STEP 5: Update conversation flow
echo "🚀 Step 5: Updating conversation flow via API...\n";

$updatePayload = [
    'global_prompt' => $updatedPrompt
];

$response = Http::withHeaders([
    'Authorization' => "Bearer {$retellApiKey}",
    'Content-Type' => 'application/json'
])->patch("{$baseUrl}/update-conversation-flow/{$conversationFlowId}", $updatePayload);

if (!$response->successful()) {
    echo "❌ ERROR: Failed to update conversation flow (HTTP {$response->status()})\n";
    echo "Response: {$response->body()}\n";
    exit(1);
}

$result = $response->json();
echo "✅ SUCCESS! Conversation flow updated\n";
echo "📋 New Version: " . ($result['version'] ?? 'unknown') . "\n";
echo "\n";

// STEP 6: Verify date context was added
echo "🔍 Step 6: Verifying date context was added...\n";

$verifyResponse = Http::withHeaders([
    'Authorization' => "Bearer {$retellApiKey}",
    'Content-Type' => 'application/json'
])->get("{$baseUrl}/get-conversation-flow/{$conversationFlowId}");

$verifyFlow = $verifyResponse->json();
$verifyPrompt = $verifyFlow['global_prompt'] ?? '';

$hasDateContext = strpos($verifyPrompt, '⚠️ KRITISCH: Aktuelles Datum') !== false;
$hasYear2025 = strpos($verifyPrompt, 'Aktuelles Jahr: **' . $now->format('Y')) !== false;

echo "   - Date context section: " . ($hasDateContext ? "✅ Found" : "❌ Missing") . "\n";
echo "   - Year {$now->format('Y')} reference: " . ($hasYear2025 ? "✅ Found" : "❌ Missing") . "\n";
echo "\n";

if ($hasDateContext && $hasYear2025) {
    echo "═══════════════════════════════════════════════════════════════\n";
    echo " ✅ SUCCESS - Year Bug Fixed\n";
    echo "═══════════════════════════════════════════════════════════════\n";
    echo "\n";
    echo "🎯 Date Context Added to Global Prompt:\n";
    echo "   ✅ Current date: {$dateGerman}\n";
    echo "   ✅ Year reference: {$now->format('Y')}\n";
    echo "   ✅ Rules for date processing\n";
    echo "   ✅ Examples with correct year\n";
    echo "\n";
    echo "🎯 Agent Behavior Changed:\n";
    echo "   - Will now use year {$now->format('Y')} for all bookings\n";
    echo "   - Has explicit examples showing correct date format\n";
    echo "   - Has warnings against using 2023 or 2024\n";
    echo "\n";
    echo "🎯 Next Steps:\n";
    echo "1. Test with new call to verify year is now {$now->format('Y')}\n";
    echo "2. Investigate database save failure (P0-2)\n";
    echo "3. Monitor for any other date-related issues\n";
    echo "\n";
    echo "📖 Expected Behavior:\n";
    echo "   User: \"Termin am Freitag um 17 Uhr\"\n";
    echo "   Agent: \"08.11.{$now->format('Y')} 17:00\" ✅\n";
    echo "   NOT: \"08.11.2023 17:00\" ❌\n";
    echo "\n";
} else {
    echo "❌ Verification failed - date context may not have been added correctly\n";
    exit(1);
}
