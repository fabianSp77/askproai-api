#!/usr/bin/env php
<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Http;

$token = env('RETELL_TOKEN');
$agentId = 'agent_2d467d84eb674e5b3f5815d81c';

echo "\n═══════════════════════════════════════════════════════════\n";
echo "📋 NEW AGENT - ALL VERSIONS CHECK\n";
echo "═══════════════════════════════════════════════════════════\n\n";

$versions = [];

// Check versions 0-3
for ($v = 0; $v <= 3; $v++) {
    $response = Http::withHeaders([
        'Authorization' => "Bearer $token",
    ])->get("https://api.retellai.com/get-agent/$agentId?version=$v");

    if ($response->successful()) {
        $agent = $response->json();

        $isPublished = $agent['is_published'] ?? false;

        // Get flow info if exists
        $toolCount = 0;
        $nodeCount = 0;

        if (isset($agent['response_engine']['conversation_flow_id'])) {
            $flowId = $agent['response_engine']['conversation_flow_id'];

            $flowResponse = Http::withHeaders([
                'Authorization' => "Bearer $token",
            ])->get("https://api.retellai.com/get-conversation-flow/$flowId");

            if ($flowResponse->successful()) {
                $flow = $flowResponse->json();
                $toolCount = isset($flow['tools']) ? count($flow['tools']) : 0;
                $nodeCount = isset($flow['nodes']) ? count($flow['nodes']) : 0;
            }
        }

        $versions[$v] = [
            'version' => $v,
            'published' => $isPublished,
            'tools' => $toolCount,
            'nodes' => $nodeCount,
        ];
    }
}

echo "Version Overview:\n";
echo "───────────────────────────────────────────────────────────\n";
printf("%-10s %-15s %-10s %-10s\n", "Version", "Status", "Tools", "Nodes");
echo "───────────────────────────────────────────────────────────\n";

$publishedVersion = null;

foreach ($versions as $v) {
    $status = $v['published'] ? "PUBLISHED ✅" : "Draft";
    $toolIcon = $v['tools'] > 0 ? "✅ {$v['tools']}" : "❌ {$v['tools']}";
    $nodeIcon = $v['nodes'] > 0 ? "✅ {$v['nodes']}" : "❌ {$v['nodes']}";

    printf("%-10s %-15s %-10s %-10s\n",
        "V{$v['version']}",
        $status,
        $toolIcon,
        $nodeIcon
    );

    if ($v['published']) {
        $publishedVersion = $v;
    }
}

echo "\n";
echo "═══════════════════════════════════════════════════════════\n";
echo "DIAGNOSIS\n";
echo "═══════════════════════════════════════════════════════════\n\n";

if (!$publishedVersion) {
    echo "❌ NO PUBLISHED VERSION!\n";
    echo "   Phone calls will FAIL!\n\n";

    echo "ACTION REQUIRED:\n";
    echo "  Publish Version 0 (or whichever has tools):\n";
    echo "  → Dashboard: https://dashboard.retellai.com/agent/$agentId\n";
    echo "  → Or run publish script\n\n";

    exit(1);
}

$pv = $publishedVersion['version'];
echo "Currently Published: Version $pv\n";
echo "  Tools: {$publishedVersion['tools']}\n";
echo "  Nodes: {$publishedVersion['nodes']}\n\n";

if ($publishedVersion['tools'] === 0 || $publishedVersion['nodes'] === 0) {
    echo "❌ CRITICAL: Published version is BROKEN!\n";
    echo "   This is why function calls don't work!\n\n";

    // Find good version
    $goodVersions = array_filter($versions, fn($v) => $v['tools'] > 0 && $v['nodes'] > 0);

    if (!empty($goodVersions)) {
        $bestVersion = reset($goodVersions)['version'];
        echo "✅ Version $bestVersion has tools and nodes\n";
        echo "   ACTION: Publish Version $bestVersion\n\n";
    }

    exit(1);
}

echo "✅ Published version looks good!\n";
echo "   Configuration is correct.\n\n";

echo "IF test calls still don't work:\n";
echo "  → The problem is in the flow logic\n";
echo "  → Check flow path and transitions\n";
echo "  → Make a new test call with monitoring\n\n";
