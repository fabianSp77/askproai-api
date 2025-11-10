<?php

/**
 * Verify Current Flow Contents
 *
 * Shows actual content to prove fixes were saved
 */

$flowId = 'conversation_flow_a58405e3f67a';
$apiKey = 'key_6ff998ba48e842092e04a5455d19';

$ch = curl_init("https://api.retellai.com/get-conversation-flow/{$flowId}");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer {$apiKey}",
    "Content-Type: application/json"
]);
$response = curl_exec($ch);
curl_close($ch);

$flow = json_decode($response, true);

echo "📋 AKTUELLER FLOW-INHALT (VERIFIZIERUNG)\n";
echo str_repeat('=', 80) . "\n\n";

echo "Flow Version: {$flow['version']}\n";
echo "Flow ID: {$flow['conversation_flow_id']}\n\n";

// ============================================================================
// 1. Global Prompt - Variables Section
// ============================================================================

echo "1. GLOBAL PROMPT - Variable Declarations:\n";
echo str_repeat('-', 80) . "\n";

$lines = explode("\n", $flow['global_prompt']);
$inVariables = false;
$count = 0;

foreach ($lines as $line) {
    if (stripos($line, 'Du hast Zugriff auf Dynamic Variables') !== false) {
        $inVariables = true;
    }
    if ($inVariables) {
        echo $line . "\n";
        $count++;
        if ($count > 15 || (trim($line) === '' && $count > 5)) {
            break;
        }
    }
}
echo "\n";

// ============================================================================
// 2. Stornierung Node
// ============================================================================

echo "2. STORNIERUNG NODE:\n";
echo str_repeat('-', 80) . "\n";

foreach ($flow['nodes'] as $node) {
    if ($node['id'] === 'node_collect_cancel_info') {
        $instruction = is_array($node['instruction']) ? $node['instruction']['text'] : $node['instruction'];

        echo "First 400 characters of instruction:\n";
        echo substr($instruction, 0, 400) . "...\n\n";

        // Check for key phrases
        $keyPhrases = [
            'Bereits gesammelte Informationen',
            '{{cancel_datum}}',
            '{{cancel_uhrzeit}}',
            'ÜBERSPRINGE'
        ];

        echo "Key Phrases Check:\n";
        foreach ($keyPhrases as $phrase) {
            $found = stripos($instruction, $phrase) !== false;
            echo ($found ? "✅" : "❌") . " {$phrase}\n";
        }

        echo "\nTransition Condition:\n";
        if (isset($node['edges'][0])) {
            echo "   " . $node['edges'][0]['transition_condition']['prompt'] . "\n";
        }

        break;
    }
}
echo "\n";

// ============================================================================
// 3. Verschiebung Node
// ============================================================================

echo "3. VERSCHIEBUNG NODE:\n";
echo str_repeat('-', 80) . "\n";

foreach ($flow['nodes'] as $node) {
    if ($node['id'] === 'node_collect_reschedule_info') {
        $instruction = is_array($node['instruction']) ? $node['instruction']['text'] : $node['instruction'];

        echo "First 400 characters of instruction:\n";
        echo substr($instruction, 0, 400) . "...\n\n";

        // Check for key phrases
        $keyPhrases = [
            'Bereits gesammelte Informationen',
            '{{old_datum}}',
            '{{new_datum}}',
            'ÜBERSPRINGE'
        ];

        echo "Key Phrases Check:\n";
        foreach ($keyPhrases as $phrase) {
            $found = stripos($instruction, $phrase) !== false;
            echo ($found ? "✅" : "❌") . " {$phrase}\n";
        }

        echo "\nTransition Condition:\n";
        if (isset($node['edges'][0])) {
            echo "   " . $node['edges'][0]['transition_condition']['prompt'] . "\n";
        }

        break;
    }
}
echo "\n";

// ============================================================================
// 4. Parameter Mappings
// ============================================================================

echo "4. PARAMETER MAPPINGS (call_id):\n";
echo str_repeat('-', 80) . "\n";

$functionNodes = array_filter($flow['nodes'], fn($n) => $n['type'] === 'function');
foreach ($functionNodes as $node) {
    $mapping = $node['parameter_mapping'] ?? [];
    $callId = $mapping['call_id'] ?? 'NOT SET';
    echo "   {$node['name']}: {$callId}\n";
}
echo "\n";

// ============================================================================
// SUMMARY
// ============================================================================

echo str_repeat('=', 80) . "\n";
echo "VERIFIKATION:\n";
echo str_repeat('=', 80) . "\n\n";

// Count new variables in global prompt
$newVars = ['cancel_datum', 'cancel_uhrzeit', 'old_datum', 'old_uhrzeit', 'new_datum', 'new_uhrzeit'];
$foundCount = 0;
foreach ($newVars as $var) {
    if (stripos($flow['global_prompt'], "{{{$var}}}") !== false) {
        $foundCount++;
    }
}

echo "Global Prompt: {$foundCount}/6 neue Variables (" . ($foundCount === 6 ? "✅ OK" : "❌ FEHLT") . ")\n";

// Check stornierung
$stornierungOk = false;
foreach ($flow['nodes'] as $node) {
    if ($node['id'] === 'node_collect_cancel_info') {
        $instruction = is_array($node['instruction']) ? $node['instruction']['text'] : $node['instruction'];
        $stornierungOk = stripos($instruction, 'Bereits gesammelte') !== false;
        break;
    }
}
echo "Stornierung Node: " . ($stornierungOk ? "✅ State Management vorhanden" : "❌ FEHLT") . "\n";

// Check verschiebung
$verschiebungOk = false;
foreach ($flow['nodes'] as $node) {
    if ($node['id'] === 'node_collect_reschedule_info') {
        $instruction = is_array($node['instruction']) ? $node['instruction']['text'] : $node['instruction'];
        $verschiebungOk = stripos($instruction, 'Bereits gesammelte') !== false;
        break;
    }
}
echo "Verschiebung Node: " . ($verschiebungOk ? "✅ State Management vorhanden" : "❌ FEHLT") . "\n";

// Check parameter mappings
$allCallIdOk = true;
$functionNodes = array_filter($flow['nodes'], fn($n) => $n['type'] === 'function');
foreach ($functionNodes as $node) {
    $mapping = $node['parameter_mapping'] ?? [];
    $callId = $mapping['call_id'] ?? '';
    if ($callId !== '{{call.call_id}}') {
        $allCallIdOk = false;
        break;
    }
}
echo "Parameter Mappings: " . ($allCallIdOk ? "✅ Alle nutzen {{call.call_id}}" : "❌ FEHLT") . "\n\n";

if ($foundCount === 6 && $stornierungOk && $verschiebungOk && $allCallIdOk) {
    echo "🎉 BESTÄTIGT: ALLE FIXES SIND IM FLOW GESPEICHERT!\n\n";
    echo "ℹ️  HINWEIS ZUM TIMESTAMP:\n";
    echo "   Der Timestamp den Sie im Dashboard sehen (23:04) ist vom AGENT.\n";
    echo "   Der FLOW hat keinen last_modification_timestamp in der API Response.\n";
    echo "   Das ist normal - Retell zeigt Flow-Änderungen nicht immer mit Timestamp.\n\n";
    echo "   WICHTIG: Die INHALTE sind korrekt (siehe oben)!\n";
} else {
    echo "❌ FEHLER: Einige Fixes fehlen!\n";
}
