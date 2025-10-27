#!/usr/bin/env php
<?php

require __DIR__ . '/../../vendor/autoload.php';
$app = require_once __DIR__ . '/../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Http;

$agentId = 'agent_f1ce85d06a84afb989dfbb16a9';
$flowPath = __DIR__ . '/../../public/friseur1_flow_v_PRODUCTION_FIXED.json';
$token = env('RETELL_TOKEN');

echo "\n";
echo "═══════════════════════════════════════════════════════════\n";
echo "DEPLOY & PUBLISH - Production Fixed Flow\n";
echo "═══════════════════════════════════════════════════════════\n\n";

if (!file_exists($flowPath)) {
    die("❌ Flow file not found: $flowPath\n");
}

$flowData = json_decode(file_get_contents($flowPath), true);

if (!$flowData) {
    die("❌ Invalid JSON in flow file\n");
}

echo "Flow: " . basename($flowPath) . "\n";
echo "Agent: $agentId\n";
echo "Tools in flow: " . count($flowData['tools'] ?? []) . "\n";
echo "Nodes in flow: " . count($flowData['nodes'] ?? []) . "\n\n";

echo "STEP 1: Deploying flow...\n";

$deployResponse = Http::timeout(30)->withHeaders([
    'Authorization' => "Bearer $token",
    'Content-Type' => 'application/json',
])->patch("https://api.retellai.com/update-agent/$agentId", [
    'conversation_flow' => $flowData
]);

if (!$deployResponse->successful()) {
    echo "❌ DEPLOY FAILED\n";
    echo "HTTP Status: " . $deployResponse->status() . "\n";
    echo "Error: " . $deployResponse->body() . "\n\n";
    exit(1);
}

echo "✅ Deploy successful!\n\n";

echo "STEP 2: Publishing agent (making it LIVE)...\n";

sleep(1); // Give API a moment

$publishResponse = Http::timeout(30)->withHeaders([
    'Authorization' => "Bearer $token",
    'Content-Type' => 'application/json',
])->post("https://api.retellai.com/publish-agent/$agentId");

if (!$publishResponse->successful()) {
    echo "❌ PUBLISH FAILED\n";
    echo "HTTP Status: " . $publishResponse->status() . "\n";
    echo "Error: " . $publishResponse->body() . "\n\n";
    exit(1);
}

echo "✅ Publish successful!\n\n";

echo "STEP 3: Verifying published status...\n";

sleep(2); // Give API time to update

$verifyResponse = Http::withHeaders([
    'Authorization' => "Bearer $token",
])->get("https://api.retellai.com/get-agent/$agentId");

if ($verifyResponse->successful()) {
    $agent = $verifyResponse->json();

    echo "Agent Version: " . ($agent['version'] ?? 'N/A') . "\n";
    echo "Is Published: " . (($agent['is_published'] ?? false) ? 'YES ✅' : 'NO ❌') . "\n";
    echo "Version Title: " . ($agent['version_title'] ?? 'N/A') . "\n\n";

    if ($agent['is_published'] ?? false) {
        echo "═══════════════════════════════════════════════════════════\n";
        echo "🎉 SUCCESS! Flow is now LIVE!\n";
        echo "═══════════════════════════════════════════════════════════\n\n";

        echo "Next steps:\n";
        echo "1. Fix phone mapping: +493033081738 → $agentId\n";
        echo "2. Make test call\n";
        echo "3. Verify functions were called\n\n";

        exit(0);
    } else {
        echo "⚠️  WARNING: Agent shows as NOT published\n";
        echo "This might be a Retell API timing issue.\n\n";
        echo "Please check dashboard manually:\n";
        echo "https://dashboard.retellai.com/agent/$agentId\n\n";
        exit(1);
    }
} else {
    echo "❌ Verification failed\n";
    exit(1);
}
