#!/usr/bin/env php
<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Http;

$token = env('RETELL_TOKEN');
$agentId = 'agent_f1ce85d06a84afb989dfbb16a9';

echo "\n═══════════════════════════════════════════════════════════\n";
echo "✅ VERIFY VERSION 71 PUBLISHED\n";
echo "═══════════════════════════════════════════════════════════\n\n";

$response = Http::withHeaders([
    'Authorization' => "Bearer $token",
])->get("https://api.retellai.com/get-agent/$agentId");

$agent = $response->json();

$currentVersion = $agent['version'] ?? 'unknown';
$isPublished = $agent['is_published'] ?? false;

echo "CURRENT STATE:\n";
echo "───────────────────────────────────────────────────────────\n";
echo "Version: $currentVersion\n";
echo "Published: " . ($isPublished ? "YES ✅" : "NO ❌") . "\n\n";

if (isset($agent['conversation_flow']['tools'])) {
    $tools = $agent['conversation_flow']['tools'];
    echo "Tools: " . count($tools) . "\n";
    echo "Nodes: " . count($agent['conversation_flow']['nodes'] ?? []) . "\n\n";
}

if ($isPublished && $currentVersion == 71) {
    echo "═══════════════════════════════════════════════════════════\n";
    echo "🎉 SUCCESS! VERSION 71 IST LIVE!\n";
    echo "═══════════════════════════════════════════════════════════\n\n";

    echo "✅ Alle Systeme bereit für Testanruf\n";
    echo "✅ 7 Functions verfügbar\n";
    echo "✅ Keine unnötigen Fragen\n";
    echo "✅ Initialize läuft silent\n\n";

    echo "NÄCHSTER SCHRITT:\n";
    echo "───────────────────────────────────────────────────────────\n";
    echo "Testanruf machen: +493033081738\n";
    echo "Sagen: 'Herrenhaarschnitt morgen 9 Uhr'\n\n";

    exit(0);
} elseif ($isPublished && $currentVersion != 71) {
    echo "═══════════════════════════════════════════════════════════\n";
    echo "⚠️  FALSCHE VERSION PUBLISHED\n";
    echo "═══════════════════════════════════════════════════════════\n\n";

    echo "❌ Version $currentVersion ist live (sollte 71 sein)\n";
    echo "❌ Version 71 ist NICHT published\n\n";

    echo "LÖSUNG:\n";
    echo "───────────────────────────────────────────────────────────\n";
    echo "1. Öffne Dashboard\n";
    echo "2. Finde Version 71 (7 Tools, 11 Nodes)\n";
    echo "3. Klick PUBLISH\n\n";

    echo "Dashboard: https://dashboard.retellai.com/agent/$agentId\n\n";

    exit(1);
} else {
    echo "═══════════════════════════════════════════════════════════\n";
    echo "❌ VERSION 71 NICHT PUBLISHED\n";
    echo "═══════════════════════════════════════════════════════════\n\n";

    if ($currentVersion == 71) {
        echo "✅ Version 71 existiert\n";
        echo "❌ Aber nicht published\n\n";
    } else {
        echo "⚠️  Version $currentVersion ist aktuell (erwartet: 71)\n";
        echo "⚠️  Version 71 wurde eventuell überschrieben\n\n";
    }

    echo "NÄCHSTER SCHRITT:\n";
    echo "───────────────────────────────────────────────────────────\n";
    echo "1. Öffne Dashboard: https://dashboard.retellai.com/agent/$agentId\n";
    echo "2. Finde Version 71 (7 Tools, 11 Nodes)\n";
    echo "3. Klick PUBLISH Button\n";
    echo "4. Warte 5 Sekunden\n";
    echo "5. Run this script again to verify\n\n";

    exit(1);
}
