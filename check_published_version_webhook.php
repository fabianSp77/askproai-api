<?php

require __DIR__ . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

$apiKey = $_ENV['RETELLAI_API_KEY'] ?? $_ENV['RETELL_TOKEN'];
$agentId = 'agent_f1ce85d06a84afb989dfbb16a9';

echo "═══════════════════════════════════════════════════════\n";
echo "🔍 CHECKING PUBLISHED VERSIONS WEBHOOK CONFIG\n";
echo "═══════════════════════════════════════════════════════\n\n";

// Get all versions
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "https://api.retellai.com/get-agent-versions/$agentId");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer $apiKey",
    "Content-Type: application/json"
]);

$response = curl_exec($ch);
curl_close($ch);

$versions = json_decode($response, true);

// Sort by version desc
usort($versions, fn($a, $b) => ($b['version'] ?? 0) - ($a['version'] ?? 0));

// Show last 5 published versions
echo "📋 Latest Published Versions Webhook Config:\n\n";

$count = 0;
foreach ($versions as $version) {
    if ($version['is_published'] ?? false) {
        $count++;
        if ($count > 5) break;

        echo "─────────────────────────────────────────\n";
        echo "Version: " . ($version['version'] ?? 'N/A') . "\n";
        echo "Title: " . ($version['version_title'] ?? 'No title') . "\n";
        echo "Published: YES\n";
        echo "Webhook URL: " . ($version['webhook_url'] ?? '❌ NOT SET') . "\n";

        if (isset($version['last_modification_timestamp'])) {
            $timestamp = $version['last_modification_timestamp'] / 1000;
            $dateTime = new DateTime('@' . $timestamp);
            $dateTime->setTimezone(new DateTimeZone('Europe/Berlin'));
            echo "Modified: " . $dateTime->format('Y-m-d H:i:s') . " (Berlin)\n";
        }

        echo "\n";
    }
}

// Check specifically version 42 (the one phone number uses)
echo "═══════════════════════════════════════════════════════\n";
echo "🎯 PHONE NUMBER USES VERSION 42\n";
echo "═══════════════════════════════════════════════════════\n\n";

$version42 = null;
foreach ($versions as $v) {
    if (($v['version'] ?? 0) === 42) {
        $version42 = $v;
        break;
    }
}

if ($version42) {
    echo "✅ Found Version 42:\n\n";
    echo "Version: 42\n";
    echo "Published: " . (($version42['is_published'] ?? false) ? 'YES' : 'NO') . "\n";
    echo "Webhook URL: " . ($version42['webhook_url'] ?? '❌ NOT SET') . "\n";
    echo "Webhook Timeout: " . ($version42['webhook_timeout_ms'] ?? 'N/A') . " ms\n\n";

    if (empty($version42['webhook_url'])) {
        echo "🚨 CRITICAL PROBLEM FOUND!\n";
        echo "   Version 42 has NO WEBHOOK URL!\n";
        echo "   This is why calls are not being tracked!\n\n";
        echo "🔧 SOLUTION:\n";
        echo "   Need to update Version 42 webhook URL via API\n";
        echo "   But published versions are immutable!\n";
        echo "   Need to:\n";
        echo "   1. Update DRAFT (v43) webhook if not set\n";
        echo "   2. Publish new version\n";
        echo "   3. Update phone number to new version\n\n";
    } else {
        echo "✅ Webhook URL is configured\n\n";
        echo "🤔 But webhooks are not arriving...\n";
        echo "   Possible causes:\n";
        echo "   1. Retell is not sending webhooks\n";
        echo "   2. Network/firewall blocking\n";
        echo "   3. Wrong webhook URL format\n";
        echo "   4. Laravel route not working\n\n";
    }
} else {
    echo "❌ Version 42 not found!\n\n";
}
