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
echo "📋 PUBLISH INSTRUCTIONS\n";
echo "═══════════════════════════════════════════════════════════\n\n";

// Get current agent state
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

echo "Current Agent State:\n";
echo "───────────────────────────────────────────────────────────\n";
echo "Version: $currentVersion\n";
echo "Published: " . ($isPublished ? 'YES ✅' : 'NO ❌') . "\n\n";

if ($isPublished) {
    echo "✅ Agent is already published!\n";
    echo "   This might be an old version.\n";
    echo "   If you just deployed a new version, you need to publish that one.\n\n";
}

// Find the unpublished version (should be one version back)
$unpublishedVersion = $currentVersion - 1;

if (!$isPublished) {
    $unpublishedVersion = $currentVersion;
}

echo "═══════════════════════════════════════════════════════════\n";
echo "🎯 ACTION REQUIRED\n";
echo "═══════════════════════════════════════════════════════════\n\n";

echo "1️⃣  Open this URL in your browser:\n\n";
echo "   \033[1;34mhttps://dashboard.retellai.com/agent/$agentId\033[0m\n\n";

echo "2️⃣  Find this version:\n\n";
echo "   Version: \033[1;32m$unpublishedVersion\033[0m\n";
echo "   Tools: 7 (initialize, check_availability, book, get_appointments, cancel, reschedule, get_services)\n";
echo "   Status: Draft / Not Published\n\n";

echo "3️⃣  Click the \033[1;32m\"Publish\"\033[0m button\n\n";

echo "4️⃣  Verify with this command:\n\n";
echo "   php scripts/deployment/verify_published.php\n\n";

echo "═══════════════════════════════════════════════════════════\n";
echo "💡 TIP\n";
echo "═══════════════════════════════════════════════════════════\n\n";

echo "Copy this command to open URL directly:\n\n";

if (PHP_OS_FAMILY === 'Linux') {
    echo "xdg-open 'https://dashboard.retellai.com/agent/$agentId'\n\n";
} elseif (PHP_OS_FAMILY === 'Darwin') {
    echo "open 'https://dashboard.retellai.com/agent/$agentId'\n\n";
} else {
    echo "start 'https://dashboard.retellai.com/agent/$agentId'\n\n";
}

echo "═══════════════════════════════════════════════════════════\n\n";
