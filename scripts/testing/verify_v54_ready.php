#!/usr/bin/env php
<?php

require __DIR__ . '/../../vendor/autoload.php';
$app = require_once __DIR__ . '/../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Http;

$agentId = 'agent_f1ce85d06a84afb989dfbb16a9';
$friseurPhone = '+493033081738';
$token = env('RETELL_TOKEN');

echo "\n";
echo "═══════════════════════════════════════════════════════════\n";
echo "QUICK CHECK: Ist V54 published & Phone gemappt?\n";
echo "═══════════════════════════════════════════════════════════\n\n";

$allGood = true;

// Check 1: Is agent published?
echo "1️⃣  Checking Agent Status...\n";

$agentResponse = Http::withHeaders([
    'Authorization' => "Bearer $token",
])->get("https://api.retellai.com/get-agent/$agentId");

if ($agentResponse->successful()) {
    $agent = $agentResponse->json();
    $version = $agent['version'] ?? 'unknown';
    $isPublished = $agent['is_published'] ?? false;

    echo "   Agent Version: $version\n";

    if ($isPublished) {
        echo "   ✅ Agent IS PUBLISHED\n";

        // Check if it's likely our version (can't check tools via API)
        if ($version >= 54) {
            echo "   ✅ Version $version (likely V54 or newer)\n";
        } else {
            echo "   ⚠️  Version $version (expected >=54)\n";
            echo "   → Check if you published the right version!\n";
            $allGood = false;
        }
    } else {
        echo "   ❌ Agent is NOT PUBLISHED\n";
        echo "   → Go to dashboard and publish Version 54!\n";
        $allGood = false;
    }
} else {
    echo "   ❌ Failed to get agent status\n";
    $allGood = false;
}

echo "\n";

// Check 2: Is phone mapped?
echo "2️⃣  Checking Phone Mapping...\n";

$phoneResponse = Http::withHeaders([
    'Authorization' => "Bearer $token",
])->get('https://api.retellai.com/list-phone-numbers');

if ($phoneResponse->successful()) {
    $phones = $phoneResponse->json();
    $found = false;
    $correctMapping = false;

    foreach ($phones as $phone) {
        if (($phone['phone_number'] ?? '') === $friseurPhone) {
            $found = true;
            $mappedAgent = $phone['agent_id'] ?? 'NONE';

            echo "   Phone: $friseurPhone\n";
            echo "   Mapped to: $mappedAgent\n";

            if ($mappedAgent === $agentId) {
                echo "   ✅ CORRECTLY MAPPED!\n";
                $correctMapping = true;
            } else {
                echo "   ❌ WRONG MAPPING!\n";
                echo "   → Expected: $agentId\n";
                echo "   → Go to dashboard and map the phone!\n";
                $allGood = false;
            }
            break;
        }
    }

    if (!$found) {
        echo "   ❌ Phone $friseurPhone not found!\n";
        $allGood = false;
    }
} else {
    echo "   ❌ Failed to get phone numbers\n";
    $allGood = false;
}

echo "\n";
echo "═══════════════════════════════════════════════════════════\n";

if ($allGood) {
    echo "🟢 ALL GOOD! System is ready for test call!\n";
    echo "═══════════════════════════════════════════════════════════\n\n";

    echo "✅ Next step: Make test call to $friseurPhone\n";
    echo "✅ Then run: php artisan tinker\n";
    echo "   \$call = \\App\\Models\\RetellCallSession::latest()->first();\n";
    echo "   \$call->functionTraces->pluck('function_name');\n\n";

    exit(0);
} else {
    echo "🔴 NOT READY - Please fix the issues above!\n";
    echo "═══════════════════════════════════════════════════════════\n\n";

    echo "Dashboard URLs:\n";
    echo "  Agent: https://dashboard.retellai.com/agent/$agentId\n";
    echo "  Phones: https://dashboard.retellai.com/phone-numbers\n\n";

    exit(1);
}
