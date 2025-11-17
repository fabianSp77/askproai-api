#!/usr/bin/env php
<?php

require __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

$apiKey = $_ENV['RETELLAI_API_KEY'] ?? $_ENV['RETELL_TOKEN'];
$baseUrl = 'https://api.retellai.com';
$v109FlowId = 'conversation_flow_a58405e3f67a';

echo "\n";
echo "═══════════════════════════════════════════════════════════\n";
echo "  ALL AGENTS WITH FLOW IDS\n";
echo "═══════════════════════════════════════════════════════════\n\n";

// List all agents
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "$baseUrl/list-agents");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer $apiKey"
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode !== 200) {
    echo "❌ Failed to list agents\n";
    echo "HTTP Status: $httpCode\n";
    exit(1);
}

$agents = json_decode($response, true);
echo "Found " . count($agents) . " agents\n\n";

$v109Agents = [];

foreach ($agents as $agent) {
    $agentId = $agent['agent_id'] ?? 'N/A';
    $agentName = $agent['agent_name'] ?? 'N/A';
    $flowId = $agent['response_engine']['conversation_flow_id'] ?? 'N/A';
    $version = $agent['version'] ?? 0;
    $isPublished = $agent['is_published'] ?? false;

    echo "─────────────────────────────────────────────────────────\n";
    echo "Agent: $agentName\n";
    echo "ID: $agentId\n";
    echo "Flow: $flowId\n";
    echo "Version: $version\n";
    echo "Published: " . ($isPublished ? 'YES' : 'NO') . "\n";

    if ($flowId === $v109FlowId) {
        echo "🎯 HAS V109 FLOW!\n";
        $v109Agents[] = $agent;
    }

    if (strpos($agentName, 'Friseur') !== false || strpos($agentName, 'friseur') !== false) {
        echo "👤 Friseur Agent\n";
    }

    echo "\n";
}

echo "═══════════════════════════════════════════════════════════\n";
echo "  SUMMARY\n";
echo "═══════════════════════════════════════════════════════════\n\n";

if (count($v109Agents) > 0) {
    echo "✅ Found " . count($v109Agents) . " agent(s) with V109 flow:\n\n";

    foreach ($v109Agents as $agent) {
        echo "  Agent ID: " . ($agent['agent_id'] ?? 'N/A') . "\n";
        echo "  Agent Name: " . ($agent['agent_name'] ?? 'N/A') . "\n";
        echo "  Version: " . ($agent['version'] ?? 0) . "\n";
        echo "  Published: " . (($agent['is_published'] ?? false) ? 'YES' : 'NO') . "\n";
        echo "\n";
    }

    echo "RECOMMENDED ACTION:\n";
    echo "Update phone +493033081738 to use one of these agents\n";

} else {
    echo "❌ No agents found with V109 flow!\n\n";

    echo "RECOMMENDED ACTION:\n";
    echo "1. Create a NEW agent with V109 flow\n";
    echo "2. Update phone +493033081738 to use the new agent\n";
}

echo "\n";
echo "═══════════════════════════════════════════════════════════\n";
echo "  DONE\n";
echo "═══════════════════════════════════════════════════════════\n\n";
