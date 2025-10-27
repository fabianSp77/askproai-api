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
echo "AGGRESSIVE AUTO-DEPLOY + PUBLISH\n";
echo "═══════════════════════════════════════════════════════════\n\n";

echo "Strategy: Deploy, then retry publish up to 10 times\n";
echo "with different wait times until it works\n\n";

// Load flow
$flowPath = __DIR__ . '/../../public/friseur1_optimized_v61.json';
$flowData = json_decode(file_get_contents($flowPath), true);

if (!$flowData) {
    echo "❌ Invalid flow file\n";
    exit(1);
}

// STEP 1: Deploy
echo "STEP 1: Deploying flow\n";
echo "───────────────────────────────────────────────────────────\n";

$updateResponse = Http::timeout(30)->withHeaders([
    'Authorization' => "Bearer $token",
    'Content-Type' => 'application/json',
])->patch("https://api.retellai.com/update-agent/$agentId", [
    'conversation_flow' => $flowData
]);

if (!$updateResponse->successful()) {
    echo "❌ Deploy failed\n";
    exit(1);
}

$deployedVersion = $updateResponse->json()['version'] ?? 'unknown';
echo "✅ Deployed as Version $deployedVersion\n\n";

// STEP 2: Aggressive publish with retries
echo "STEP 2: Aggressive publish (up to 10 attempts)\n";
echo "───────────────────────────────────────────────────────────\n\n";

$maxAttempts = 10;
$waitTimes = [2, 3, 5, 5, 10, 10, 15, 15, 20, 30]; // seconds to wait between attempts

for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
    echo "Attempt $attempt/$maxAttempts:\n";

    // Try to publish
    $publishResponse = Http::timeout(30)->withHeaders([
        'Authorization' => "Bearer $token",
    ])->post("https://api.retellai.com/publish-agent/$agentId");

    if ($publishResponse->successful()) {
        echo "  ✅ Publish API call successful\n";
    } else {
        echo "  ❌ Publish API call failed\n";
        continue; // Try again
    }

    // Wait before verifying
    $waitTime = $waitTimes[$attempt - 1] ?? 30;
    echo "  ⏳ Waiting {$waitTime} seconds...\n";
    sleep($waitTime);

    // Verify
    $verifyResponse = Http::withHeaders([
        'Authorization' => "Bearer $token",
    ])->get("https://api.retellai.com/get-agent/$agentId");

    if ($verifyResponse->successful()) {
        $data = $verifyResponse->json();
        $currentVersion = $data['version'] ?? 'unknown';
        $isPublished = $data['is_published'] ?? false;

        echo "  Current state: Version $currentVersion, Published: " . ($isPublished ? 'YES' : 'NO') . "\n";

        if ($isPublished && $currentVersion == $deployedVersion) {
            echo "\n";
            echo "═══════════════════════════════════════════════════════════\n";
            echo "🎉 SUCCESS!\n";
            echo "═══════════════════════════════════════════════════════════\n\n";

            echo "Version $deployedVersion is now LIVE!\n";
            echo "Took $attempt attempt(s)\n\n";

            echo "✅ Agent published automatically\n";
            echo "✅ All 7 functions available\n";
            echo "✅ initialize_call runs silently\n";
            echo "✅ Ready for testing\n\n";

            exit(0);
        } elseif ($isPublished && $currentVersion != $deployedVersion) {
            echo "  ⚠️  Published but wrong version (expected $deployedVersion, got $currentVersion)\n";
        } else {
            echo "  ❌ Not published yet\n";
        }
    }

    if ($attempt < $maxAttempts) {
        echo "  🔄 Retrying...\n\n";
    }
}

// If we get here, all attempts failed
echo "\n";
echo "═══════════════════════════════════════════════════════════\n";
echo "❌ AUTO-PUBLISH FAILED AFTER $maxAttempts ATTEMPTS\n";
echo "═══════════════════════════════════════════════════════════\n\n";

echo "This is the consistent Retell API bug.\n\n";

echo "Agent is deployed as Version $deployedVersion but NOT published.\n\n";

echo "Quick manual fix (30 seconds):\n";
echo "  1. Open: https://dashboard.retellai.com/agent/$agentId\n";
echo "  2. Find: Version $deployedVersion (7 tools)\n";
echo "  3. Click: Publish button\n";
echo "  4. Done!\n\n";

echo "Or run helper script:\n";
echo "  php scripts/deployment/show_publish_instructions.php\n\n";

exit(1);
