#!/usr/bin/env php
<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Http;

$token = env('RETELL_TOKEN');
$agentId = 'agent_f1ce85d06a84afb989dfbb16a9';

echo "\n═══════════════════════════════════════════════════════════\n";
echo "📋 ALL AGENT VERSIONS OVERVIEW\n";
echo "═══════════════════════════════════════════════════════════\n\n";

// Get all versions by iterating
$allVersions = [];
$currentVersion = 74; // Start from latest

echo "Fetching versions...\n";

for ($v = $currentVersion; $v >= 50; $v--) {
    $response = Http::withHeaders([
        'Authorization' => "Bearer $token",
    ])->get("https://api.retellai.com/get-agent/$agentId?version=$v");

    if ($response->successful()) {
        $agent = $response->json();

        $toolCount = isset($agent['conversation_flow']['tools']) ? count($agent['conversation_flow']['tools']) : 0;
        $nodeCount = isset($agent['conversation_flow']['nodes']) ? count($agent['conversation_flow']['nodes']) : 0;
        $isPublished = $agent['is_published'] ?? false;

        $allVersions[$v] = [
            'version' => $v,
            'tools' => $toolCount,
            'nodes' => $nodeCount,
            'published' => $isPublished,
        ];

        if ($isPublished) {
            echo "  V$v: Published ✅\n";
        }
    }
}

echo "\n";
echo "Version Overview:\n";
echo "───────────────────────────────────────────────────────────\n";
printf("%-10s %-10s %-10s %-15s\n", "Version", "Tools", "Nodes", "Status");
echo "───────────────────────────────────────────────────────────\n";

foreach (array_reverse($allVersions) as $v) {
    $status = $v['published'] ? "PUBLISHED ✅" : "Draft";
    $toolIcon = $v['tools'] === 0 ? "❌ {$v['tools']}" : "✅ {$v['tools']}";
    $nodeIcon = $v['nodes'] === 0 ? "❌ {$v['nodes']}" : "✅ {$v['nodes']}";

    printf("%-10s %-10s %-10s %-15s\n",
        "V{$v['version']}",
        $toolIcon,
        $nodeIcon,
        $status
    );
}

// Find published version
$publishedVersion = null;
foreach ($allVersions as $v) {
    if ($v['published']) {
        $publishedVersion = $v;
        break;
    }
}

echo "\n";
echo "═══════════════════════════════════════════════════════════\n";
echo "CURRENTLY PUBLISHED VERSION\n";
echo "═══════════════════════════════════════════════════════════\n\n";

if ($publishedVersion) {
    $v = $publishedVersion['version'];
    $tools = $publishedVersion['tools'];
    $nodes = $publishedVersion['nodes'];

    echo "Version: $v\n";
    echo "Tools: $tools\n";
    echo "Nodes: $nodes\n\n";

    if ($tools === 0 || $nodes === 0) {
        echo "🚨 CRITICAL PROBLEM!\n";
        echo "───────────────────────────────────────────────────────────\n";
        echo "The published version is BROKEN:\n";
        if ($tools === 0) {
            echo "  ❌ 0 tools → Functions can NOT be called\n";
        }
        if ($nodes === 0) {
            echo "  ❌ 0 nodes → No conversation flow\n";
        }
        echo "\n";
        echo "This explains why:\n";
        echo "  - AI hallucinates function calls\n";
        echo "  - No backend webhooks are triggered\n";
        echo "  - Endpoints appear to 'not work'\n\n";

        echo "ACTION REQUIRED:\n";
        echo "───────────────────────────────────────────────────────────\n";

        // Find best version to publish
        $goodVersions = array_filter($allVersions, fn($v) => $v['tools'] === 7 && $v['nodes'] >= 10);

        if (!empty($goodVersions)) {
            echo "✅ Found good versions to publish:\n\n";
            foreach ($goodVersions as $gv) {
                echo "  V{$gv['version']}: {$gv['tools']} tools, {$gv['nodes']} nodes\n";
            }

            $bestVersion = reset($goodVersions)['version'];
            echo "\n📌 RECOMMENDED: Publish Version $bestVersion\n\n";
            echo "Steps:\n";
            echo "  1. Open: https://dashboard.retellai.com/agent/$agentId\n";
            echo "  2. Find: Version $bestVersion (7 tools, 11 nodes)\n";
            echo "  3. Click: PUBLISH button\n";
            echo "  4. Wait: 5 seconds\n";
            echo "  5. Test: Make a call to +493033081738\n\n";
        } else {
            echo "❌ NO good versions found in V50-V74 range\n";
            echo "   You may need to deploy a new version first\n\n";
        }
    } else {
        echo "✅ Published version looks good!\n";
        echo "   Tools and nodes are present.\n\n";
        echo "If functions still don't trigger, check:\n";
        echo "  1. Tool webhook URLs are correct\n";
        echo "  2. Function nodes have tool_id set\n";
        echo "  3. wait_for_result is enabled\n\n";
    }
} else {
    echo "⚠️  NO published version found!\n";
    echo "   This should not happen - every agent must have a published version.\n\n";
}
