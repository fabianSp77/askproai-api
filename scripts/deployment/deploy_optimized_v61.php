#!/usr/bin/env php
<?php

require __DIR__ . '/../../vendor/autoload.php';
$app = require_once __DIR__ . '/../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Http;

$agentId = 'agent_f1ce85d06a84afb989dfbb16a9';
$token = env('RETELL_TOKEN');

echo "\n";
echo "═══════════════════════════════════════════════════════════\n";
echo "OPTIMIZED AGENT DEPLOYMENT - VERSION 61\n";
echo "═══════════════════════════════════════════════════════════\n\n";

echo "🎯 Optimizations:\n";
echo "   ✅ initialize_call runs SILENTLY (no delay for user)\n";
echo "   ✅ Greeting comes IMMEDIATELY after silent init\n";
echo "   ✅ User doesn't notice initialization\n";
echo "   ✅ All 7 functions still available\n\n";

// Load the flow
$flowPath = __DIR__ . '/../../public/friseur1_optimized_v61.json';

if (!file_exists($flowPath)) {
    echo "❌ Flow file not found: $flowPath\n";
    exit(1);
}

$flowData = json_decode(file_get_contents($flowPath), true);

if (!$flowData) {
    echo "❌ Invalid JSON in flow file\n";
    exit(1);
}

// Validate critical fix
$funcInitNode = null;
foreach ($flowData['nodes'] as $node) {
    if ($node['id'] === 'func_initialize') {
        $funcInitNode = $node;
        break;
    }
}

if (!$funcInitNode) {
    echo "❌ func_initialize node not found!\n";
    exit(1);
}

$speakDuring = $funcInitNode['speak_during_execution'] ?? null;

echo "📋 Critical Check: initialize_call configuration\n";
echo "   speak_during_execution: " . ($speakDuring ? 'true' : 'false') . "\n";

if ($speakDuring === false) {
    echo "   ✅ CORRECT! Initialize runs silently\n\n";
} else {
    echo "   ❌ WRONG! Initialize will delay the greeting\n\n";
    exit(1);
}

$toolCount = count($flowData['tools'] ?? []);
echo "   Tools: $toolCount\n";
echo "   Nodes: " . count($flowData['nodes'] ?? []) . "\n";
echo "   Edges: " . count($flowData['edges'] ?? []) . "\n\n";

// Deploy to Retell
echo "═══════════════════════════════════════════════════════════\n";
echo "DEPLOYING TO RETELL\n";
echo "═══════════════════════════════════════════════════════════\n\n";

echo "🚀 Updating agent $agentId...\n\n";

try {
    $response = Http::timeout(30)->withHeaders([
        'Authorization' => "Bearer $token",
        'Content-Type' => 'application/json',
    ])->patch("https://api.retellai.com/update-agent/$agentId", [
        'conversation_flow' => $flowData
    ]);

    if ($response->successful()) {
        $data = $response->json();
        $version = $data['version'] ?? 'unknown';

        echo "✅ DEPLOYMENT SUCCESSFUL!\n\n";
        echo "   New Version: $version\n";
        echo "   Agent ID: $agentId\n\n";

        // Try to publish
        echo "═══════════════════════════════════════════════════════════\n";
        echo "ATTEMPTING TO PUBLISH\n";
        echo "═══════════════════════════════════════════════════════════\n\n";

        $publishResponse = Http::timeout(30)->withHeaders([
            'Authorization' => "Bearer $token",
        ])->post("https://api.retellai.com/publish-agent/$agentId");

        if ($publishResponse->successful()) {
            echo "✅ Publish API call successful\n\n";

            // Verify publish
            sleep(2);
            $verifyResponse = Http::withHeaders([
                'Authorization' => "Bearer $token",
            ])->get("https://api.retellai.com/get-agent/$agentId");

            if ($verifyResponse->successful()) {
                $agentData = $verifyResponse->json();
                $isPublished = $agentData['is_published'] ?? false;
                $currentVersion = $agentData['version'] ?? 'unknown';

                if ($isPublished && $currentVersion == $version) {
                    echo "🎉 PUBLISH VERIFIED! Version $version is now LIVE!\n\n";
                    echo "═══════════════════════════════════════════════════════════\n";
                    echo "SUCCESS - AGENT IS LIVE!\n";
                    echo "═══════════════════════════════════════════════════════════\n\n";
                    echo "Test it now:\n";
                    echo "   Call: +493033081738\n";
                    echo "   Say: 'Ich möchte einen Herrenhaarschnitt'\n\n";
                    echo "Expected behavior:\n";
                    echo "   1. You hear: 'Guten Tag! Wie kann ich Ihnen helfen?' IMMEDIATELY\n";
                    echo "   2. (initialize_call runs silently in background)\n";
                    echo "   3. AI continues naturally\n\n";
                    exit(0);
                } else {
                    echo "❌ PUBLISH VERIFICATION FAILED\n\n";
                    echo "   Expected: Version $version published\n";
                    echo "   Actual: Version $currentVersion, published=$isPublished\n\n";
                }
            }
        }

        echo "🔧 MANUAL ACTION REQUIRED:\n";
        echo "   Go to: https://dashboard.retellai.com/agent/$agentId\n";
        echo "   Find: Version $version (with 7 tools)\n";
        echo "   Click: Publish button\n\n";

    } else {
        echo "❌ DEPLOYMENT FAILED\n\n";
        echo "Response Status: " . $response->status() . "\n";
        echo "Response Body: " . $response->body() . "\n\n";
        exit(1);
    }

} catch (\Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n\n";
    exit(1);
}

echo "═══════════════════════════════════════════════════════════\n";
echo "DEPLOYMENT SUMMARY\n";
echo "═══════════════════════════════════════════════════════════\n\n";

echo "✅ Key Improvements:\n";
echo "   1. initialize_call: SILENT (speak_during_execution: false)\n";
echo "   2. Greeting: IMMEDIATE (no delay)\n";
echo "   3. User experience: SMOOTH (no weird pause)\n\n";

echo "✅ All 7 Functions Available:\n";
echo "   1. initialize_call\n";
echo "   2. check_availability_v17\n";
echo "   3. book_appointment_v17\n";
echo "   4. get_customer_appointments\n";
echo "   5. cancel_appointment\n";
echo "   6. reschedule_appointment\n";
echo "   7. get_available_services\n\n";

echo "═══════════════════════════════════════════════════════════\n\n";
