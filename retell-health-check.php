<?php

/**
 * Retell.ai Health Check & Auto-Fix Script.
 *
 * Dieses Skript prüft die Retell-Integration und behebt automatisch häufige Probleme
 * Verwendung: php retell-health-check.php
 */

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Company;
use App\Models\PhoneNumber;
use App\Models\RetellAgent;
use App\Services\RetellV2Service;
use Illuminate\Support\Facades\DB;

echo "\n=== Retell.ai Health Check ===\n";
echo 'Datum: ' . date('Y-m-d H:i:s') . "\n\n";

$errors = [];
$warnings = [];
$fixed = [];

// 1. Check Environment Variables
echo "1. Prüfe Environment Variables...\n";
$requiredEnvVars = [
    'RETELL_TOKEN' => config('services.retell.api_key'),
    'RETELL_WEBHOOK_SECRET' => config('services.retell.webhook_secret'),
    'RETELL_BASE' => config('services.retell.base_url', 'https://api.retellai.com'),
];

foreach ($requiredEnvVars as $key => $value) {
    if (empty($value)) {
        $errors[] = "❌ $key ist nicht gesetzt";
    } else {
        echo "   ✅ $key: " . (strpos($key, 'SECRET') !== false ? '***' : substr($value, 0, 20) . '...') . "\n";
    }
}

// 2. Check Database Connection
echo "\n2. Prüfe Datenbankverbindung...\n";

try {
    DB::connection()->getPdo();
    echo "   ✅ Datenbankverbindung erfolgreich\n";
} catch (Exception $e) {
    $errors[] = '❌ Datenbankverbindung fehlgeschlagen: ' . $e->getMessage();
}

// 3. Check Phone Numbers
echo "\n3. Prüfe Telefonnummern...\n";
$phoneNumbers = PhoneNumber::all();
if ($phoneNumbers->isEmpty()) {
    $errors[] = '❌ Keine Telefonnummern in der Datenbank';
} else {
    foreach ($phoneNumbers as $phone) {
        echo "   ✅ {$phone->number} → Company: {$phone->company_id}, Branch: {$phone->branch_id}\n";

        // Check Agent Assignment
        if (empty($phone->retell_agent_id)) {
            $warnings[] = "⚠️ Telefonnummer {$phone->number} hat keinen zugewiesenen Agent";
        }
    }
}

// 4. Check Retell API Connection
echo "\n4. Teste Retell API-Verbindung...\n";

try {
    $company = Company::first();
    if (! $company) {
        $errors[] = '❌ Keine Company in der Datenbank';
    } else {
        $apiKey = $company->retell_api_key ?: config('services.retell.api_key');

        if (empty($apiKey)) {
            $errors[] = '❌ Kein Retell API Key gefunden';

            // Auto-Fix: Set API key from config
            if (config('services.retell.api_key')) {
                $company->retell_api_key = encrypt(config('services.retell.api_key'));
                $company->save();
                $fixed[] = '🔧 API Key aus Config für Company gesetzt';
            }
        } else {
            $retellService = new RetellV2Service(decrypt($apiKey));

            // Test API with list agents
            $agents = $retellService->listAgents();
            if (isset($agents['agents'])) {
                echo '   ✅ API-Verbindung erfolgreich - ' . count($agents['agents']) . " Agents gefunden\n";
            } else {
                $warnings[] = '⚠️ API-Verbindung funktioniert, aber keine Agents gefunden';
            }
        }
    }
} catch (Exception $e) {
    $errors[] = '❌ Retell API Fehler: ' . $e->getMessage();
}

// 5. Check Retell Agents
echo "\n5. Prüfe Retell Agents...\n";
$expectedAgentId = 'agent_9a8202a740cd3120d96fcfda1e';
$agent = RetellAgent::where('retell_agent_id', $expectedAgentId)->first();

if (! $agent) {
    $warnings[] = "⚠️ Erwarteter Agent $expectedAgentId nicht in lokaler DB";

    // Auto-Fix: Try to sync agent
    echo "   🔧 Versuche Agent zu synchronisieren...\n";

    try {
        if (isset($retellService)) {
            $remoteAgent = $retellService->getAgent($expectedAgentId);
            if ($remoteAgent) {
                // Import agent
                $agent = RetellAgent::updateOrCreate(
                    ['retell_agent_id' => $expectedAgentId],
                    [
                        'company_id' => 1,
                        'name' => $remoteAgent['agent_name'] ?? 'Imported Agent',
                        'configuration' => $remoteAgent,
                        'llm_id' => $remoteAgent['llm_id'] ?? null,
                        'voice_id' => $remoteAgent['voice_id'] ?? null,
                        'is_active' => true,
                        'last_synced_at' => now(),
                    ]
                );
                $fixed[] = "🔧 Agent $expectedAgentId erfolgreich synchronisiert";
            }
        }
    } catch (Exception $e) {
        $warnings[] = '⚠️ Agent-Sync fehlgeschlagen: ' . $e->getMessage();
    }
} else {
    echo "   ✅ Agent gefunden: {$agent->name}\n";

    // Check LLM configuration
    $config = is_string($agent->configuration) ? json_decode($agent->configuration, true) : $agent->configuration;
    if (! isset($config['llm_id']) || ! isset($config['functions']) || count($config['functions'] ?? []) < 9) {
        $warnings[] = '⚠️ Agent-Konfiguration unvollständig (Functions: ' . count($config['functions'] ?? []) . '/9)';

        // Auto-Fix: Sync from Retell
        if ($agent->syncFromRetell()) {
            $fixed[] = '🔧 Agent-Konfiguration aktualisiert';
        }
    }
}

// 6. Check Webhook Configuration
echo "\n6. Prüfe Webhook-Konfiguration...\n";
$webhookUrl = config('app.url') . '/api/retell/webhook';
echo "   📍 Webhook URL: $webhookUrl\n";
echo '   🔑 Webhook Secret: ' . (config('services.retell.webhook_secret') ? '***' : 'NICHT GESETZT') . "\n";

// 7. Check Recent Calls
echo "\n7. Prüfe aktuelle Anrufe...\n";
$recentCalls = DB::table('calls')
    ->where('created_at', '>', now()->subHours(24))
    ->count();
echo "   📞 Anrufe in den letzten 24 Stunden: $recentCalls\n";

if ($recentCalls == 0) {
    $warnings[] = '⚠️ Keine Anrufe in den letzten 24 Stunden';
}

// 8. Check Horizon Status
echo "\n8. Prüfe Queue Worker (Horizon)...\n";
$horizonStatus = shell_exec('php artisan horizon:status 2>&1');
if (strpos($horizonStatus, 'running') !== false) {
    echo "   ✅ Horizon läuft\n";
} else {
    $errors[] = '❌ Horizon läuft nicht - starte mit: php artisan horizon';
}

// Summary
echo "\n=== ZUSAMMENFASSUNG ===\n";

if (! empty($errors)) {
    echo "\n❌ FEHLER (" . count($errors) . "):\n";
    foreach ($errors as $error) {
        echo "   $error\n";
    }
}

if (! empty($warnings)) {
    echo "\n⚠️ WARNUNGEN (" . count($warnings) . "):\n";
    foreach ($warnings as $warning) {
        echo "   $warning\n";
    }
}

if (! empty($fixed)) {
    echo "\n🔧 AUTOMATISCH BEHOBEN (" . count($fixed) . "):\n";
    foreach ($fixed as $fix) {
        echo "   $fix\n";
    }
}

if (empty($errors) && empty($warnings)) {
    echo "\n✅ SYSTEM IST VOLLSTÄNDIG FUNKTIONSFÄHIG!\n";
} else {
    echo "\n📋 Empfohlene Aktionen:\n";
    if (! empty($errors)) {
        echo "   1. Behebe die Fehler oben\n";
        echo "   2. Führe dann erneut php retell-health-check.php aus\n";
    }
    if (! empty($warnings)) {
        echo "   3. Prüfe die Warnungen und entscheide ob Handlung nötig ist\n";
    }
}

echo "\n=== Health Check abgeschlossen ===\n\n";

// Exit code for monitoring
exit(empty($errors) ? 0 : 1);
