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
echo "AUTO-PUBLISH RETELL AGENT\n";
echo "═══════════════════════════════════════════════════════════\n\n";

// Get current agent state
echo "🔍 Checking current agent state...\n\n";

$response = Http::withHeaders([
    'Authorization' => "Bearer $token",
])->get("https://api.retellai.com/get-agent/$agentId");

if (!$response->successful()) {
    echo "❌ Failed to get agent status\n";
    exit(1);
}

$agent = $response->json();
$currentVersion = $agent['version'] ?? 'unknown';
$isPublished = $agent['is_published'] ?? false;

echo "Current state:\n";
echo "  Version: $currentVersion\n";
echo "  Published: " . ($isPublished ? 'YES' : 'NO') . "\n\n";

if ($isPublished) {
    echo "✅ Agent is already published (Version $currentVersion)\n";
    echo "Nothing to do!\n\n";
    exit(0);
}

echo "Agent is NOT published. Attempting to publish...\n\n";

// Method 1: Try standard publish endpoint
echo "📤 Method 1: Standard /publish-agent endpoint\n";

$publishResponse = Http::withHeaders([
    'Authorization' => "Bearer $token",
])->post("https://api.retellai.com/publish-agent/$agentId");

if ($publishResponse->successful()) {
    echo "✅ API call successful\n";

    // Verify
    sleep(3);

    $verifyResponse = Http::withHeaders([
        'Authorization' => "Bearer $token",
    ])->get("https://api.retellai.com/get-agent/$agentId");

    if ($verifyResponse->successful()) {
        $verifyData = $verifyResponse->json();
        $nowPublished = $verifyData['is_published'] ?? false;
        $nowVersion = $verifyData['version'] ?? 'unknown';

        if ($nowPublished) {
            echo "🎉 SUCCESS! Agent is now published (Version $nowVersion)\n\n";
            exit(0);
        } else {
            echo "⚠️  API said success but verification shows NOT published\n";
            echo "   This is the known Retell API bug\n\n";
        }
    }
} else {
    echo "❌ API call failed: " . $publishResponse->status() . "\n\n";
}

// Method 2: Try update-agent with conversation_flow (force re-publish)
echo "📤 Method 2: Update agent conversation_flow\n";
echo "   (Sometimes triggers auto-publish)\n\n";

// Get current flow
$flowResponse = Http::withHeaders([
    'Authorization' => "Bearer $token",
])->get("https://api.retellai.com/get-agent/$agentId");

if ($flowResponse->successful()) {
    $agentData = $flowResponse->json();
    $conversationFlow = $agentData['conversation_flow'] ?? null;

    if ($conversationFlow) {
        // Re-apply same flow (sometimes triggers publish)
        $updateResponse = Http::withHeaders([
            'Authorization' => "Bearer $token",
            'Content-Type' => 'application/json',
        ])->patch("https://api.retellai.com/update-agent/$agentId", [
            'conversation_flow' => $conversationFlow
        ]);

        if ($updateResponse->successful()) {
            echo "✅ Flow re-applied\n";

            sleep(3);

            // Verify again
            $verifyResponse2 = Http::withHeaders([
                'Authorization' => "Bearer $token",
            ])->get("https://api.retellai.com/get-agent/$agentId");

            if ($verifyResponse2->successful()) {
                $verify2Data = $verifyResponse2->json();
                $nowPublished2 = $verify2Data['is_published'] ?? false;

                if ($nowPublished2) {
                    echo "🎉 SUCCESS! Agent is now published\n\n";
                    exit(0);
                }
            }
        }
    }
}

// All methods failed
echo "\n";
echo "═══════════════════════════════════════════════════════════\n";
echo "❌ AUTO-PUBLISH FAILED\n";
echo "═══════════════════════════════════════════════════════════\n\n";

echo "Retell API bug: Publish endpoint doesn't work programmatically\n\n";

echo "🔧 SOLUTION: Use Puppeteer to automate Dashboard\n\n";

echo "Manual steps (for now):\n";
echo "  1. Go to: https://dashboard.retellai.com/agent/$agentId\n";
echo "  2. Find Version $currentVersion\n";
echo "  3. Click 'Publish'\n\n";

echo "Or run:\n";
echo "  node scripts/deployment/puppeteer-publish.js\n\n";

exit(1);
