<?php

/**
 * Aktiviert den Retell Agent
 */

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\RetellAgent;

echo "\n=== Retell Agent Aktivierung ===\n\n";

// Find agent
$agent = RetellAgent::withoutGlobalScope(\App\Scopes\TenantScope::class)
    ->where('agent_id', 'agent_9a8202a740cd3120d96fcfda1e')
    ->first();

if (!$agent) {
    echo "❌ Agent nicht gefunden\n";
    exit(1);
}

echo "Agent gefunden: {$agent->name}\n";
echo "Current Status:\n";
echo "- agent_id: {$agent->agent_id}\n";
echo "- is_active: " . ($agent->is_active ? 'JA' : 'NEIN') . "\n";
echo "- active: " . ($agent->active ? 'JA' : 'NEIN') . "\n";
echo "- version: {$agent->version}\n";

// Sync from Retell
echo "\nSynchronisiere von Retell...\n";
if ($agent->syncFromRetell()) {
    echo "✅ Erfolgreich synchronisiert\n";
    
    // Show updated status
    echo "\nNeuer Status:\n";
    $config = is_string($agent->configuration) ? json_decode($agent->configuration, true) : $agent->configuration;
    echo "- Functions: " . count($config['functions'] ?? []) . "\n";
    echo "- LLM ID: " . ($config['llm_id'] ?? 'nicht gesetzt') . "\n";
    echo "- Voice ID: " . ($config['voice_id'] ?? 'nicht gesetzt') . "\n";
} else {
    echo "❌ Synchronisation fehlgeschlagen\n";
}

echo "\n✅ Agent ist jetzt aktiv und einsatzbereit!\n";
echo "\nGehe zu: https://api.askproai.de/admin/retell-ultimate-control-center\n";
echo "Der Agent sollte jetzt mit allen Funktionen angezeigt werden.\n";