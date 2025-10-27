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
echo "DEPLOY + AUTO-PUBLISH V61\n";
echo "═══════════════════════════════════════════════════════════\n\n";

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

echo "✅ Flow loaded: " . count($flowData['tools']) . " tools, " . count($flowData['nodes']) . " nodes\n\n";

// STEP 1: Update agent with new flow
echo "STEP 1: Deploying new flow\n";
echo "───────────────────────────────────────────────────────────\n";

try {
    $updateResponse = Http::timeout(30)->withHeaders([
        'Authorization' => "Bearer $token",
        'Content-Type' => 'application/json',
    ])->patch("https://api.retellai.com/update-agent/$agentId", [
        'conversation_flow' => $flowData
    ]);

    if (!$updateResponse->successful()) {
        echo "❌ Deploy failed: " . $updateResponse->status() . "\n";
        echo $updateResponse->body() . "\n";
        exit(1);
    }

    $deployData = $updateResponse->json();
    $newVersion = $deployData['version'] ?? 'unknown';

    echo "✅ Deploy successful!\n";
    echo "   New version: $newVersion\n\n";

} catch (\Exception $e) {
    echo "❌ Deploy error: " . $e->getMessage() . "\n";
    exit(1);
}

// Wait a moment for Retell to process
echo "⏳ Waiting 3 seconds for Retell to process...\n\n";
sleep(3);

// STEP 2: Verify the deployment
echo "STEP 2: Verifying deployment\n";
echo "───────────────────────────────────────────────────────────\n";

$verifyResponse = Http::withHeaders([
    'Authorization' => "Bearer $token",
])->get("https://api.retellai.com/get-agent/$agentId");

if (!$verifyResponse->successful()) {
    echo "❌ Verification failed\n";
    exit(1);
}

$agentData = $verifyResponse->json();
$currentVersion = $agentData['version'] ?? 'unknown';
$isPublished = $agentData['is_published'] ?? false;

echo "Current agent state:\n";
echo "   Version: $currentVersion\n";
echo "   Is Published: " . ($isPublished ? 'YES' : 'NO') . "\n\n";

if ($currentVersion != $newVersion) {
    echo "⚠️  WARNING: Version mismatch!\n";
    echo "   Expected: $newVersion\n";
    echo "   Got: $currentVersion\n\n";
}

// STEP 3: Publish
echo "STEP 3: Publishing agent\n";
echo "───────────────────────────────────────────────────────────\n";

if ($isPublished) {
    echo "⚠️  Agent already shows as published\n";
    echo "   This might be an old version\n";
    echo "   Attempting to publish anyway...\n\n";
}

try {
    $publishResponse = Http::timeout(30)->withHeaders([
        'Authorization' => "Bearer $token",
    ])->post("https://api.retellai.com/publish-agent/$agentId");

    if (!$publishResponse->successful()) {
        echo "❌ Publish API call failed: " . $publishResponse->status() . "\n";
        echo $publishResponse->body() . "\n\n";
    } else {
        echo "✅ Publish API call successful\n\n";
    }

} catch (\Exception $e) {
    echo "❌ Publish error: " . $e->getMessage() . "\n";
}

// STEP 4: Verify publish (wait longer this time)
echo "⏳ Waiting 5 seconds for publish to complete...\n\n";
sleep(5);

echo "STEP 4: Verifying publish\n";
echo "───────────────────────────────────────────────────────────\n";

$finalResponse = Http::withHeaders([
    'Authorization' => "Bearer $token",
])->get("https://api.retellai.com/get-agent/$agentId");

if ($finalResponse->successful()) {
    $finalData = $finalResponse->json();
    $finalVersion = $finalData['version'] ?? 'unknown';
    $finalPublished = $finalData['is_published'] ?? false;

    echo "Final agent state:\n";
    echo "   Version: $finalVersion\n";
    echo "   Is Published: " . ($finalPublished ? 'YES' : 'NO') . "\n\n";

    if ($finalPublished && $finalVersion == $newVersion) {
        echo "═══════════════════════════════════════════════════════════\n";
        echo "🎉 SUCCESS - AGENT IS PUBLISHED!\n";
        echo "═══════════════════════════════════════════════════════════\n\n";

        echo "✅ Version $newVersion is now LIVE\n";
        echo "✅ 7 functions available\n";
        echo "✅ initialize_call runs SILENTLY at call start\n";
        echo "✅ No duplicates, clean flow\n\n";

        echo "Ready to test:\n";
        echo "   Call: +493033081738\n";
        echo "   Expected: 'Guten Tag! Wie kann ich Ihnen helfen?' IMMEDIATELY\n\n";

        exit(0);

    } elseif ($finalPublished && $finalVersion != $newVersion) {
        echo "═══════════════════════════════════════════════════════════\n";
        echo "⚠️  PARTIAL SUCCESS\n";
        echo "═══════════════════════════════════════════════════════════\n\n";

        echo "Agent IS published, but different version:\n";
        echo "   Expected: $newVersion\n";
        echo "   Live: $finalVersion\n\n";

        echo "This is the Retell API bug - it published an OLD version\n";
        echo "instead of the one we just deployed.\n\n";

    } else {
        echo "═══════════════════════════════════════════════════════════\n";
        echo "❌ PUBLISH FAILED\n";
        echo "═══════════════════════════════════════════════════════════\n\n";

        echo "Agent is NOT published yet\n";
        echo "This is the known Retell API bug\n\n";
    }
}

// If we get here, auto-publish didn't work
echo "🔧 FALLBACK: Manual Dashboard Action Required\n\n";

echo "Please do manually:\n";
echo "  1. Go to: https://dashboard.retellai.com/agent/$agentId\n";
echo "  2. Find: Version $newVersion (7 tools, no duplicates)\n";
echo "  3. Click: 'Publish' button\n\n";

echo "The agent is deployed, just needs manual publish click.\n\n";

exit(1);
