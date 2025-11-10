#!/usr/bin/env php
<?php

/**
 * Fix Conversation Flow V85 → V86 - Add Date Format Rules
 *
 * Root Cause: Agent extracts "10.11." instead of "10.11.2025"
 * → Backend receives incomplete date → wrong parsing → inconsistent results
 *
 * Solution: Add DATUMS-FORMAT section to global prompt
 * → Enforce VOLLSTÄNDIGES DATUM mit Jahr bei jeder Extraktion
 *
 * Date: 2025-11-08 23:50
 */

$apiKey = "key_6ff998ba48e842092e04a5455d19";
$baseUrl = "https://api.retellai.com";
$conversationFlowId = "conversation_flow_a58405e3f67a";

// Fetch current conversation flow
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "$baseUrl/get-conversation-flow/$conversationFlowId");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer $apiKey",
    "Content-Type: application/json"
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

if ($httpCode !== 200) {
    echo "❌ Failed to fetch conversation flow\n";
    echo "Response: $response\n";
    exit(1);
}

$flow = json_decode($response, true);
echo "✅ Fetched conversation flow version {$flow['version']}\n\n";

// Modify global_prompt - Add DATUMS-FORMAT section after ZEIT-FORMAT
$oldPrompt = $flow['global_prompt'];

// Find the position to insert the new section (after ZEIT-FORMAT section)
$insertAfter = "NIEMALS: \"halb vier\", \"viertel nach\", \"2025\", \"14.5\"";
$insertPosition = strpos($oldPrompt, $insertAfter);

if ($insertPosition === false) {
    echo "❌ Could not find insertion point in global prompt\n";
    exit(1);
}

// Move to end of that line
$insertPosition = strpos($oldPrompt, "\n", $insertPosition) + 1;

// New section to insert
$newSection = <<<'SECTION'

## DATUMS-FORMAT (KRITISCH)
Bei Datums-Extraktionen IMMER vollständiges Datum mit Jahr verwenden:

**PFLICHT-FORMAT:**
- "10.11.2025" (vollständig mit Jahr)
- "Montag, den 10. November 2025"
- "heute", "morgen", "Montag" (relative Angaben OK)

**VERBOTEN:**
- "10.11." (OHNE Jahr) ❌
- "10. November" (OHNE Jahr) ❌
- Nur Tag und Monat ❌

**WENN USER DATUM WIEDERHOLT:**
User: "Der Montag, was ist das fürn Datum?"
Agent: "Montag ist der 10. November 2025."

WICHTIG: Bei ZWEITER Erwähnung des gleichen Datums → GLEICHE VOLLSTÄNDIGE Form verwenden wie beim ersten Mal!

**Variable {{appointment_date}}:**
- IMMER mit Jahr: "10.11.2025" oder "Montag, 10.11.2025"
- NIEMALS nur "10.11." oder "10.11"

SECTION;

// Insert the new section
$newPrompt = substr_replace($oldPrompt, $newSection, $insertPosition, 0);

$flow['global_prompt'] = $newPrompt;

echo "📝 Modified global prompt:\n";
echo "   Added DATUMS-FORMAT section with year validation rules\n\n";

// Prepare payload for update
$payload = [
    'global_prompt' => $flow['global_prompt'],
    'nodes' => $flow['nodes'],
    'tools' => $flow['tools'],
    'model_choice' => $flow['model_choice'] ?? ['type' => 'cascading', 'model' => 'gpt-4o-mini'],
    'model_temperature' => $flow['model_temperature'] ?? 0.3,
    'start_node_id' => $flow['start_node_id'] ?? 'node_greeting',
    'start_speaker' => $flow['start_speaker'] ?? 'agent',
    'begin_after_user_silence_ms' => $flow['begin_after_user_silence_ms'] ?? 800,
];

// Update via API
curl_setopt($ch, CURLOPT_URL, "$baseUrl/update-conversation-flow/$conversationFlowId");
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PATCH");
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer $apiKey",
    "Content-Type: application/json"
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode !== 200) {
    echo "❌ Failed to update conversation flow\n";
    echo "HTTP Code: $httpCode\n";
    echo "Response: $response\n";
    exit(1);
}

$result = json_decode($response, true);
echo "✅ Successfully updated conversation flow!\n";
echo "   Old Version: {$flow['version']}\n";
echo "   New Version: {$result['version']}\n";
echo "   Conversation Flow ID: {$result['conversation_flow_id']}\n";
echo "\n";
echo "🎯 CHANGES APPLIED:\n";
echo "   1. Added DATUMS-FORMAT section to global prompt\n";
echo "   2. Enforced year requirement for date extraction\n";
echo "   3. Added consistency rules for repeated date mentions\n";
echo "\n";
echo "📌 NEXT STEPS:\n";
echo "   1. Phone number already uses latest agent version (84)\n";
echo "   2. New version {$result['version']} will auto-apply on next call\n";
echo "   3. Backend validation will reject incomplete dates\n";
echo "   4. Test with new call to verify date consistency\n";

echo "\n✨ Fix complete! Version 85 → {$result['version']}\n";
