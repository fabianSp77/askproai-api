#!/usr/bin/env php
<?php

/**
 * Complete V48 Verification
 * Verifies all components are working correctly
 */

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Http;
use Carbon\Carbon;

echo "\n";
echo "═══════════════════════════════════════════════════════════════\n";
echo " V48 Complete System Verification\n";
echo "═══════════════════════════════════════════════════════════════\n";
echo "\n";

$retellApiKey = config('services.retellai.api_key');
$agentId = 'agent_45daa54928c5768b52ba3db736';
$conversationFlowId = 'conversation_flow_a58405e3f67a';
$baseUrl = rtrim(config('services.retellai.base_url', 'https://api.retellai.com'), '/');

$allChecks = [];

// CHECK 1: Backend Dynamic Date Endpoint
echo "🔍 CHECK 1: Backend Dynamic Date Endpoint\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

try {
    $response = Http::post('http://localhost/api/webhooks/retell/current-context');

    if ($response->successful()) {
        $data = $response->json();
        $now = Carbon::now('Europe/Berlin');

        $checks = [
            'Endpoint erreichbar' => $response->successful(),
            'Datum vorhanden' => isset($data['date']),
            'Zeit vorhanden' => isset($data['time']),
            'Wochentag vorhanden' => isset($data['day_of_week']),
            'Datum korrekt' => ($data['date'] ?? '') === $now->format('Y-m-d'),
        ];

        foreach ($checks as $name => $result) {
            echo ($result ? '✅' : '❌') . " {$name}\n";
            $allChecks["Backend: {$name}"] = $result;
        }

        echo "\n📋 Response Data:\n";
        echo "  Date: " . ($data['date'] ?? 'N/A') . "\n";
        echo "  Time: " . ($data['time'] ?? 'N/A') . "\n";
        echo "  Day: " . ($data['day_of_week'] ?? 'N/A') . "\n";
        echo "\n";
    } else {
        echo "❌ Endpoint nicht erreichbar\n\n";
        $allChecks["Backend: Endpoint erreichbar"] = false;
    }
} catch (\Exception $e) {
    echo "❌ Fehler: " . $e->getMessage() . "\n\n";
    $allChecks["Backend: Endpoint erreichbar"] = false;
}

// CHECK 2: Agent Configuration
echo "🔍 CHECK 2: Agent Configuration\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

$agentResponse = Http::withHeaders([
    'Authorization' => "Bearer {$retellApiKey}",
])->get("{$baseUrl}/get-agent/{$agentId}");

if ($agentResponse->successful()) {
    $agent = $agentResponse->json();

    $agentChecks = [
        'Agent existiert' => isset($agent['agent_id']),
        'Ist V48' => strpos($agent['agent_name'] ?? '', 'V48') !== false,
        'Correct Flow' => ($agent['response_engine']['conversation_flow_id'] ?? '') === $conversationFlowId,
        'German Language' => ($agent['language'] ?? '') === 'de-DE',
    ];

    foreach ($agentChecks as $name => $result) {
        echo ($result ? '✅' : '❌') . " {$name}\n";
        $allChecks["Agent: {$name}"] = $result;
    }

    echo "\n📋 Agent Details:\n";
    echo "  ID: " . ($agent['agent_id'] ?? 'N/A') . "\n";
    echo "  Name: " . ($agent['agent_name'] ?? 'N/A') . "\n";
    echo "  Voice: " . ($agent['voice_id'] ?? 'N/A') . "\n";
    echo "\n";
} else {
    echo "❌ Konnte Agent nicht abrufen\n\n";
    $allChecks["Agent: Agent existiert"] = false;
}

// CHECK 3: Conversation Flow V48
echo "🔍 CHECK 3: Conversation Flow V48\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

$flowResponse = Http::withHeaders([
    'Authorization' => "Bearer {$retellApiKey}",
])->get("{$baseUrl}/get-conversation-flow/{$conversationFlowId}");

if ($flowResponse->successful()) {
    $flow = $flowResponse->json();
    $prompt = $flow['global_prompt'] ?? '';

    $flowChecks = [
        'Prompt existiert' => strlen($prompt) > 0,
        'V48 marker' => strpos($prompt, 'V48 (2025 Optimized') !== false,
        'Dynamic Date {{current_date}}' => strpos($prompt, '{{current_date}}') !== false,
        'Voice-Optimized section' => strpos($prompt, 'Voice-Optimized') !== false,
        'Context Management' => strpos($prompt, 'Context Management') !== false,
        'NO hardcoded "05. November"' => strpos($prompt, '05. November 2025') === false,
        'NO hardcoded "Mittwoch"' => strpos($prompt, 'HEUTE IST: Mittwoch') === false,
    ];

    foreach ($flowChecks as $name => $result) {
        echo ($result ? '✅' : '❌') . " {$name}\n";
        $allChecks["Flow: {$name}"] = $result;
    }

    echo "\n📋 Flow Details:\n";
    echo "  ID: {$conversationFlowId}\n";
    echo "  Prompt Length: " . strlen($prompt) . " characters\n";
    echo "\n";
} else {
    echo "❌ Konnte Flow nicht abrufen\n\n";
    $allChecks["Flow: Prompt existiert"] = false;
}

// CHECK 4: Tools Verification
echo "🔍 CHECK 4: Tools Verification\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

if ($flowResponse->successful()) {
    $tools = $flow['tools'] ?? [];

    $expectedTools = [
        'check_availability_v17',
        'book_appointment_v17',
        'start_booking',
        'confirm_booking',
        'get_customer_appointments',
        'cancel_appointment',
        'reschedule_appointment',
        'get_available_services',
        'get_current_context',
    ];

    $foundTools = array_column($tools, 'name');

    echo "Expected: " . count($expectedTools) . " tools\n";
    echo "Found: " . count($foundTools) . " tools\n\n";

    foreach ($expectedTools as $tool) {
        $found = in_array($tool, $foundTools);
        echo ($found ? '✅' : '❌') . " {$tool}\n";
        $allChecks["Tool: {$tool}"] = $found;
    }

    echo "\n";
}

// CHECK 5: Critical Fixes Applied
echo "🔍 CHECK 5: Critical Fixes from Research\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

$criticalFixes = [
    'Fix: Dynamic Date (NOT hardcoded)' => !strpos($prompt, 'HEUTE IST: Mittwoch, 05. November 2025'),
    'Fix: Voice-First (max 2 sentences)' => strpos($prompt, 'max. 2 Sätze') !== false,
    'Fix: Vary responses' => strpos($prompt, 'Variiere') !== false,
    'Fix: Context-aware' => strpos($prompt, 'Context Management & State') !== false,
    'Fix: Tool-Call Enforcement' => strpos($prompt, 'NIEMALS Verfügbarkeit erfinden') !== false,
    'Fix: NO example times in prompt' => substr_count($prompt, '14:00, 16:30 und 18:00') <= 1,
];

foreach ($criticalFixes as $name => $result) {
    echo ($result ? '✅' : '❌') . " {$name}\n";
    $allChecks[$name] = $result;
}

// SUMMARY
echo "\n";
echo "═══════════════════════════════════════════════════════════════\n";
echo " VERIFICATION SUMMARY\n";
echo "═══════════════════════════════════════════════════════════════\n";
echo "\n";

$passed = count(array_filter($allChecks));
$total = count($allChecks);
$percentage = round(($passed / $total) * 100, 1);

echo "Passed: {$passed}/{$total} checks ({$percentage}%)\n\n";

if ($passed === $total) {
    echo "🎉 ✅ ALLE CHECKS BESTANDEN!\n";
    echo "\n";
    echo "V48 ist vollständig implementiert und bereit für Production:\n";
    echo "  ✅ Backend: Dynamic Date Injection\n";
    echo "  ✅ Agent: V48 Name und Configuration\n";
    echo "  ✅ Flow: V48 Optimized Prompt\n";
    echo "  ✅ Tools: 9/9 inkl. get_current_context\n";
    echo "  ✅ Fixes: Alle State-of-the-art Optimierungen\n";
    echo "\n";
    echo "🚀 Ready to TEST!\n";
    echo "\n";
    exit(0);
} else {
    $failed = $total - $passed;
    echo "⚠️  {$failed} CHECKS FEHLGESCHLAGEN\n\n";

    echo "Fehlgeschlagene Checks:\n";
    foreach ($allChecks as $name => $result) {
        if (!$result) {
            echo "  ❌ {$name}\n";
        }
    }

    echo "\n";
    exit(1);
}
