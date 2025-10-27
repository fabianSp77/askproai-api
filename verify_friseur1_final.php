<?php

/**
 * Final Verification - Friseur 1 Agent
 */

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$retellApiKey = config('services.retellai.api_key');
$agentId = 'agent_f1ce85d06a84afb989dfbb16a9';
$flowId = 'conversation_flow_1607b81c8f93';
$phoneNumber = '+493033081738';

echo "╔══════════════════════════════════════════════════════════════╗\n";
echo "║         FINALE VERIFIKATION - FRISEUR 1                     ║\n";
echo "╚══════════════════════════════════════════════════════════════╝\n";
echo PHP_EOL;

// Get Flow
$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => "https://api.retellai.com/get-conversation-flow/{$flowId}",
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $retellApiKey]
]);
$response = curl_exec($ch);
curl_close($ch);

$flow = json_decode($response, true);

// Check greeting node
echo "=== 1. BEGRÜSSUNG ===\n";
foreach ($flow['nodes'] as $node) {
    if ($node['id'] === 'func_00_initialize') {
        $greeting = $node['instruction']['text'] ?? 'N/A';
        echo "Node: {$node['name']}\n";
        echo "Text: \"{$greeting}\"\n";

        if (stripos($greeting, 'askpro') !== false || stripos($greeting, 'ask pro') !== false) {
            echo "Status: ❌ IMMER NOCH ASKPRO!\n";
        } elseif (stripos($greeting, 'friseur') !== false) {
            echo "Status: ✅ FRISEUR 1 - KORREKT!\n";
        } else {
            echo "Status: ⚠️  Neutral\n";
        }
        echo PHP_EOL;
        break;
    }
}

// Check global prompt
echo "=== 2. GLOBAL PROMPT ===\n";
$promptStart = substr($flow['global_prompt'], 0, 100);
echo "Start: \"{$promptStart}...\"\n";
if (strpos($flow['global_prompt'], 'Friseur 1') !== false) {
    echo "Status: ✅ Friseur 1 Branding vorhanden\n";
} else {
    echo "Status: ❌ Kein Friseur 1 Branding!\n";
}
echo PHP_EOL;

// Check tool descriptions
echo "=== 3. TOOL DESCRIPTIONS ===\n";
$toolsOk = 0;
foreach ($flow['tools'] as $tool) {
    if (in_array($tool['name'], ['collect_appointment_data', 'check_availability_v17', 'book_appointment_v17'])) {
        $dienstDesc = $tool['parameters']['properties']['dienstleistung']['description'] ?? 'N/A';

        if (stripos($dienstDesc, 'herrenhaarschnitt') !== false) {
            echo "✅ {$tool['name']}: Friseur Services\n";
            $toolsOk++;
        } else {
            echo "❌ {$tool['name']}: {$dienstDesc}\n";
        }
    }
}
echo PHP_EOL;

// Get Agent & Phone Status
$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => "https://api.retellai.com/get-agent/{$agentId}",
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $retellApiKey]
]);
$response = curl_exec($ch);
curl_close($ch);
$agent = json_decode($response, true);

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => "https://api.retellai.com/list-phone-numbers",
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $retellApiKey]
]);
$response = curl_exec($ch);
curl_close($ch);
$phones = json_decode($response, true);

$phoneVersion = null;
foreach ($phones as $phone) {
    if ($phone['phone_number'] === $phoneNumber) {
        $phoneVersion = $phone['inbound_agent_version'];
        break;
    }
}

echo "=== 4. VERSIONEN ===\n";
echo "Agent Version: {$agent['version']}\n";
echo "Phone Version: {$phoneVersion}\n";

if ($agent['version'] == $phoneVersion) {
    echo "Status: ✅ SYNCHRONISIERT!\n";
} else {
    echo "Status: ❌ MISMATCH!\n";
}
echo PHP_EOL;

// Summary
echo "╔══════════════════════════════════════════════════════════════╗\n";
echo "║                    FINAL STATUS                              ║\n";
echo "╚══════════════════════════════════════════════════════════════╝\n";
echo PHP_EOL;

$allOk = true;

// Check all criteria
if (stripos($node['instruction']['text'] ?? '', 'friseur') === false) {
    echo "❌ Begrüßung: Nicht Friseur 1\n";
    $allOk = false;
} else {
    echo "✅ Begrüßung: Friseur 1\n";
}

if (strpos($flow['global_prompt'], 'Friseur 1') === false) {
    echo "❌ Global Prompt: Nicht Friseur 1\n";
    $allOk = false;
} else {
    echo "✅ Global Prompt: Friseur 1\n";
}

if ($toolsOk < 3) {
    echo "❌ Tool Descriptions: Nicht alle korrekt\n";
    $allOk = false;
} else {
    echo "✅ Tool Descriptions: Alle korrekt\n";
}

if ($agent['version'] != $phoneVersion) {
    echo "❌ Versionen: Nicht synchronisiert\n";
    $allOk = false;
} else {
    echo "✅ Versionen: Synchronisiert (V{$agent['version']})\n";
}

echo PHP_EOL;

if ($allOk) {
    echo "🎉 ALLES PERFEKT! BEREIT FÜR TEST-ANRUF!\n";
    echo PHP_EOL;
    echo "📞 Ruf jetzt an: {$phoneNumber}\n";
    echo PHP_EOL;
    echo "Du solltest hören:\n";
    echo "\"Guten Tag bei Friseur 1, mein Name ist Carola. Wie kann ich Ihnen helfen?\"\n";
} else {
    echo "⚠️  ES GIBT NOCH PROBLEME!\n";
    echo PHP_EOL;
}
