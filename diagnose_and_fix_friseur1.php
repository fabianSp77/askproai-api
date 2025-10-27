<?php

/**
 * Diagnose and Fix Friseur 1 Agent
 *
 * Problem: Agent zeigt alte "AskPro AI" Texte
 * Root Cause: Version Mismatch oder Unpublished Agent
 */

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$retellApiKey = config('services.retellai.api_key');
$agentId = 'agent_f1ce85d06a84afb989dfbb16a9';
$phoneNumber = '+493033081738';

echo "╔══════════════════════════════════════════════════════════════╗\n";
echo "║     Friseur 1 Agent - Diagnose & Fix                        ║\n";
echo "╚══════════════════════════════════════════════════════════════╝\n";
echo PHP_EOL;

// Step 1: Get Agent Status
echo "=== Step 1: Agent Status ===\n";
$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => "https://api.retellai.com/get-agent/{$agentId}",
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $retellApiKey]
]);
$response = curl_exec($ch);
curl_close($ch);

$agent = json_decode($response, true);

echo "Agent Version: {$agent['version']}\n";
echo "Published: " . ($agent['is_published'] ? 'Yes' : 'No') . "\n";
echo "Response Engine Version: {$agent['response_engine']['version']}\n";
echo "Flow ID: {$agent['response_engine']['conversation_flow_id']}\n";
echo PHP_EOL;

// Step 2: Get Phone Number Status
echo "=== Step 2: Phone Number Status ===\n";
$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => "https://api.retellai.com/list-phone-numbers",
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $retellApiKey]
]);
$response = curl_exec($ch);
curl_close($ch);

$phones = json_decode($response, true);
$friseur1Phone = null;

foreach ($phones as $phone) {
    if ($phone['phone_number'] === $phoneNumber) {
        $friseur1Phone = $phone;
        break;
    }
}

if ($friseur1Phone) {
    echo "Phone: {$friseur1Phone['phone_number']}\n";
    echo "Agent Version: {$friseur1Phone['inbound_agent_version']}\n";
    echo PHP_EOL;
} else {
    echo "❌ Phone number not found!\n";
    exit(1);
}

// Step 3: Check for mismatch
echo "=== Step 3: Diagnosis ===\n";

$issues = [];

if ($agent['version'] != $friseur1Phone['inbound_agent_version']) {
    $issues[] = "Version Mismatch: Agent is v{$agent['version']}, Phone uses v{$friseur1Phone['inbound_agent_version']}";
}

if (!$agent['is_published']) {
    $issues[] = "Agent is NOT published";
}

if (count($issues) > 0) {
    echo "❌ PROBLEMS FOUND:\n";
    foreach ($issues as $issue) {
        echo "  - {$issue}\n";
    }
    echo PHP_EOL;
} else {
    echo "✅ No problems found!\n";
    echo PHP_EOL;
    exit(0);
}

// Step 4: Fix - Publish Agent
echo "=== Step 4: Publishing Agent ===\n";

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => "https://api.retellai.com/publish-agent/{$agentId}",
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => [
        'Authorization: Bearer ' . $retellApiKey,
        'Content-Type: application/json'
    ]
]);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode === 200) {
    echo "✅ Agent published (HTTP 200)\n";
} else {
    echo "❌ Publish failed (HTTP {$httpCode})\n";
    echo "Response: {$response}\n";
}
echo PHP_EOL;

// Step 5: Get NEW agent version
echo "=== Step 5: Getting New Agent Version ===\n";

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => "https://api.retellai.com/get-agent/{$agentId}",
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $retellApiKey]
]);
$response = curl_exec($ch);
curl_close($ch);

$agentAfterPublish = json_decode($response, true);

echo "Agent Version NOW: {$agentAfterPublish['version']}\n";
echo "Published NOW: " . ($agentAfterPublish['is_published'] ? 'Yes' : 'No') . "\n";
echo PHP_EOL;

// Step 6: Update Phone Number
echo "=== Step 6: Updating Phone Number to v{$agentAfterPublish['version']} ===\n";

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => "https://api.retellai.com/update-phone-number/" . urlencode($phoneNumber),
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_CUSTOMREQUEST => 'PATCH',
    CURLOPT_HTTPHEADER => [
        'Authorization: Bearer ' . $retellApiKey,
        'Content-Type: application/json'
    ],
    CURLOPT_POSTFIELDS => json_encode([
        'inbound_agent_version' => $agentAfterPublish['version']
    ])
]);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode === 200) {
    $phoneAfterUpdate = json_decode($response, true);
    echo "✅ Phone updated to v{$phoneAfterUpdate['inbound_agent_version']}\n";
} else {
    echo "❌ Phone update failed (HTTP {$httpCode})\n";
    echo "Response: {$response}\n";
}
echo PHP_EOL;

// Summary
echo "╔══════════════════════════════════════════════════════════════╗\n";
echo "║                    FIX COMPLETE                              ║\n";
echo "╚══════════════════════════════════════════════════════════════╝\n";
echo PHP_EOL;

echo "✅ Friseur 1 Agent sollte jetzt korrekt sein!\n";
echo PHP_EOL;

echo "📞 Mach einen Test-Anruf: {$phoneNumber}\n";
echo PHP_EOL;
