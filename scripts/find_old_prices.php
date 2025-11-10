#!/usr/bin/env php
<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Http;

$retellApiKey = config('services.retellai.api_key');
$conversationFlowId = 'conversation_flow_a58405e3f67a';
$baseUrl = rtrim(config('services.retellai.base_url', 'https://api.retellai.com'), '/');

$response = Http::withHeaders([
    'Authorization' => "Bearer {$retellApiKey}",
])->get("{$baseUrl}/get-conversation-flow/{$conversationFlowId}");

$flow = $response->json();
$prompt = $flow['global_prompt'] ?? '';

echo "\n═══════════════════════════════════════════════════════════════\n";
echo " Suche nach alten Preisen im Prompt\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

$lines = explode("\n", $prompt);
$lineNum = 0;

$foundOccurrences = [];

foreach ($lines as $line) {
    $lineNum++;
    if (strpos($line, 'Herrenhaarschnitt (32€') !== false ||
        strpos($line, 'Herrenhaarschnitt (25€') !== false ||
        strpos($line, 'Damenhaarschnitt (45€') !== false ||
        strpos($line, 'Damenhaarschnitt (35€') !== false) {
        $foundOccurrences[] = [
            'line' => $lineNum,
            'text' => trim($line)
        ];
    }
}

if (empty($foundOccurrences)) {
    echo "✅ Keine Preise in Service-Beispielen gefunden!\n";
} else {
    echo "⚠️ Gefunden in folgenden Zeilen:\n\n";
    foreach ($foundOccurrences as $occ) {
        echo "Zeile {$occ['line']}: {$occ['text']}\n\n";
    }
}

echo "\n";
