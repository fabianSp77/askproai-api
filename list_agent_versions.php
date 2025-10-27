<?php

require __DIR__ . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

$apiKey = $_ENV['RETELLAI_API_KEY'] ?? $_ENV['RETELL_TOKEN'] ?? null;
$agentId = 'agent_f1ce85d06a84afb989dfbb16a9';

echo "═══════════════════════════════════════════════════════\n";
echo "📚 LISTING ALL AGENT VERSIONS\n";
echo "═══════════════════════════════════════════════════════\n\n";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "https://api.retellai.com/get-agent-versions/$agentId");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer $apiKey",
    "Content-Type: application/json"
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode !== 200) {
    echo "❌ Failed to fetch versions! HTTP $httpCode\n";
    echo "Response: $response\n";
    exit(1);
}

$versions = json_decode($response, true);

echo "Total Versions: " . count($versions) . "\n\n";

// Sort by version number descending
usort($versions, function($a, $b) {
    return ($b['version'] ?? 0) - ($a['version'] ?? 0);
});

foreach ($versions as $version) {
    $versionNum = $version['version'] ?? 'N/A';
    $published = $version['is_published'] ?? false;
    $versionTitle = $version['version_title'] ?? 'No title';

    $status = $published ? "✅ PUBLISHED" : "📝 DRAFT";

    echo "─────────────────────────────────────────\n";
    echo "Version: $versionNum\n";
    echo "Status: $status\n";
    echo "Title: $versionTitle\n";

    if (isset($version['last_modification_timestamp'])) {
        $timestamp = $version['last_modification_timestamp'] / 1000;
        $dateTime = new DateTime('@' . $timestamp);
        $dateTime->setTimezone(new DateTimeZone('Europe/Berlin'));
        echo "Modified: " . $dateTime->format('Y-m-d H:i:s') . " (Berlin)\n";
    }

    if (isset($version['response_engine']['conversation_flow_id'])) {
        echo "Flow ID: " . $version['response_engine']['conversation_flow_id'] . "\n";
        echo "Flow Version: " . ($version['response_engine']['version'] ?? 'N/A') . "\n";
    }

    echo "\n";
}

echo "═══════════════════════════════════════════════════════\n";
echo "🎯 PUBLISHED VERSION SUMMARY\n";
echo "═══════════════════════════════════════════════════════\n\n";

$publishedVersions = array_filter($versions, function($v) {
    return $v['is_published'] ?? false;
});

if (count($publishedVersions) > 0) {
    echo "✅ Published Versions Found: " . count($publishedVersions) . "\n\n";

    foreach ($publishedVersions as $pv) {
        echo "   Version " . ($pv['version'] ?? 'N/A') . ": " . ($pv['version_title'] ?? 'No title') . "\n";
    }
} else {
    echo "❌ NO published versions found!\n";
}

echo "\n═══════════════════════════════════════════════════════\n";
